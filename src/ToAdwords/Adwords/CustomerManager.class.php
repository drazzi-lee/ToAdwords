<?php

/**
 * AdwordsBase.class.php
 *
 * Base Object to classes who operate Google Adwords Object by API.
 *
 * @author Li Pengfei
 * @email drazzi.lee@gmail.com
 * @version 1.0
 */
namespace ToAdwords\Adwords;

class CustomerManager extends AdwordsBase{
	private $managedCustomerService;
	private $budgetService;
	
	public function __construct(){
		parent::__construct();
		$this->user->SetClientCustomerId($customerId);
		$this->managedCustomerService = $this->getService('ManagedCustomerService');
		//$this->budgetService = $this->getService('BudgetService');
	}
	
	public function create(array $data=null){
		if(!isset($data['idclick_uid'])){
			throw new \Exception('idclick_uid is required.');
		}
		// Create customer.
		$customer = new \ManagedCustomer();
		$customer->name = 'Account #' . $data['idclick_uid'];
		$customer->currencyCode = 'CNY';
		$customer->dateTimeZone = 'Asia/Shanghai';

		// Create operation.
		$operation = new \ManagedCustomerOperation();
		$operation->operator = 'ADD';
		$operation->operand = $customer;

		$operations = array($operation);

		// Make the mutate request.
		$result = $managedCustomerService->mutate($operations);

		// Handle result.
		$customer = $result->value[0];
		return $customer->customerId;
	}	
}