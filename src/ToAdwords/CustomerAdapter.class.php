<?php

namespace ToAdwords;

use ToAdwords\AdwordsAdapter;
use ToAdwords\Util\Log;
use ToAdwords\Util\Message;
use ToAdwords\Object\Idclick\Member;
use \PDO;
use \PDOException;
use \AdWordsUser;
use \Exception;


/**
 * 用户
 */
class CustomerAdapter extends AdwordsAdapter{
	protected $tableName = 'customer';
	protected $moduleName = 'Customer';
	
	protected $adwordsObjectIdField = 'adwords_customerid';
	protected $idclickObjectIdField = 'idclick_uid';
	
	/**
	 * 插入新用户记录
	 *
	 * 使用事务插入新用户，并推送进消息队列，如果出错即进行回滚。
	 *
	 * @param string $idclickUid: Idclick用户ID
	 * @return boolean: TRUE, FALSE
	 */
	protected function create($idclickUid){
		$sql = 'INSERT INTO `'.$this->tableName.'` ('.$this->idclickObjectIdField.',last_action)
						VALUES (:'.$this->idclickObjectIdField.', :last_action)';
		try{
			$this->dbh->beginTransaction();
			$member = new Member($idclickUid);
			if($this->insertOne(array('idclick_uid' => $idclickUid)) 
					&& $this->_createMessageAndPut($idclickUid)
					&& $this->updateSyncStatus(self::SYNC_STATUS_QUEUE, $member)){
				$this->dbh->commit();
				return TRUE;
			} else {
				$this->dbh->rollBack();
				return FALSE;
			}
		} catch (PDOException $e){
			$this->dbh->rollBack();
			if(ENVIRONMENT == 'development'){
				echo '在Customer表新插入一行失败，事务已回滚，idclick_uid为'.$idclickUid
									.' ==》'.$e->getMessage();
			} else {
				Log::write('在Customer表新插入一行失败，事务已回滚，idclick_uid为'.$idclickUid
									.' ==》'.$e->getMessage(), __METHOD__);					
			}
			return FALSE;
		} catch (Exception $e){
			$this->dbh->rollBack();
			if(ENVIRONMENT == 'development'){
				echo $e->getMessage();
			} else {
				Log::write($e->getMessage(), __METHOD__);					
			}
			return FALSE;
		}
	}
	
	/**
	 * 构建消息并推送至消息队列
	 *
	 *
	 */
	private function _createMessageAndPut($idclickUid){
		$information = array('idclick_uid' => $idclickUid);
		try{
			$message = new Message($this->moduleName, self::ACTION_CREATE, $information);
			if($message->put()){
				return TRUE;
			} else {
				throw new Exception('新消息构建完毕，但插入失败，消息内容为：'.$message);
			}
		} catch (Exception $e){
			if(ENVIRONMENT == 'development'){
				trigger_error('在Customer表新插入一行失败，事务已回滚，idclick_uid为'.$idclickUid.'  ####'.$e->getMessage(), E_USER_ERROR);
			} else {
				Log::write('创建新消息失败，idclick_uid为'.$idclickUid.'  ####'.$e->getMessage(), __METHOD__);				
			}
			return FALSE;
		}
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
