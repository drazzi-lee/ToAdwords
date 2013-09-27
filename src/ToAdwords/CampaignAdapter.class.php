<?php

namespace ToAdwords;

use ToAdwords\AdwordsAdapter;
use ToAdwords\CustomerAdapter;
use ToAdwords\Util\Log;

/**
 * 广告系列
 */
class CampaignAdapter extends AdwordsAdapter{
	protected $tableName = 'campaign';
	
	protected $fieldAdwordsObjectId = 'campaign_id';
	protected $fieldIdclickObjectId = 'idclick_planid';
	
	/**
	 * 添加广告计划
	 *
	 * @param array $data: 要添加的数据，数据结构为
	 * 		$data = array(
	 * 			'idclick_planid'	=> 12345,
	 * 			'idclick_uid'		=> 441,		//创建时必需
	 * 			'campaign_name'		=> 'campaign_name',
	 *			'areas'				=> '10031, 10032',
	 *			'languages'			=> '10031, 10032',
	 *			'bidding_type'		=> 1,
	 *			'budget_amount'		=> 200.00,
	 *			'delivery_method'	=> 'ACCELERATED',
	 *			'max_cpc'			=> 2.00,
	 *			'campaign_status'	=> 'ACTIVE',		//ACTIVE PAUSE DELETE	
	 * 		);
	 * @return array $result
	 */
	public function create(array $data){
		Log::write('test information', __method__);
		if(self::IS_CHECK_DATA && !$this->_checkData($data, self::ACTION_CREATE)){
			$this->result['status'] = -1;
			$this->result['description'] = self::DESC_DATA_CHECK_FAILURE;
			return $this->result;
		}
		
		$customer = new CustomerAdapter();
		$data['last_action'] = self::ACTION_CREATE;
		$data['customer_id'] = $customer->getAdaptedId($data['idclick_uid']);
		$data['sync_status'] = self::SYNC_STATUS_RECEIVE;
		dump($this);exit;
		
		if($this->add($data)){
			$this->processed++;
			$this->result['success']++;
			if($this->_queuePut(self::ACTION_CREATE, $data)){
				$conditions = array (
					'idclick_planid' => $data ['idclick_planid'] 
				);
				$status = array (
					'sync_status' => self::SYNC_STATUS_QUEUE 
				);
				$this->where($conditions)->save($status);
			}
		} else {
			$this->processed++;
			$this->result['failure']++;
		}		
		
		/**
		 * 同步Google Adwords
		 * 
		 * 仅限DEMO版需要，正式版需要从消息队列中获取数据。
		 */
		if(false){
			try{			
				$user = new AdwordsUser();
				$user->SetClientCustomerId($data['customer_id']);
				$user->LogAll();
				
				//暂不同步。
				$campaignId = $this->_createAdwordsCampaign($user, $data);
				if(!empty($campaignId)){
					$campaignAdwordsData = array(
								'campaign_id'	=> $campaignId,
								'sync_status'	=> self::SYNC_STATUS_SYNCED,
							);
					$this->where($conditions)->save($campaignAdwordsData);
				}		
			} catch (Exception $e) {
				Log::write('请求Google Adwords Api出错：' . $e->getMessage());
			}
		}
		
		return $this->_generateResult();
	}
	
	/**
	 * 更新广告计划
	 *
	 * @param array $data: 要添加的数据，数据结构为
	 * 		$data = array(
	 * 			'idclick_planid'	=> 12345,
	 * 			'idclick_uid'		=> 441,
	 * 			'campaign_name'		=> 'campaign_name',
	 *			'areas'				=> '10031, 10032',
	 *			'languages'			=> '10031, 10032',
	 *			'bidding_type'		=> 1,
	 *			'budget_amount'		=> 200.00,
	 *			'delivery_method'	=> 'ACCELERATED',
	 *			'max_cpc'			=> 2.00,
	 * 		);
	 * @return array $result
	 */
	public function update(array $data){
		if(self::IS_CHECK_DATA && !$this->_checkData($data, 'UPDATE')){
			$this->result['status'] = -1;
			$this->result['description'] = self::DESC_DATA_CHECK_FAILURE;
			return $this->result;
		}
		
		$conditions = array(
					'idclick_planid'	=> $data['idclick_planid'],
					'idclick_uid'		=> $data['idclick_uid'],
				);
		$newStatus = array_diff_key($data, $conditions);
		$newStatus['last_action'] = self::ACTION_UPDATE;
		//$newStatus['sync_status'] = self::SYNC_STATUS_RECEIVE;
		if(FALSE !== $this->where($conditions)->save($newStatus)){
			$this->processed++;
			$this->result['success']++;
		} else {
			$this->processed++;
			$this->result['failure']++;
		}
		return $this->_generateResult();		
	}
	
	/**
	 * 删除广告计划
	 * @param array $data
	 */
	public function delete(array $data){
		if(self::IS_CHECK_DATA && !$this->_checkData($data, 'DELETE')){
			$this->result['status'] = -1;
			$this->result['description'] = self::DESC_DATA_CHECK_FAILURE;
			return $this->result;
		}
		
		$conditions = array(
					'idclick_planid'	=> $data['idclick_planid'],
				);
		$newStatus = array(
					'campaign_status'	=> 'DELETE',
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
	
	private function _insertOne($idclickPlanId, $data){
	
	}
}
