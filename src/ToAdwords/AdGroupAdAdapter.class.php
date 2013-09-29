<?php

namespace ToAdwords;

use ToAdwords\AdwordsAdapter;
use ToAdwords\AdGroupAdapter;
use ToAdwords\Object\Idclick\AdGroup;
use ToAdwords\Exceptions\DependencyException;
use ToAdwords\Exceptions\SyncStatusException;

/**
 * 广告
 */
class AdGroupAdAdapter extends AdwordsAdapter{
	private $tableName = 'adgroupad';
	
	private $adwordsObjectIdField = 'ad_id';
	private $idclickObjectIdField = 'idclick_adid';
	
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
	public function create($data){
		if(self::IS_CHECK_DATA && !$this->_checkData($data, 'CREATE')){
			$this->result['status'] = -1;
			$this->result['description'] = self::DESC_DATA_CHECK_FAILURE;
			return $this->result;
		}
		
		try{
			$adGroupAdapter = new AdGroupAdapter();
			$adGroup = new AdGroup($data['idclick_groupid']);
			$data['last_action'] = self::ACTION_CREATE;
		
			$data['adgroup_id'] = $adGroupAdapter->getAdaptedId($adGroup);
		} catch (DependencyException $e){
			$this->result['status'] = -1;
			$this->result['description'] = $e->getMessage();
			return $this->_generateResult();
		} catch (SyncStatusException $e){
			echo $e->getMessage();exit;
		}
		/* if($this->add($data)){
			$this->processed++;
			$this->result['success']++;
			//$this->_queuePut($data);
		} else {
			$this->processed++;
			$this->result['failure']++;
		} */
		return $this->_generateResult();	
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
	public function update($data){
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
		return $this->_generateResult();
	}
	
	public function delete($data){
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
		return $this->_generateResult();
	}
}