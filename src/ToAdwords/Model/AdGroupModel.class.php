<?php

namespace ToAdwords\Model;

use ToAdwords\Model\BaseModel;

/**
 * 广告组
 */
class AdGroupModel extends BaseModel{
	protected $tableName = 'adgroup';
	protected $moduleName = 'AdGroup';
	
	protected $adwordsObjectIdField = 'adgroup_id';
	protected $idclickObjectIdField = 'idclick_groupid';
	
	protected $dataCheckFilter = array(
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
