<?php

namespace ToAdwords\Model;

use ToAdwords\BaseModel;

/**
 * CustomerModel 
 */
class CustomerModel extends BaseModel{

	protected $tableName = 'customer';

	protected $adwordsObjectIdField = 'adwords_customerid';
	protected $idclickObjectIdField = 'idclick_uid';

}
