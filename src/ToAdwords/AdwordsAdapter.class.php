<?php

namespace ToAdwords;

require_once 'Adapter.interface.php';

use ToAdwords\Adapter;
use ToAdwords\Object\Base;
use ToAdwords\Object\Adwords\AdwordsBase;
use ToAdwords\Object\Idclick\Member;
use ToAdwords\Object\Idclick\IdclickBase;
use ToAdwords\Util\Log;
use ToAdwords\Util\Message;
use ToAdwords\CustomerAdapter;
use ToAdwords\Exceptions\DependencyException;
use ToAdwords\Exceptions\SyncStatusException;
use ToAdwords\Exceptions\DataCheckException;
use ToAdwords\Exceptions\MessageException;
use ToAdwords\MessageHandler;
use ToAdwords\Definitions\SyncStatus;

use \PDO;
use \PDOException;
use \Exception;

abstract class AdwordsAdapter implements Adapter{

	/**
	 * 结果描述文字定义
	 */
	const DESC_DATA_CHECK_FAILURE   = '提供数据不完整，请检查数据。';
	const DESC_DATA_PROCESS_SUCCESS = '成功处理了所有数据';
	const DESC_DATA_PROCESS_WARNING = '执行完毕，有部分数据未正常处理：：';

	/**
	 * 数据库操作相关设置
	 */
	protected $dbh = null;
	protected $fieldSyncStatus = 'sync_status';

	/**
	 * 处理结果
	 *
	 * $result = array(
	 *			'status'	=> 1,	//1：成功；0：失败或者部分失败
	 *									-1: 提供参数不完整或解析失败
	 *			'success'	=> 7,	//成功添加的内容计数
	 *			'failure'	=> 0	//如果status不为1，则failure有计数
	 *			'description'	=> 文字描述
	 * 		)
	 * @var array $result
	 */
	protected $result = array(
				'status'		=> null,
				'description'	=> null,
				'success'		=> 0,
				'failure'		=> 0,
			);
	protected $processed = 0;

	/**
	 * 此构造过程一般被接口层直接调用，需要内部直接处理异常。
	 */
	public function __construct(){
		try {
			$this->dbh = new PDO(TOADWORDS_DSN, TOADWORDS_USER, TOADWORDS_PASS);
			$this->dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			$this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e){
			if(ENVIRONMENT == 'development'){
				trigger_error('数据库连接错误，实例化'.__CLASS__.'失败。', E_USER_ERROR);
			} else {
				Log::write('数据库连接错误，实例化'.__CLASS__.'失败。', __METHOD__);
				trigger_error('A system error occurred.', E_USER_WARNING);
			}
		} catch (Exception $e){
			if(ENVIRONMENT == 'development'){
				trigger_error('未知错误，实例化'.__CLASS__.'失败。', E_USER_ERROR);
			} else {
				Log::write('未知错误，实例化'.__CLASS__.'失败。', __METHOD__);
				trigger_error('A system error occurred.', E_USER_WARNING);
			}
		}
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
				trigger_error('limit只能为数字，已自动转换', E_USER_NOTICE);
		}
		$sql .= ' LIMIT '.$limit;

		$statement = $this->dbh->prepare($sql);
		$statement->execute($paramValues);
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
	 * 根据当前类型准备PlaceHolder和Params
	 *
	 * @param array $data: 当前需要操作的数据信息
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
	 *
	 * @param string $conditions
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
	 * 向数据表插入一条信息
	 * @TODO 根据CampaignAdapter->insertOne抽象方法
	 * @param array $data: 插入内容
	 * @return boolean: TRUE, FALSE
	 * @throw PDOException
	 */
	public function insertOne($data){
		$preparedInsert = $this->prepareInsert($data);
		$sql = 'INSERT INTO `' . $this->tableName.'`'
				. ' (' . $preparedInsert['placeHolders']['field'] . ')'
				. ' VALUES (' .$preparedInsert['placeHolders']['value'] . ')';
		$statement = $this->dbh->prepare($sql);
		return $statement->execute($preparedInsert['paramValues']);
	}

	/**
	 * 更新数据表某条信息
	 *
	 * @param string $conditions: 更新条件，一般为unique_id=123形式
	 * @param array $data: 更新内容
	 * @return boolean: TRUE, FALSE
	 * @throw PDOException
	 */
	public function updateOne($conditions, array $data){
		$preparedUpdates = $this->prepareUpdate($data, $conditions);

		$sql = 'UPDATE `' . $this->tableName . '` SET '. $preparedUpdates['placeHolders']
							. ' WHERE ' . $preparedUpdates['placeHoldersWhere'];

		$statement = $this->dbh->prepare($sql);
		return $statement->execute($preparedUpdates['paramValues']);
	}

