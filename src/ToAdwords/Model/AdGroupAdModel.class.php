<?php

namespace ToAdwords\Model;

use ToAdwords\Model\BaseModel;

class AdGroupAdModel extends BaseModel{
	protected $tableName = 'adgroupad';
	protected $moduleName = 'AdGroupAd';
	
	protected $adwordsObjectIdField = 'ad_id';
	protected $idclickObjectIdField = 'idclick_adid';
	
	protected $dataCheckFilter = array(
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
