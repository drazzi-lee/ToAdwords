<?php

namespace ToAdwords\Model;

use ToAdwords\Model\BaseModel;

/**
 * AdGroup Ad Model
 */
class AdGroupAdModel extends BaseModel{
	protected static $tableName = 'adgroupad';
	protected static $moduleName = 'AdGroupAd';

	public static $adwordsObjectIdField = 'ad_id';
	public static $idclickObjectIdField = 'idclick_adid';
	public static $statusField = 'ad_status';
}
