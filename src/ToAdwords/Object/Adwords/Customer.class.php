<?php

namespace ToAdwords\Object\Adwords;

require_once('init.php');

use ToAdwords\CustomerAdapter;
use ToAdwords\Object\Adwords\AdwordsBase;
use ToAdwords\Object\Idclick\Member;

use \AdWordsUser;
use \ManagedCustomer;
use \ManagedCustomerOperation;
use \Exception;
use \PDOException;

class Customer extends AdwordsBase{
	
	private $idclickUid;
	private $adwordsCustomerId;
	private $lastAction;

	public function __construct(){
	
	}
	
	/**
	 * 创建Client Customer账号
	 *
	 * 根据消息创建Google Adwords Customer账号，并更新数据库中同步状态及AdwordsCustomerID.
	 *
	 * @param array $data: Message::information Array，来自消息队列
	 * @return boolean: TRUE, FALSE
	 * @throw Exception, PDOException.
	 */
	public function create(array $data){
		try{
			$user = new AdWordsUser();
			$user->LogAll();
			// Get the service, which loads the required classes.
			$managedCustomerService = $user->GetService('ManagedCustomerService', ADWORDS_VERSION);

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
				$dataInsert = array('adwords_customerid' => $customer->customerId);
				
				$this->dbh->beginTransaction();
				$customerAdapter->updateOne('idclick_uid='.$member->getId(), $dataInsert);
				$customerAdapter->updateSyncStatus(CustomerAdapter::SYNC_STATUS_SYNCED, $member);
				$this->dbh->commit();
				return TRUE;
				if(ENVIRONMENT == 'development'){
					Log::write('[NOTICE] 已成功创建GOOGLE CUSTOMER账户 #'
														. $customer->customerId, __METHOD__);
				}
			}			
		} catch (PDOException $e){
			if($this->dbh->inTransaction()){
				$this->dbh->rollBack();
			}
			Log::write('创建Customer成功，但是数据未插入。消息体：'
									. $message . ' 错误原因：' . $e->getMessage(), __METHOD__);
			return FALSE;
		} catch (Exception $e){
			if($this->dbh->inTransaction()){
				$this->dbh->rollBack();
			}
			Log::write('创建ClientCustomer失败：' . $e->getMessage(), __METHOD__);
			return FALSE;
		}
	}
	
	/**
	 * 链接已有的Google Adwords Customer账号
	 *
	 * 此方法暂不启用，可能用来后台调用。
	 */
	public function link(){
	
	}
}