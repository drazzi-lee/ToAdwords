<?php

namespace ToAdwords\Model;

use ToAdwords\Model\BaseModel;

/**
 * CustomerModel 
 */
class CustomerModel extends BaseModel{
	protected static $tableName = 'customer';
	protected static $moduleName = 'Customer';

	public static $idclickObjectIdField = 'idclick_uid';
	public static $adwordsObjectIdField = 'customer_id';

	/**
	 * Check the given customerId is valid or not.
	 *
	 * @param integer $customerId:
	 * @return boolean.
	 * @todo check the sync_status is ok.
	 */
	public function isValidAdwordsId($customerId){
		if((int)$customerId < 1000){
			return FALSE;
		} else {
			return TRUE;
		}
	}
}
