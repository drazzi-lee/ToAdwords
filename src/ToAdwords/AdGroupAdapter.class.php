<?php

namespace ToAdwords;

use ToAdwords\AdwordsAdapter;

/**
 * 广告组
 */
class AdGroupAdapter extends AdwordsAdapter{
	private $tableName = 'adgroup';
	
	private $fieldAdwordsObjectId = 'adgroup_id';
	private $fieldIdclickObjectId = 'idclick_groupid';
	
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
	public function create($data){
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
		return $this->_generateResult();	
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
	public function update($data){
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
		return $this->_generateResult();
	}
	
	public function delete($data){
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
		return $this->_generateResult();
	}
}