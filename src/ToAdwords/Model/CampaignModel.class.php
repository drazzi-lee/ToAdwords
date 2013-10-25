<?php

namespace ToAdwords\Model;

use ToAdwords\Model\BaseModel;

class CampaignModel extends BaseModel{
	protected static $tableName = 'campaign';
	protected static $moduleName = 'Campaign';
	
	public static $adwordsObjectIdField = 'campaign_id';
	public static $idclickObjectIdField = 'idclick_planid';
	public static $statusField = 'campaign_status';
}
