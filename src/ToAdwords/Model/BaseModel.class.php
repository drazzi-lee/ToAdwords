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

use ToAdwords\Model\Driver\DbMysql;
use ToAdwords\Util\Log;

use \PDO;

/**
 * BaseModel
 *
 * An abstract class, which defines some functions for common use.
 */
abstract class BaseModel{

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
		foreach($preparedParams as $key => $value) {
			if(is_string($value))
				$value = "'$value'";
			if($indexed)
				$this->lastSql = preg_replace('/\?/', $value, $string, 1);
			else
				$this->lastSql = str_replace("$key", $value, $string);
		}
	}

	protected function getLastSql(){
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
				throw new DataCheckException('解析查询条件失败，请检查是否符合语法'.__METHOD__);
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
	 * @throw PDOException,DataCheckException
	 */
	protected function insertOne($data){
		$preparedInsert = $this->prepareInsert($data);
		$sql = 'INSERT INTO `' . $this->tableName.'`'
				. ' (' . $preparedInsert['placeHolders']['field'] . ')'
				. ' VALUES (' .$preparedInsert['placeHolders']['value'] . ')';
		$statement = $this->dbh->prepare($sql);
		$this->setLastSql($sql, $preparedInsert['paramValues'];
		return $statement->execute($preparedInsert['paramValues']);
	}

	/**
	 * 更新数据表某条信息
	 *
	 * @param string $conditions: 更新条件，一般为unique_id=123形式
	 * @param array $data: 更新内容
	 * @return boolean: TRUE, FALSE
	 * @throw PDOException,DataCheckException
	 */
	protected function updateOne($conditions, array $data){
		$preparedUpdates = $this->prepareUpdate($data, $conditions);

		$sql = 'UPDATE `' . $this->tableName . '` SET '. $preparedUpdates['placeHolders']
							. ' WHERE ' . $preparedUpdates['placeHoldersWhere'];

		$statement = $this->dbh->prepare($sql);
		$this->setLastSql($sql, $preparedUpdates['paramValues'];
		return $statement->execute($preparedUpdates['paramValues']);
	}

	/**
	 * 更新ObjectId对应的数据表同步状态
	 *
	 * @param string $status: SYNC_STATUS_QUEUE等
	 * @param Base $object:
	 * @return boolean: TRUE, FALSE
	 * @throw PDOException,DataCheckException
	 */
	protected function updateSyncStatus($status, Base $object){
		$sql = 'UPDATE `'.$this->tableName.'` SET sync_status=:sync_status';
		$preparedParams = array();

		if(!in_array($status, array(self::SYNC_STATUS_QUEUE, self::SYNC_STATUS_RECEIVE,
				self::SYNC_STATUS_SYNCED, self::SYNC_STATUS_ERROR, self::SYNC_STATUS_RETRY))){
			throw new DataCheckException('SYNC_STATUS未被允许的同步状态类型::'.$status);
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
	 * 在IDCLICK与ADWORDS的ID之间转换
	 *
	 * 此方法一般在子类使用，需要子类的adwordsObjectIdField, idclickObjectIdField
	 * 暂支持IDCLICK到ADWORDS单向转换
	 *
	 * @param Base $object: AdwordsObject | IdclickObject
	 * @return int
	 */
	protected function getAdaptedId(Base $object){
		if(!$object instanceof IdclickBase){
			throw new DataCheckException('尚未支持的objectType类型，返回FALSE。object::'
														.get_class($object).' METHOD::'.__METHOD__);
		}

		$row = $this->getAdapteInfo($object);
		if(!empty($row)){
			switch($row[$this->fieldSyncStatus]){
				case self::SYNC_STATUS_SYNCED:
					if(empty($row[$this->adwordsObjectIdField])) {
						throw new SyncStatusException('已同步状态但是没有ADWORDS
								对应信息IdclickId为'.$object->getId().' 对象：'.get_class($object));
					}
					return $row[$this->adwordsObjectIdField];
					break;
				case self::SYNC_STATUS_QUEUE:
					if(empty($row[$this->adwordsObjectIdField])){
						//如果获取的ID为空，且状态为QUEUE，则发送一条更新本数据表对应ADWORDS_ID的消息
					}
					return TRUE;
					break;
				//case self::SYNC_STATUS_RECEIVE:
				//case self::SYNC_STATUS_ERROR:
				default:
					throw new SyncStatusException('对象:'.get_class($object).' objectId'.$object->getId());
			}
		} else {
			if($object instanceof Member){
				return $this->create($object->getId());
			} else {
				throw new DependencyException('还没有创建上级依赖，请先创建上级对象:'.get_class($this));
			}
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
	 * 检查数据是否完整
	 *
	 * 根据当前模块配置的dataCheckFilter来进行验证，同时过滤掉不需要的字段。
	 *
	 * @return void.
	 */
	protected function checkData(&$data, $action){
		$filter = $this->dataCheckFilter[$action];
		foreach($filter['prohibitedFields'] as $item){
			if(isset($data[$item])){
				if(ENVIRONMENT == 'development'){
					Log::write('[WARNING]检查到禁止设置的字段，字段：'
												. $item . ' #'. $data[$item], __METHOD__);
				}
				unset($data[$item]);
			}
		}
		foreach($filter['requiredFields'] as $item){
			if(!isset($data[$item])){
				if(ENVIRONMENT == 'development'){
					Log::write('检查到不符合条件的数据，未设置：' . $item, __METHOD__);
				}
				throw new DataCheckException('检查到不符合条件的数据，未设置：' . $item);
				break;
			}
		}
	}
}
