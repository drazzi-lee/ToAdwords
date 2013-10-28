<?php

namespace ToAdwords;

use ToAdwords\AdwordsAdapter;

/**
 * 广告组
 */
class AdGroupAdapter extends AdwordsAdapter{
	protected static $moduleName        = 'AdGroup';
	protected static $currentModelName  = 'ToAdwords\Model\AdGroupModel';
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
