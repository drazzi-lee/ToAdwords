<?php

namespace ToAdwords;

use ToAdwords\AdwordsAdapter;

/**
 * 广告系列
 */
class CampaignAdapter extends AdwordsAdapter{
	protected static $moduleName           = 'Campaign';
	protected static $currentModelName     = 'ToAdwords\Model\CampaignModel';
	protected static $parentModelName      = 'ToAdwords\Model\CustomerModel';
	protected static $parentAdapterName    = 'ToAdwords\CustomerAdapter';
	protected static $dataCheckFilter      = array(
			'CREATE'    => array(
				'requiredFields'    => array(
					'idclick_planid','idclick_uid','campaign_name','languages',
					'areas','bidding_type','budget_amount','max_cpc','campaign_status'
					),
				'prohibitedFields'	=> array('sync_status','campaign_id','customer_id'),
				),
			'UPDATE'	=> array(
				'requiredFields'	=> array('idclick_planid','idclick_uid'),
				'prohibitedFields'	=> array('sync_status','campaign_id','customer_id'),
				),
			'DELETE'	=> array(
				'requiredFields'	=> array('idclick_planid','idclick_uid'),
				'prohibitedFields'	=> array('sync_status','campaign_id','customer_id'),
				),
			);
	
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
		if(self::IS_CHECK_DATA){
			$this->checkData($data, Operation::UPDATE);
		}

		$campaignRow = $this->getOne('idclick_planid,idclick_uid', 
				'idclick_planid='.$data['idclick_planid']);
		if(empty($campaignRow)){
			throw new DataCheckException('广告计划未找到，idclick_planid为'
					.$data['idclick_planid']);
		} else if($campaignRow['idclick_uid'] != $data['idclick_uid']){
			throw new DataCheckException('找到广告计划ID #' . $data['idclick_planid']
					. '，但idclick_uid不符，提供的idclick_uid #' . $data['idclick_uid']
					. '  记录中的idclick_uid #' . $campaignRow['idclick_uid']);
		}			

		$data['last_action'] = isset($data['last_action']) ? $data['last_action'] : Operation::UPDATE;
		$conditions = 'idclick_planid='.$data['idclick_planid'];
		$conditionsArray = array(
				'idclick_planid'	=> $data['idclick_planid'],
				);
		$newData = array_diff_key($data, $conditionsArray);

		if(isset($data['bidding_type'])){
			switch($data['bidding_type']){
				case '0': $data['bidding_type'] = 'MANUAL_CPC'; break;
				case '1': $data['bidding_type'] = 'BUDGET_OPTIMIZER'; break;
				default: throw new DataCheckException('未知的bidding_type ##'.$data['bidding_type']);
			}
		}

		$this->dbh->beginTransaction();
		$adPlan = new AdPlan($data['idclick_planid']);
		if($this->updateOne($conditions, $newData) && $this->createMessageAndPut($data, $data['last_action'])
				&& $this->updateSyncStatus(SyncStatus::QUEUE, $adPlan)){
			$this->dbh->commit();
			$this->processed++;
			$this->result['success']++;
			$this->result['description'] = '广告计划操作成功';
			return $this->generateResult();
		} else {
			throw new Exception('顺序操作数据表、发送消息、更新同步状态为QUEUE出错。');
		}			
	}
	
	/**
	 * 删除广告计划
	 *
	 * @param array $data: 要删除的数据，数据结构为
	 * 		$data = array(
	 * 			'idclick_planid'	=> 12345, 
	 * 			'idclick_uid'		=> 441,
	 * 		);
	 * @return array $result
	 */
	public function delete(array $data){
		if(ENVIRONMENT == 'development'){
			Log::write("从AMC接口收到数据=========================\n"
					. print_r($data, TRUE), __METHOD__);
		}
		
		try{
			if(self::IS_CHECK_DATA){
				$this->checkData($data, Operation::DELETE);
			}
		} catch (DataCheckException $e){
			$this->result['status'] = -1;
			$this->result['description'] = '数据验证未通过：'.$e->getMessage();
			return $this->generateResult();
		}
		
		$infoForRemove = array();
		$infoForRemove['last_action'] = Operation::DELETE;
		$infoForRemove['campaign_status'] = 'DELETE';
		$infoForRemove[$this->idclickObjectIdField] = $data[$this->idclickObjectIdField];

		return $this->update($infoForRemove);
	}
}
