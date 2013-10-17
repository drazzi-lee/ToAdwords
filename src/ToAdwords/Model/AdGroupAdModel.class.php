<?php

namespace ToAdwords\Model;

use ToAdwords\Model\BaseModel;

/**
 * AdGroup Ad Model
 */
class AdGroupAdModel extends BaseModel{
	protected static $tableName = 'adgroupad';
	protected static $moduleName = 'AdGroupAd';
	
	protected static $adwordsObjectIdField = 'ad_id';
	protected static $idclickObjectIdField = 'idclick_adid';
	
	protected static $dataCheckFilter = array(
				'CREATE'	=> array(
					'requiredFields'	=> array(
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
