<?php

namespace ToAdwords;

require_once 'Adapter.interface.php';

use ToAdwords\Adapter;
use \PDO;
use \PDOException;

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
	
	const PDO_DSN = 'mysql:dbname=toadwords;host=127.0.0.1;charset=utf8';
	const PDO_USER = 'root';
	const PDO_PASS = 'qjklw';
	
	protected $dbh = null;
	
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
			$this->dbh = new PDO(self::PDO_DSN, self::PDO_USER, self::PDO_PASS);
			$this->dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			$this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e){
			trigger_error('数据库连接错误，实例化'.__CLASS__.'失败。', E_USER_ERROR);
		} catch (Exception $e){
			trigger_error('未知错误，实例化'.__CLASS__.'失败。', E_USER_ERROR);
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