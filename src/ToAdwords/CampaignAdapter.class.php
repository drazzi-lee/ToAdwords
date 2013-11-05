<?php

/**
 * CampaignAdapter.class.php
 *
 * Defines a class CampaignAdapter, handle relation between idclick ad-plans and adwords campaigns.
 *
 * @author Li Pengfei
 * @email drazzi.lee@gmail.com
 * @version 1.0
 */
namespace ToAdwords;

use ToAdwords\AdwordsAdapter;
use \Exception;

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
				'requiredFields'	=> array('idclick_planid','idclick_uid','campaign_status'),
				'prohibitedFields'	=> array('sync_status','campaign_id','customer_id'),
				),
			);

	/**
	 * Create Adwords Campaign 
	 *
	 * Call AdwordsManager to create a campaign on specify customer's account.
	 *
	 * @param array $data: 
	 * @return boolean: TRUE on success, FALSE on failure.
	 */
	public function createAdwordsObject(array $data){
		try{
			if(!isset($data['idclick_uid']){
				throw new Exception('idclick uid is required.');	
			}
			
			$customerModel = new self::$parentModelName();
			$customerInfo = $customerModel->getAdapteInfo($data['idclick_uid']);
			if(!$customerInfo){
				throw new Exception('idclick uid#'.$data['idclick_uid'] . ' not found.');
			} else if($customerInfo['sync_status'] !== SyncStatus::SYNCED){
				throw new Exception('customer has not synced. idclick_uid #'.$data['idclick_uid']);
			} else {
				$data['customer_id'] = $customerInfo['customer_id'];	
			}

			$adwordsManager = new AdwordsManager();
			$campaignId = $adwordsManager->createCampaign($data['customer_id'], $data);
			Log::write("[notice] Account with customer_id #{$customerId} was created.\n", __METHOD__);
			$this->update
			return $customerId;
		} catch(Exception $e){
			Log::write("[warning] An error has occurred: {$e->getMessage()}\n"
			return FALSE;
		}
	}

	public function updateAdwordsObject(){
		Log::write("Method does not supported.\n", __METHOD__);
		return FALSE;
	}

	public function deleteAdwordsObject(){
		Log::write("Method does not supported.\n", __METHOD__);
		return FALSE;
	}
}
