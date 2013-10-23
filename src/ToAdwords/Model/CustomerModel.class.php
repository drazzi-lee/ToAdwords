<?php

namespace ToAdwords\Model;

use ToAdwords\Model\BaseModel;
use ToAdwords\Exception\ModelExcetpion;

/**
 * CustomerModel 
 */
class CustomerModel extends BaseModel{

	protected static $tableName = 'customer';
	protected static $moduleName = 'Customer';

	protected static $adwordsObjectIdField = 'adwords_customerid';
	protected static $idclickObjectIdField = 'idclick_uid';

	protected static $dataCheckFilter = array(
				'CREATE'	=> array(
					'requiredFields'	=> array('idclick_uid'),
					'prohibitedFields'	=> array('sync_status','customer_id'),
				),
				'UPDATE'	=> array(
					'requiredFields'	=> array('idclick_uid'),
					'prohibitedFields'	=> array('sync_status','customer_id'),
				),
				'DELETE'	=> array(
					'requiredFields'	=> array('idclick_uid'),
					'prohibitedFields'	=> array('sync_status','customer_id'),
				),
			);
}
