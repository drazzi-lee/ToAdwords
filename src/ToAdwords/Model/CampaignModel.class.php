<?php

namespace ToAdwords\Model;

use ToAdwords\Model\BaseModel;

class CampaignModel extends BaseModel{
	protected static $tableName = 'campaign';
	protected static $moduleName = 'Campaign';
	
	protected static $adwordsObjectIdField = 'campaign_id';
	protected static $idclickObjectIdField = 'idclick_planid';
	
	protected static $dataCheckFilter = array(
				'CREATE'	=> array(
					'requiredFields'	=> array(
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
					'requiredFields'	=> array('idclick_planid','idclick_uid'),
					'prohibitedFields'	=> array('sync_status','campaign_id','customer_id'),
				),
			);
	
}
