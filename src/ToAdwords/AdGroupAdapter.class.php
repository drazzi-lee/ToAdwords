<?php

/**
 * AdGroupAdapter.class.php
 *
 * Defines a class CustomerAdapter, handle relation between idclick adgroups and adwords AdGroups.
 *
 * @author Li Pengfei
 * @email drazzi.lee@gmail.com
 * @version 1.0
 */
namespace ToAdwords;

use ToAdwords\AdwordsAdapter;

class AdGroupAdapter extends AdwordsAdapter{
	protected static $moduleName        = 'AdGroup';
	public static $currentModelName  = 'ToAdwords\Model\AdGroupModel';
	protected static $currentManagerName   = 'ToAdwords\Adwords\AdGroupManager';
	protected static $parentModelName   = 'ToAdwords\Model\CampaignModel';
	protected static $parentAdapterName = 'ToAdwords\CampaignAdapter';
	protected static $dataCheckFilter 	= array(
			'CREATE'	=> array(
				'requiredFields'	=> array(
					'idclick_planid','idclick_groupid','adgroup_status'
					),
				'prohibitedFields'	=> array('sync_status', 'adgroup_id', 'campaign_id'),
				),
			'UPDATE'	=> array(
				'requiredFields'	=> array('idclick_planid','idclick_groupid'),
				'prohibitedFields'	=> array('sync_status', 'adgroup_id', 'campaign_id'),
				),
			'DELETE'	=> array(
				'requiredFields'	=> array('idclick_planid','idclick_groupid','adgroup_status'),
				'prohibitedFields'	=> array('sync_status', 'adgroup_id', 'campaign_id'),
				),
			);
}
