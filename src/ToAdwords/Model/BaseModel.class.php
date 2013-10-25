<?php


/**
 * BaseModel.class.php
 *
 * Defines class BaseModel, parent class of Models.
 *
 * @author Li Pengfei
 * @email drazzi.lee@gmail.com
 * @version 1.0
 */
namespace ToAdwords\Model;

use ToAdwords\Definition\SyncStatus;
use ToAdwords\Model\Driver\DbMysql;
use ToAdwords\Util\Log;
use ToAdwords\Exception\ModelException;

use \PDO;
use \PDOException;

/**
 * BaseModel, A Model Packaging database operations. 
 *
 * Provides a simple layer to operate database for actions from Adwords Adapter.
 *
 * It does:
 *	1. checking whether an object is exists.
 *	2. insert all kinds of object.
 *	3. checking whether the parent object is exists.
 *	4. update all kinds of object.
 *	5. update parent object id.
 *	6. update current object id and synchronous status. 
 */
abstract class BaseModel{
	protected static $tableName;
	protected static $syncStatusField = 'sync_status';

	protected $dbh = null;
	protected $lastSql;

	public function __construct(){
		$this->dbh = DbMysql::getInstance();
	}

	/**
	 * Set Last Sql being executed.
	 *
	 * Only when PDO::Statement prepared with $sql, and Executed by an Array contains params.
	 * 
	 * @param string $sql: last prepared $sql, which is the PDO::prepare() 's param also.
	 * @param array $preparedParams: An array contains params' value.
	 * @return void.
	 */
	protected function setLastSql($sql, $preparedParams){
		$indexed = $preparedParams == array_values($preparedParams);
		$this->lastSql = $sql;
		foreach($preparedParams as $key => $value) {
			if(is_string($value))
				$value = "'$value'";
			if($indexed)
				$this->lastSql = preg_replace('/\?/', $value, $this->lastSql, 1);
			else
				$this->lastSql = str_replace("$key", $value, $this->lastSql);
		}
	}

	public function beginTransaction(){
		return $this->dbh->beginTransaction();
	}

	public function commit(){
		return $this->dbh->commit();
	}

	public function rollBack(){
		return $this->dbh->rollBack();
	}

	public function inTransaction(){
		return $this->dbh->inTransaction();
	}

	public function getLastSql(){
		return $this->lastSql;
	}

