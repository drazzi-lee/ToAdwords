<?php

namespace ToAdwords;

require_once 'Adapter.interface.php';

use ToAdwords\Adapter;
use \PDO;
use \PDOException;
use \Exception;
use ToAdwords\Util\Log;

abstract class AdwordsAdapter implements Adapter{
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
	
	/**
	 * 数据同步状态定义 
	 */
	const SYNC_STATUS_RECEIVE = 'RECEIVE';
	const SYNC_STATUS_QUEUE = 'QUEUE';
	const SYNC_STATUS_SYNCED = 'SYNCED';
	const SYNC_STATUS_ERROR = 'ERROR';	
	
	/**
	 * 结果描述文字定义
	 */
	const DESC_DATA_CHECK_FAILURE = '提供数据不完整，请检查数据或设置IS_CHECK_DATA为FALSE';
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
	 * This method returns a one-dimensional array.
	 *
	 * @param string $fields: $fields = 'id,name,gender';
	 * @param string $conditions: $conditions = 'id=1234 AND name="Bob"';
	 * @return array $row
	 */
	protected function getOne($fields='*', $conditions){
		$result = $this->getRows($fields, $conditions, 1);
		return $result[0];
	}
	
	/**
	 * Get rows from current table.
	 * 
	 * This method returns a two-dimensional array.
	 *
	 * @param string $fields: $fields = 'id,name,gender';
	 * @param string $conditions: $conditions = 'id=1234 AND name="Bob"';
	 * @return array $rows
	 */
	protected function getRows($fields='*', $conditions, $limit = '1000'){
		$sql = 'SELECT '.$fields.' FROM `'.$this->tableName.'`';	
		$preparedParams = array();
		
		if(!empty($conditions)){
			$preparedConditions = $this->prepareConditions($conditions);
			$sql .= ' WHERE '.$preparedConditions['preparedWhere'];
			$preparedParams = $preparedConditions['preparedParams'];
		}
		
		if(!is_numeric($limit)){
			$limit = (int)$limit;
			if(ENVIRONMENT == 'development')
				trigger_error('limit只能为数字，已自动转换', E_USER_NOTICE);
		}
		$sql .= ' LIMIT '.$limit;
		
		$statement = $this->dbh->prepare($sql);
		$statement->execute($preparedParams);
		return $statement->fetchAll(PDO::FETCH_ASSOC);	
	}
	
