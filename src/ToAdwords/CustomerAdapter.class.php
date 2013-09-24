<?php

namespace ToAdwords;

use ToAdwords\AdwordsAdapter;

/**
 * 用户
 */
class CustomerAdapter extends AdwordsAdapter{
	private $tableName = 'customer';
	
	private $fieldAdwordsObjectId = 'adwords_customerid';
	private $fieldIdclickObjectId = 'idclick_uid';
	
	public function getCustomerId($uid){
		if(!$uid){
			return NULL;
		}
		
		return '5572928024';

		/*
		$condition = array(
			'idclick_uid' 	=> $uid,
		);
		$userRow = $this->where($condition)->find();
		if(!empty($userRow['adwords_customerid'])){
			return $userRow['adwords_customerid'];
		} else if(!empty($userRow)) {
			$adwordsCustomerId = $this->_createCustomer($uid);
			$data = array(
				'adwords_customerid'	=> $adwordsCustomerId,
				'last_action'			=> 'CREATE',
			);
			$result = $this->where($condition)->data($data)->save();
			if(!$result){
				Log::write('更新数据失败' . $this->getLastSql()
					. ' ## adwords_customerid: ' . $adwordsCustomerId);
			}
			return $adwordsCustomerId;
		} else {
			$adwordsCustomerId = $this->_createCustomer($uid);
			$data = array(
				'idclick_uid'			=> $uid,
				'adwords_customerid'	=> $adwordsCustomerId,
				'last_action'			=> 'CREATE',
			);
			$result = $this->add($data);
			if(!$result){
				Log::write('添加数据失败' . $this->getLastSql()
					. ' ## adwords_customerid: ' . $adwordsCustomerId);
			}
			return $adwordsCustomerId;
		}*/
	}
	
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