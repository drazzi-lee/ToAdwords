<?php

namespace ToAdwords;

use ToAdwords\AdwordsAdapter;

/**
 * 广告
 */
class AdGroupAdAdapter extends AdwordsAdapter{
	protected static $moduleName        = 'AdGroupAd';
	protected static $currentModelName  = 'ToAdwords\Model\AdGroupAdModel';
	protected static $parentModelName   = 'ToAdwords\Model\AdGroupModel';
	protected static $parentAdapterName = 'ToAdwords\AdGroupAdapter';
	protected static $dataCheckFilter   = array(
			'CREATE'    => array(
				'requiredFields'    => array(
					'idclick_adid','idclick_groupid','ad_status'
					),
				'prohibitedFields'	=> array('sync_status', 'ad_id', 'adgroup_id'),
				),
			'UPDATE'	=> array(
				'requiredFields'	=> array('idclick_adid','idclick_groupid','ad_status'),
				'prohibitedFields'	=> array('sync_status', 'ad_id', 'adgroup_id'),
				),
			'DELETE'	=> array(
				'requiredFields'	=> array('idclick_adid','idclick_groupid','ad_status'),
				'prohibitedFields'	=> array('sync_status', 'ad_id', 'adgroup_id'),
				),
			);
}
