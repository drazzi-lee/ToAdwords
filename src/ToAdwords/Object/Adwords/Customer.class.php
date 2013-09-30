<?php

namespace ToAdwords\Object\Adwords;
use ToAdwords\Object\Adwords\AdwordsBase;
use ToAdwords\Exceptions\DataCheckException;

class Customer extends AdwordsBase{
	/**
	 * @access public
	 * @var string
	 */
	public $name;

	/**
	 * @access public
	 * @var string
	 */
	public $companyName;

	/**
	* @access public
	* @var integer
	*/
	public $customerId;

	/**
	 * @access public
	 * @var boolean
	 */
	public $canManageClients;

	/**
	* @access public
	* @var string
	*/
	public $currencyCode;

	/**
	* @access public
	* @var string
	*/
	public $dateTimeZone;

	/**
	* @access public
	* @var boolean
	*/
	public $testAccount;
	
	private function _createCustomer($uid){
		try{
			$user = new AdWordsUser();
			$user->LogAll();
			return $this->_createAdAccount($user);
		} catch (Exception $e){
			Log::write('请求GOOGLE_ADWORDS创建CustomerId失败：'. $e->getMessage());
		}
	}

	private function _createAdAccount(AdWordsUser $user){
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

		return $customer->customerId;
	}
	

}