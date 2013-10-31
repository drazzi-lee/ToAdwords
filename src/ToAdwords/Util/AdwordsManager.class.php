<?php

namespace ToAdwords\Util;

require_once('init.php');

use \AdWordsUser;
use \ManagedCustomer;
use \ManagedCustomerOperation;
use \Exception;

class AdwordsManager{
	private $user;

	public function __construct(){
		$this->user = new AdWordsUser();
		$this->user->LogAll();
	}


	public function createAccount(){
		try{
			// Get the service, which loads the required classes.
			$managedCustomerService =
				$this->user->GetService('ManagedCustomerService', ADWORDS_VERSION);

			// Create customer.
			$customer = new ManagedCustomer();
			$customer->name = 'Account #' . uniqid();
			$customer->currencyCode = 'CNY';
			$customer->dateTimeZone = 'Asia/Shanghai';

			// Create operation.
			$operation = new ManagedCustomerOperation();
			$operation->operator = 'ADD';
			$operation->operand = $customer;

			$operations = array($operation);

			// Make the mutate request.
			$result = $managedCustomerService->mutate($operations);

			// Display result.
			$customer = $result->value[0];
			printf("Account with customer ID '%s' was created.\n",
					$customer->customerId);
		} catch(Exception $e){
			printf("An error has occurred: %s\n", $e->getMessage());
		}

	}

	public function createCampaign(){


	}

	public function createAdGroup(){

	}

	public function createAdGroupAd(){

	}
}
