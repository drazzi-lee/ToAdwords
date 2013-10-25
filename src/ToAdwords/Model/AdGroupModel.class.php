<?php

namespace ToAdwords\Model;

use ToAdwords\Model\BaseModel;

/**
 * AdGroup Model 
 */
class AdGroupModel extends BaseModel{
	protected static $tableName = 'adgroup';
	protected static $moduleName = 'AdGroup';
	
	public static $adwordsObjectIdField = 'adgroup_id';
	public static $idclickObjectIdField = 'idclick_groupid';
	public static $statusField = 'adgroup_status';
}
