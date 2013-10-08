<?php
namespace ToAdwords\Object\Adwords;

require_once('init.php');

use ToAdwords\CustomerAdapter;
use ToAdwords\Object\Adwords\AdwordsBase;
use ToAdwords\Object\Idclick\Member;

use \AdWordsUser;
use \ManagedCustomer;
use \ManagedCustomerOperation;

class Customer extends AdwordsBase{

	public function __construct(){
	
	
	}
	
	public function create($data){
		try{
			$user = new AdWordsUser();
			$user->LogAll();
			// Get the service, which loads the required classes.
			$managedCustomerService =
				$user->GetService('ManagedCustomerService', ADWORDS_VERSION);

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
			//printf("Account with customer ID '%s' was created.\n",
			//	$customer->customerId);

			if(!empty($customer->customerId)){
				$customerAdapter = new CustomerAdapter();
				$member = new Member($data['idclick_uid']);
				$customerAdapter->updateSyncStatus(CustomerAdapter::SYNC_STATUS_SYNCED, $member);
			}
			
		} catch (Exception $e){
			Log::write('创建Customer失败：' . $e->getMessage(), __METHOD__);
		}
	}
	
	public function update($data){
	
	}
	
	public function delete($data){
	
	}
}