	/**
	 * Prepare conditions to sql-parsed condition and params.
	 *
	 * @param string $conditions: $conditions = 'id=1234 AND name="Bob"';
	 * @return array $preparedConditions: array( 
	 *					'preparedWhere' 	=> 'WHERE id=:id AND name=:name',
	 *					'preparedParams'	=> array(':id' => '1234', ':name' => 'Bob'),
	 *				);
	 */
	protected function prepareConditions($conditions){
		if(empty($conditions)){
			return NULL;
		}
		$preparedWhere = preg_replace('/(\w+)(>|=|<)(\S+)/', '$1$2:$1', $conditions);
		$conditionsArray = preg_grep('/(\w+)(>|=|<)(\S+)/', preg_split('/(\s+)/', $conditions));
		$preparedParams = array();
		foreach($conditionsArray as $condition){			
			$conditionSplited = explode('=', $condition);
			$preparedParams[':'.$conditionSplited[0]] = $conditionSplited[1];
		}
		if(0 == count($preparedParams)){
			if('ENVIRONMENT' == 'development')
				trigger_error('解析查询条件失败，请检查是否符合语法', E_USER_WARNING);
			return NULL;
		}
		return array('preparedWhere' => $preparedWhere, 'preparedParams' => $preparedParams);
	}
	
	
	/**
	 * 更新ObjectId对应的数据表同步状态
	 *
	 * @param string $status: SYNC_STATUS_QUEUE等
	 * @param string $objectId: ID，可能为idclickId,adwords_customerId
	 * @param string $objectType: type, 可能为'IDCLICK','ADWORDS'
	 * @return boolean: TRUE, FALSE
	 */
	public function updateSyncStatus($status, $objectId, $objectType){
		try{
			$sql = 'UPDATE `'.$this->tableName.'` SET sync_status=:sync_status';
			$preparedParams = array();
			
			if(!in_array($status, array(self::SYNC_STATUS_QUEUE, self::SYNC_STATUS_RECEIVE,
					self::SYNC_STATUS_SYNCED, self::SYNC_STATUS_ERROR))){
				throw new Exception('SYNC_STATUS未被允许的同步状态类型::'.$status);
			} else {
				$preparedParams[':sync_status'] = $status;
			}			
			
			if(!in_array($objectType, array('IDCLICK','ADWORDS'))){
				throw new Exception('ObjectType未被允许的ID类型::'.$objectType);
			} else {
				switch($objectType){
					case 'IDCLICK':
						$sql .= ' WHERE '.$this->fieldIdclickObjectId.'=:'.$this->fieldIdclickObjectId;
						$preparedParams[':'.$this->fieldIdclickObjectId] = $objectId;
						break;
					case 'ADWORDS':
						$sql .= ' WHERE '.$this->fieldAdwordsObjectId.'=:'.$this->fieldAdwordsObjectId;
						$preparedParams[':'.$this->fieldAdwordsObjectId] = $objectId;
						break;
				}
			}
			
			$statement = $this->dbh->prepare($sql);
			return $statement->execute($preparedParams);
		} catch (PDOException $e){
			if(ENVIRONMENT == 'development'){
				trigger_error('数据库错误，更新'.$this->tableName.'表sync_status失败，objectId为'.$objectId.' Type:'.$objectType.'  ####'.$e->getMessage(), E_USER_ERROR);
			} else {
				Log::write('更新'.$this->tableName.'表sync_status失败，objectId为'.$objectId.' Type:'.$objectType.'  ####'.$e->getMessage(), __METHOD__);				
			}
			return FALSE;
		} catch (Exception $e){
			if(ENVIRONMENT == 'development'){
				trigger_error('更新'.$this->tableName.'表sync_status失败::'.$e->getMessage(), E_USER_ERROR);
			} else {
				Log::write('更新'.$this->tableName.'表sync_status失败::'.$e->getMessage(), __METHOD__);				
			}
			return FALSE;
		}
	}
	
	/**
	 * Check whether the data comlete.
	 * 
	 * @param array $data
	 * @param string $process
	 * @return boolean
	 */
	protected function _checkData(array $data, $process){
		if(empty($data) || empty($process)){
			return FALSE;
		}
		switch($process){
			case 'CREATE':
				if(empty($data['idclick_planid']) || empty($data['idclick_uid'])
						|| empty($data['campaign_name'])){
					return FALSE;
				}
				break;
			case 'UPDATE':
				if(empty($data['idclick_planid']) || empty($data['idclick_uid'])){
					return FALSE;
				}
				break;
			case 'DELETE':
				if(empty($data['idclick_planid'])){
					return FALSE;
				}
				break;
			default:
				return FALSE;
		}
		
		//...check data
		return TRUE;	
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
	protected function _arrayToString(array $array){
		return implode(',', $array);
	}
	
	/**
	 * 整合执行结果
	 */
	protected function _generateResult(){
		if($this->processed == $this->result['success']){
			$this->result['status'] = 1;
			$this->result['description'] = self::DESC_DATA_PROCESS_SUCCESS . "，共有{$this->result['success']}条。";
		} else {
			$this->result['status'] = 0;
			$this->result['description'] = self::DESC_DATA_PROCESS_WARNING . "，成功{$this->result['success']}条，失败{$this->result['failure']}条。";
		}
		return $this->result;
	}
	
	/**
	 * Put message in queue.
	 * 
	 * @param string $action: self::ACTION_CREATE ACTION_UPDATE ACTION_DELETE
	 * @param array $data
	 */
	protected function _queuePut($action, array $data) {
	
		// put data to queue.
		$message_combine = array(
					'module' 	=> 'CAMPAIGN',
					'action' 	=> $action,
					'data' 		=> $data,
				);
		$message = json_encode($message_combine);
		include_once 'httpsqs_client.php'; 
		$httpsqs = new httpsqs('192.168.6.14', '1218', 'mypass123', 'utf-8');
		return $httpsqs->put('adwords', $message);
	}
	
	/**
	 * 转换Idclick对象为Adwords对象	 *
	 */
	public function getAdapter(IdclickObject $idclickObject){
		//$message = 
	
	}
}
