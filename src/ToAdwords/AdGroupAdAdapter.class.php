<?php

/**
 * AdGroupAdAdapter.class.php
 *
 * Defines a class AdGroupAdAdapter, handle relation between idclick advertises and adwords adgroupads.
 *
 * @author Li Pengfei
 * @email drazzi.lee@gmail.com
 * @version 1.0
 */
namespace ToAdwords;

use ToAdwords\AdwordsAdapter;

class AdGroupAdAdapter extends AdwordsAdapter{
	protected static $moduleName        = 'AdGroupAd';
	public static $currentModelName  = 'ToAdwords\Model\AdGroupAdModel';
	protected static $currentManagerName   = 'ToAdwords\Adwords\AdGroupAdManager';
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
