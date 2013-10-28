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
				'requiredFields'	=> array('idclick_planid','idclick_uid','campaign_status'),
				'prohibitedFields'	=> array('sync_status','campaign_id','customer_id'),
				),
			);
}