	/**
	 * Get only one row from table.
	 *
	 * This method returns a one-dimensional array. PDO::FETCH_ASSOC: returns an array
	 * indexed by column name as returned in your result set.
	 *
	 * @param string $fields: $fields = 'id,name,gender';
	 * @param string $conditions: $conditions = 'id=1234 AND name="Bob"';
	 * @return array $row
	 */
	public function getOne($fields='*', $conditions){
		$statement = $this->select($fields, $conditions, 1);
		return $statement->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * Get rows from current table.
	 *
	 * This method returns a two-dimensional array. PDO::FETCH_ASSOC: returns an array
	 * indexed by column name as returned in your result set.
	 *
	 * @param string $fields: $fields = 'id,name,gender';
	 * @param string $conditions: $conditions = 'id=1234 AND name="Bob"';
	 * @param int,string $limit : default 1000
	 * @return array $rows
	 */
	protected function getRows($fields='*', $conditions, $limit = '1000'){
		$statement = $this->select($fields, $conditions, $limit);
		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * A wrapper for easy to use select
	 *
	 * @param string $fields: $fields = 'id,name,gender';
	 * @param string $conditions: $conditions = 'id=1234 AND name="Bob"';
	 * @param int,string $limit : default 1000
	 * @return PDO::Statement $statement
	 */
	protected function select($fields='*', $conditions, $limit = '1000'){
		$sql = 'SELECT '.$fields.' FROM `'.static::$tableName.'`';
		$paramValues = array();

		if(!empty($conditions)){
			$preparedConditions = $this->prepareSelect($conditions);
			$sql .= ' WHERE '.$preparedConditions['placeHolders'];
			$paramValues = $preparedConditions['paramValues'];
		}

		if(!is_numeric($limit)){
			$limit = (int)$limit;
			if(ENVIRONMENT == 'development')
				Log::write('[NOTICE] param $limit has converted to integer automatically.'
							, __METHOD__);
		}
		$sql .= ' LIMIT '.$limit;

		$statement = $this->dbh->prepare($sql);
		$statement->execute($paramValues);
		$this->setLastSql($sql, $paramValues);
		return $statement;
	}

	/**
	 * Prepare conditions to sql-parsed condition and params.
	 *
	 * @param string $conditions: $conditions = 'id=1234 AND name="Bob"';
	 * @return array $preparedConditions: array(
	 *					'placeHolders' 	=> 'WHERE id=:id AND name=:name',
	 *					'paramValues'	=> array(':id' => '1234', ':name' => 'Bob'),
	 *				);
	 */
	protected function prepareSelect($conditions){
		if(empty($conditions)){
			return NULL;
		}
		$placeHolders = preg_replace('/(\w+)(>|=|<)(\S+)/', '$1$2:$1', $conditions);
		$conditionsArray = preg_grep('/(\w+)(>|=|<)(\S+)/', preg_split('/(\s+)/', $conditions));
		$paramValues = array();
		foreach($conditionsArray as $condition){
			$conditionSplited = explode('=', $condition);
			$paramValues[':'.$conditionSplited[0]] = $conditionSplited[1];
		}
		if(0 == count($paramValues)){
			if('ENVIRONMENT' == 'development')
				throw new ModelException('Parsing conditions error, given #' . $conditions);
			return NULL;
		}
		return array('placeHolders' => $placeHolders, 'paramValues' => $paramValues);
	}

	/**
	 * Prepare informations to sql-parsed placeholders and paramvalues for insert.
	 *
	 * @param array $data: informations for insert.
	 * @return array.
	 */
	protected function prepareInsert($data){
		$fieldsCombine = $this->arrayToString(array_keys($data));
		$placeHolders = $this->arrayToSpecialString(array_keys($data));
		$paramValues = array_combine(explode(',',$placeHolders), array_values($data));
		return array(
			'placeHolders' => array(
				'value'=>$placeHolders,
				'field'=>$fieldsCombine
			),
			'paramValues' => $paramValues
		);
	}

	/**
	 * Prepare informations to sql-parsed placeholders and paramvalues for update.
	 *
	 * @param array $data: informations for update.
	 * @param string $conditions
	 * @return array.
	 */
	protected function prepareUpdate($data, $conditions){
		$where = $this->prepareSelect($conditions);
		$placeHoldersArray = array();
		$paramValuesData = array();
		foreach($data as $key => $value){
			$placeHoldersArray[] = $key.'=:'.$key;
			$paramValuesData[':'.$key] = $value;
		}
		$placeHolders = $this->arrayToString($placeHoldersArray);
		$paramValues = array_merge($where['paramValues'], $paramValuesData);
		return array(
			'placeHolders' 		=> $placeHolders,
			'placeHoldersWhere' => $where['placeHolders'],
			'paramValues' 		=> $paramValues);
	}

	/**
	 * Insert one row data to table.
	 *
	 * @param array $data: information for inserting.
	 * @return boolean: TRUE, FALSE
	 * @throw PDOException, Datacheckexception;
	 */
	public function insertOne($data){
		try{
			$preparedInsert = $this->prepareInsert($data);
			$sql = 'INSERT INTO `' . static::$tableName . '` (' . $preparedInsert['placeHolders']['field'] . ') VALUES (' . $preparedInsert['placeHolders']['value'] . ')';
			$statement = $this->dbh->prepare($sql);
			$this->setLastSql($sql, $preparedInsert['paramValues']);
			return $statement->execute($preparedInsert['paramValues']);
		} catch(PDOException $e){
			throw new ModelException($this->getLastSql(). ' ErrorInfo:'. $e->getMessage());
		}
	}

	/**
	 * Update one row in table.
	 *
	 * @param string $conditions: conditions where to updates, like string "unique_id=123".
	 * @param array $data: data what need to updates.
	 * @return boolean: TRUE, FALSE
	 * @throw PDOException, Datacheckexception;
	 */
	public function updateOne($conditions, array $data){
		$preparedUpdates = $this->prepareUpdate($data, $conditions);

		$sql = 'UPDATE `' . static::$tableName . '` SET '. $preparedUpdates['placeHolders']
							. ' WHERE ' . $preparedUpdates['placeHoldersWhere'];

		$statement = $this->dbh->prepare($sql);
		$this->setLastSql($sql, $preparedUpdates['paramValues']);
		return $statement->execute($preparedUpdates['paramValues']);
	}

	/**
	 * Update the synchronous status of object given.
	 *
	 * @param string $status: SYNC_STATUS_QUEUE等
	 * @param Base $object:
	 * @return boolean: TRUE, FALSE
	 * @throw PDOException
	 */
	public function updateSyncStatus($status, $objectId, $isIdclickObject = TRUE){
		if( 0 === (int)$objectId){
			return FALSE;
		}
		$sql = 'UPDATE `'.static::$tableName.'` SET sync_status=:sync_status';
		$preparedParams = array();

		if(!SyncStatus::isValid($status)){
			throw new ModelException('[SyncStatus::isValid] returns FALSE #'.$status);
		} else {
			$preparedParams[':'.static::$syncStatusField] = $status;
		}

		if($isIdclickObject){
			$sql .= ' WHERE '.static::$idclickObjectIdField.'=:'.static::$idclickObjectIdField;
			$preparedParams[':'.static::$idclickObjectIdField] = $objectId;
		} else {
			$sql .= ' WHERE '.static::$adwordsObjectIdField.'=:'.static::$adwordsObjectIdField;
			$preparedParams[':'.static::$adwordsObjectIdField] = $objectId;
		}

		$statement = $this->dbh->prepare($sql);
		$this->setLastSql($sql, $preparedParams);
		return $statement->execute($preparedParams);
	}

	/**
	 * 获取IDCLICK与ADWORDS对照信息
	 *
	 * 同时返回同步状态信息，以便后续处理。
	 *
	 * @param Base $object: AdwordsObject | IdclickObject
	 * @return array: NULL | array()
	 * @throw PDOException
	 */
	public function getAdapteInfo($idclickObjectId){
		try{
			return $this->getOne(static::$adwordsObjectIdField.','.static::$idclickObjectIdField
					.','.static::$syncStatusField, static::$idclickObjectIdField.'='.$idclickObjectId);
		} catch(PDOException $e){
			throw new ModelException($this->getLastSql(). ' ErrorInfo:'. $e->getMessage());
		}
	}

	/**
	 * Join array elements with comma.
	 *
	 * Returns a string containing a string representation of all the array elements in the same
	 * order, with the glue "," string between each element.
	 *
	 * @param array $array
	 * @return string
	 */
	protected function arrayToString(array $array){
		return implode(',', $array);
	}

	protected function arrayToSpecialString(array $array){
		return ':'.implode(',:', $array);
	}

	/**
	 * Check the given AdwordsObjectId is valid or not.
	 *
	 * @param integer $adwordsObjectId:
	 * @return boolean.
	 * @todo check the sync_status is ok.
	 */
	public function isValidAdwordsId($adwordsObjectId){
		if((int)$adwordsObjectId < 10000){
			return FALSE;
		} else {
			return TRUE;
		}
	}
}
