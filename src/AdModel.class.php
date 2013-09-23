<?php
require_once 'init.php';

class AdModel extends Model{
	/**
	 * 是否检查数据完整性
	 */
	const IS_CHECK_DATA = FALSE;
	
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
	private $result = array(
				'success'	=> 0,
				'failure'	=> 0,
			);
	private $processed = 0;
	
	
	/**
	 * 添加广告
	 *
	 * @param array $data: 要添加的数据，数据结构为
	 * 		$data = array(
	 * 			'idclick_adid'		=> 12345,
	 * 			'idclick_uid'		=> 441,			
	 *			'idclick_groupid'	=> 123456,
	 *			'ad_headline'		=> 'headline',
	 *			'ad_description1'	=> 'description1',
	 *			'ad_description2'	=> 'description2',
	 *			'ad_url'			=> 'http://www.izptec.com/go.php',
	 *			'ad_displayurl'		=> 'http://www.izptec.com/',
	 * 		);
	 * @return array $result
	 */
	public function createAd($data){
		if(self::IS_CHECK_DATA && !$this->_checkData($data, 'CREATE')){
			$this->result['status'] = -1;
			$this->result['description'] = self::DESC_DATA_CHECK_FAILURE;
			return $this->result;
		}
		
		$data['last_action'] = self::ACTION_CREATE;
		if($this->add($data)){
			$this->processed++;
			$this->result['success']++;
			//$this->_queuePut($data);
		} else {
			$this->processed++;
			$this->result['failure']++;
		}
		$this->addup_result();
		return $this->result;	
	}
	
	/**
	 * 更新广告
	 *
	 * @param array $data: 要添加的数据，数据结构为
	 * 		$data = array(
	 * 			'idclick_adid'		=> 12345,
	 * 			'idclick_groupid'	=> 123456,
	 *			'ad_headline'		=> 'headline', //可选
	 *			'ad_description1'	=> 'description1', //可选
	 *			'ad_description2'	=> 'description2', //可选
	 *			'ad_url'			=> 'http://www.izptec.com/go.php', //可选
	 *			'ad_displayurl'		=> 'http://www.izptec.com/', //可选
	 * 		);
	 * @return array $result
	 */	
	public function updateAd($data){
		if(self::IS_CHECK_DATA && !$this->_checkData($data, 'UPDATE')){
			$this->result['status'] = -1;
			$this->result['description'] = self::DESC_DATA_CHECK_FAILURE;
			return $this->result;
		}
		
		$conditions = array(
					'idclick_groupid'	=> $data['idclick_groupid'],
					'idclick_adid'		=> $data['idclick_adid'],
				);
		$newStatus = array_diff_key($data, $conditions);
		$newStatus['last_action'] = self::ACTION_UPDATE;
		$newStatus['sync_status'] = self::SYNC_STATUS_RECEIVE;
		if(FALSE !== $this->where($conditions)->save($newStatus)){
			$this->processed++;
			$this->result['success']++;
		} else {
			$this->processed++;
			$this->result['failure']++;
		}
		
		$this->addup_result();
		return $this->result;
	}
	
	public function deleteAd($data){
		if(self::IS_CHECK_DATA && !$this->_checkData($data, 'DELETE')){
			$this->result['status'] = -1;
			$this->result['description'] = self::DESC_DATA_CHECK_FAILURE;
			return $this->result;
		}
		
		$conditions = array(
					'idclick_adid'	=> $data['idclick_adid'],
				);
		$newStatus = array(
					'adgroup_status'	=> 'DELETE',
					'last_action'		=> self::ACTION_DELETE,
					'sync_status'		=> self::SYNC_STATUS_RECEIVE,
				);
		if(FALSE !== $this->where($conditions)->save($newStatus)){
			$this->processed++;
			$this->result['success']++;
		} else {			
			$this->processed++;
			$this->result['failure']++;
		}
		
		$this->addup_result();
		return $this->result;
	}
	
	public function getAdgroupId(string $idclickGroupsId){
		
	}
	
	/**
	 * Put message in queue.
	 * 
	 * @param array $data
	 */
	private function _queuePut(array $data) {
		$conditions = array (
					'idclick_planid' => $data ['idclick_planid'] 
				);
		$status = array (
					'sync_status' => self::SYNC_STATUS_QUEUE 
				);
		// ... codes put data to queue.
		$this->where($conditions)->save($status);
	}
	
	/**
	 * Check whether the data comlete.
	 * 
	 * @param array $data
	 * @param string $process
	 * @return boolean
	 */
	private function _checkData(array $data, string $process){
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
	private function _arrayToString(array $array){
		return implode(',', $array);
	}
	
	/**
	 * 整合执行结果
	 */
	protected function addup_result(){
		if($this->processed == $this->result['success']){
			$this->result['status'] = 1;
			$this->result['description'] = self::DESC_DATA_PROCESS_SUCCESS . "，共有{$this->result['success']}条。";
		} else {
			$this->result['status'] = 0;
			$this->result['description'] = self::DESC_DATA_PROCESS_WARNING . "，成功{$this->result['success']}条，失败{$this->result['failure']}条。";
		}
	}	
	
}
