<?php

require_once('src/ToAdwords/bootstrap.inc.php');

use ToAdwords\AdGroupAdapter;

/**
 * 广告组数据模型 GroupModel
 *
 * 此模型为虚拟类，模型实例化时并不产生实际数据连接。
 */
class GroupModel{
	private AdGroupAdapter $adGroupAdapter;

	public function __construct(){
		$this->adGroupAdapter = new AdGroupAdapter();
	}
	
	/**
	 * 添加广告组
	 *
	 * @param array $data: 要添加的数据，数据结构为
	 * 		$data = array(
	 *			'idclick_groupid'	=> 123456,
	 * 			'idclick_planid'	=> 12345,
	 * 			'idclick_uid'		=> 441,			
	 *			'adgroup_name'		=> 'group_name',
	 *			'keywords'			=> array('keywords1', 'keywords2'),
	 *			'budget_amount'		=> 200.00,	
	 * 		);
	 * @return array $result
	 */
	public function createAdgroup($data){
		if(self::IS_CHECK_DATA && !$this->_checkData($data, 'CREATE')){
			$this->result['status'] = -1;
			$this->result['description'] = self::DESC_DATA_CHECK_FAILURE;
			return $this->result;
		}
		
		$conditions = array(
					'idclick_groupid' => $data['idclick_groupid'],
				);
		$data['last_action'] = self::ACTION_CREATE;
		$data['keywords'] = $this->_arrayToString($data['keywords']);
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
	 * 更新广告组
	 *
	 * @param array $data: 结构为：
	 *	  $data = array(
	 *	   		'idclick_groupid'	=> 123456,
	 *	 		'idclick_uid'		=> 441,			
	 *	   		'adgroup_name'		=> 'group_name2',
	 *	   		'keywords'			=> array('keywords3', 'keywords2', 'keywords1'),
	 *	   		'budget_amount'		=> 201.00,
	 *	   );
	 * @return array $result
	 */	
	public function updateAdgroup($data){
		if(self::IS_CHECK_DATA && !$this->_checkData($data, 'UPDATE')){
			$this->result['status'] = -1;
			$this->result['description'] = self::DESC_DATA_CHECK_FAILURE;
			return $this->result;
		}
		
		$conditions = array(
					'idclick_groupid'	=> $data['idclick_groupid'],
					'idclick_uid'		=> $data['idclick_uid'],
				);
		$newStatus = array_diff_key($data, $conditions);
		$newStatus['last_action'] = self::ACTION_UPDATE;
		$newStatus['sync_status'] = self::SYNC_STATUS_RECEIVE;
		if(isset($data['keywords'])){
			$newStatus['keywords'] = $this->_arrayToString($data['keywords']);
		}
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
	
	public function deleteAdgroup($data){
		if(self::IS_CHECK_DATA && !$this->_checkData($data, 'DELETE')){
			$this->result['status'] = -1;
			$this->result['description'] = self::DESC_DATA_CHECK_FAILURE;
			return $this->result;
		}
		
		$conditions = array(
					'idclick_groupid'	=> $data['idclick_groupid'],
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
