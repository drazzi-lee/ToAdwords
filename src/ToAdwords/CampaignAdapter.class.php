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
	public static $currentModelName     = 'ToAdwords\Model\CampaignModel';
	protected static $moduleName           = 'Campaign';
	protected static $currentManagerName   = 'ToAdwords\Adwords\CampaignManager';
	protected static $parentModelName      = 'ToAdwords\Model\CustomerModel';
	protected static $parentAdapterName    = 'ToAdwords\CustomerAdapter';
	protected static $dataCheckFilter      = array(
			'CREATE'    => array(
				'requiredFields'    => array(
					'idclick_planid','idclick_uid','campaign_name','languages',
					'areas','bidding_type','budget_amount','max_cpc','campaign_status'
					),
				'prohibitedFields'	=> array('sync_status','campaign_id'),
				),
			'UPDATE'	=> array(
				'requiredFields'	=> array('idclick_planid','idclick_uid'),
				'prohibitedFields'	=> array('sync_status','campaign_id'),
				),
			'DELETE'	=> array(
				'requiredFields'	=> array('idclick_planid','idclick_uid','campaign_status'),
				'prohibitedFields'	=> array('sync_status','campaign_id'),
				),
			);
}
