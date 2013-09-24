<?php

namespace ToAdwords;

use ToAdwords\AdwordsAdapter;

/**
 * 广告组
 */
class AdGroupAdapter extends AdwordsAdapter{
	private $tableName = 'adgroup';
	
	private $fieldAdwordsObjectId = 'adgroup_id';
	private $fieldIdclickObjectId = 'idclick_groupid';
}