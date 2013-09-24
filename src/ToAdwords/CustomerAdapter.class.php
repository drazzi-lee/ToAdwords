<?php

namespace ToAdwords;

use ToAdwords\AdwordsAdapter;

/**
 * 用户
 */
class CustomerAdapter extends Adapter{
	private $tableName = 'customer';
	
	private $fieldAdwordsObjectId = 'adwords_customerid';
	private $fieldIdclickObjectId = 'idclick_uid';
	
	public function __construct(){
		
	}

	public function getCustomerId(){
	
	}
}