	/**
	 * 更新ObjectId对应的数据表同步状态
	 *
	 * @param string $status: SYNC_STATUS_QUEUE等
	 * @return boolean: TRUE, FALSE
	 * @throw PDOException,DataCheckException
	 */
	public function updateSyncStatus($status){
		$sql = 'UPDATE `'.$this->tableName.'` SET sync_status=:sync_status';
		$preparedParams = array();

		if(!in_array($status, array(SyncStatus::QUEUE, SyncStatus::RECEIVE,
				SyncStatus::SYNCED, SyncStatus::ERROR, SyncStatus::RETRY))){
			throw new DataCheckException('SYNC_STATUS未被允许的同步状态类型::'.$status);
		} else {
			$preparedParams[':sync_status'] = $status;
		}

		$sql .= ' WHERE '.$this->idclickObjectIdField.'=:'.$this->idclickObjectIdField;
		$preparedParams[':'.$this->idclickObjectIdField] = $object->getId();

		/**
		if($object instanceof AdwordsBase){
			$sql .= ' WHERE '.$this->adwordsObjectIdField.'=:'.$this->adwordsObjectIdField;
			$preparedParams[':'.$this->adwordsObjectIdField] = $object->getId();
		}*/

		$statement = $this->dbh->prepare($sql);
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
	public function getAdapteInfo(Base $object){
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
	public function getAdaptedId(Base $object){
		if(!$object instanceof IdclickBase){
			throw new DataCheckException('尚未支持的objectType类型，返回FALSE。object::'
														.get_class($object).' METHOD::'.__METHOD__);
		}

		$row = $this->getAdapteInfo($object);
		if(!empty($row)){
			switch($row[$this->fieldSyncStatus]){
				case SyncStatus::SYNCED:
					if(empty($row[$this->adwordsObjectIdField])) {
						throw new SyncStatusException('已同步状态但是没有ADWORDS
								对应信息IdclickId为'.$object->getId().' 对象：'.get_class($object));
					}
					return $row[$this->adwordsObjectIdField];
					break;
				case SyncStatus::QUEUE:
					if(empty($row[$this->adwordsObjectIdField])){
						//如果获取的ID为空，且状态为QUEUE，则发送一条更新本数据表对应ADWORDS_ID的消息
					}
					return TRUE;
					break;
				//case SyncStatus::RECEIVE:
				//case SyncStatus::ERROR:
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
	 * Generate process result.
	 */
	protected function generateResult(){
		if($this->result['status'] == -1){
			if(ENVIRONMENT == 'development'){
				Log::write("[ERROR]数据验证失败，返回结果：\n"
								. print_r($this->result, TRUE), __METHOD__);
			}
			if(RESULT_FORMAT == 'JSON'){
				return json_encode($this->result);
			} else {
				return $this->result;
			}
		}

		if(FALSE){
			if($this->processed == $this->result['success']){
				$this->result['status'] = 1;
				$this->result['description'] = self::DESC_DATA_PROCESS_SUCCESS
										. "，共有{$this->result['success']}条。";
			} else {
				$this->result['status'] = 0;
				$this->result['description'] = self::DESC_DATA_PROCESS_WARNING
					. "，成功{$this->result['success']}条，失败{$this->result['failure']}条。";
			}
		}

		$this->result['status'] = 1;
		if(ENVIRONMENT == 'development'){
			Log::write("[NOTICE]执行完成，返回结果：\n"
							. print_r($this->result, TRUE), __METHOD__);
		}
		if(RESULT_FORMAT == 'JSON'){
			return json_encode($this->result);
		} else {
			return $this->result;
		}
	}

	/**
	 * 构建消息并推送至消息队列
	 */
	protected function createMessageAndPut(array $data, $action){
		try{
			$message = new Message();
			$message->setModule($this->moduleName);
			$message->setAction($action);
			$message->setInformation($data);

			$messageHandler = new MessageHandler();
		 	$messageHandler->put($message, array($this, 'updateSyncStatus');
			return TRUE;
		} catch(MessageException $e){
			Log::write($e, __METHOD__);
			return FALSE;
		}
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
	}
}
