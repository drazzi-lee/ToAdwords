<?php

namespace ToAdwords\Model;

use ToAdwords\Model\BaseModel;

/**
 * AdGroup Model 
 */
class AdGroupModel extends BaseModel{
	protected static $tableName = 'adgroup';
	protected static $moduleName = 'AdGroup';
	
	protected static $adwordsObjectIdField = 'adgroup_id';
	protected static $idclickObjectIdField = 'idclick_groupid';
	
	protected static $dataCheckFilter = array(
				'CREATE'	=> array(
					'requiredFields'	=> array(
						'idclick_groupid','idclick_planid','adgroup_name','keywords',
						'adgroup_status',
					),
					'prohibitedFields'	=> array('sync_status', 'adgroup_id', 'campaign_id'),
				),
				'UPDATE'	=> array(
					'requiredFields'	=> array('idclick_groupid','idclick_planid'),
					'prohibitedFields'	=> array('sync_status', 'adgroup_id', 'campaign_id'),
				),
				'DELETE'	=> array(
					'requiredFields'	=> array('idclick_groupid','idclick_planid'),
					'prohibitedFields'	=> array('sync_status', 'adgroup_id', 'campaign_id'),
				),
			);
}
