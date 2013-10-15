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

use ToAdwords\SyncStatus;
use ToAdwords\Model\Driver\DbMysql;
use ToAdwords\Util\Log;
use ToAdwords\Exceptions\DataCheckException;

use \PDO;

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
	/**
	 * 是否检查数据完整性
	 */
	const IS_CHECK_DATA = TRUE;

	/**
	 * 数据执行动作定义
	 */
	const ACTION_CREATE = 'CREATE';
	const ACTION_UPDATE = 'UPDATE';
	const ACTION_DELETE = 'DELETE';

	protected $tableName;
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
	protected function getOne($fields='*', $conditions){
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
		$sql = 'SELECT '.$fields.' FROM `'.$this->tableName.'`';
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
				throw new DataCheckException('Parsing conditions error, given #' . $conditions);
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
		if(self::IS_CHECK_DATA){
			$this->checkData($data, self::ACTION_CREATE);
		}
		$preparedInsert = $this->prepareInsert($data);
		$sql = 'INSERT INTO `' . $this->tableName.'`'
				. ' (' . $preparedInsert['placeHolders']['field'] . ')'
				. ' VALUES (' .$preparedInsert['placeHolders']['value'] . ')';
		$statement = $this->dbh->prepare($sql);
		$this->setLastSql($sql, $preparedInsert['paramValues']);
		return $statement->execute($preparedInsert['paramValues']);
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
		if(self::IS_CHECK_DATA){
			$this->checkData($data, self::ACTION_CREATE);
		}
		$preparedUpdates = $this->prepareUpdate($data, $conditions);

		$sql = 'UPDATE `' . $this->tableName . '` SET '. $preparedUpdates['placeHolders']
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
	public function updateSyncStatus($status, Base $object){
		$sql = 'UPDATE `'.$this->tableName.'` SET sync_status=:sync_status';
		$preparedParams = array();

		if(!in_array($status, array('RECEIVE', 'QUEUE', 'SYNCED', 'ERROR', 'RETRY'))){
			throw new DataCheckException('未定义的同步状态::'.$status);
		} else {
			$preparedParams[':sync_status'] = $status;
		}

		if($object instanceof IdclickBase){
			$sql .= ' WHERE '.$this->idclickObjectIdField.'=:'.$this->idclickObjectIdField;
			$preparedParams[':'.$this->idclickObjectIdField] = $object->getId();
		}
		if($object instanceof AdwordsBase){
			$sql .= ' WHERE '.$this->adwordsObjectIdField.'=:'.$this->adwordsObjectIdField;
			$preparedParams[':'.$this->adwordsObjectIdField] = $object->getId();
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
	protected function getAdapteInfo(Base $object){
		if($object instanceof IdclickBase){
			return $this->getOne($this->adwordsObjectIdField.','.$this->idclickObjectIdField
						.',sync_status', $this->idclickObjectIdField.'='.$object->getId());
		}
		if($object instanceof AdwordsBase){
			return $this->getOne($this->adwordsObjectIdField.','.$this->idclickObjectIdField
						.',sync_status', $this->adwordsObjectIdField.'='.$object->getId());
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
	 * Check whether the data meets the requirements.
	 *
	 * According to the current module's dataCheckFilter, verify that the data is valid, while
	 * filtering out the fields prohibited.
	 *
	 * @return void.
	 */
	protected function checkData(&$data, $action){
		$filter = $this->dataCheckFilter[$action];
		foreach($filter['prohibitedFields'] as $item){
			if(isset($data[$item])){
				if(ENVIRONMENT == 'development'){
					Log::write('[WARNING] A prohibited fields found, Field #'
												. $item . ' Value #'. $data[$item], __METHOD__);
				}
				unset($data[$item]);
			}
		}
		foreach($filter['requiredFields'] as $item){
			if(!isset($data[$item])){
				if(ENVIRONMENT == 'development'){
					Log::write('[WARNING] Field #' . $item . ' is required.', __METHOD__);
				}
				throw new DataCheckException('[WARNING] Field #' . $item . ' is required.');
				break;
			}
		}
		foreach($data as $key => $item){
			if(is_array($item)){
				$data[$key] = $this->arrayToString($item);
				if(ENVIRONMENT == 'development'){
					Log::write('[WARNING] Field #' . $item . ' Array to String conversion.', __METHOD__);
				}
			}
		}
	}

	/**
	 * Check whether the given primary id is exists in current table.
	 * @TODO incomplete.
	 */
	protected function isExists($primaryId){
		return FALSE;
	}
}
