<?php

namespace ToAdwords;

use ToAdwords\AdwordsAdapter;
use ToAdwords\Util\Log;
use ToAdwords\Message;
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
	
	protected $fieldAdwordsObjectId = 'adwords_customerid';
	protected $fieldIdclickObjectId = 'idclick_uid';
	
	/**
	 * 根据idclickUid获取Adwords帐户ID
	 *
	 * 获取过程会自动根据情况发送创建Adwords帐户消息，或返回对应帐户ID
	 *
	 * @param string $idclickUid: Idclick帐户ID
	 * @return $result: 帐户ID，FALSE，NULL
	 */
	public function getCustomerId($idclickUid){
		if(empty($idclickUid)){
			if(ENVIRONMENT == 'development')
				trigger_error('由于idclick_uid为空，查询CustomerId被积极拒绝，返回FALSE。', E_USER_WARNING);
			return FALSE;
		}

		$userRow = $this->getOne($this->fieldAdwordsObjectId.','.$this->fieldIdclickObjectId
			.',sync_status', $this->fieldIdclickObjectId.'='.$idclickUid);

		//如果已有该idclickUid的信息
		if(!empty($userRow)){
			switch($userRow[$this->fieldSyncStatus]){
				case self::SYNC_STATUS_SYNCED:
					if(empty($userRow[$this->fieldAdwordsObjectId])) {
						if(ENVIRONMENT == 'development'){
							trigger_error('发现一条异常信息，已同步但是没有Adwords帐户ID信息，Idclick_uid为'.$idclickUid, E_USER_ERROR);
						} else {
							Log::write('发现一条异常信息，已同步但是没有Adwords帐户ID信息，Idclick_uid为'.$idclickUid, __METHOD__);
						}
					}
					return $userRow[$this->fieldAdwordsObjectId];
					break;
				case self::SYNC_STATUS_QUEUE:
					return TRUE;
					break;
				case self::SYNC_STATUS_RECEIVE:
					if($this->_createMessageAndPut($userRow[$this->fieldIdclickObjectId])){
						$this->updateSyncStatus(self::SYNC_STATUS_QUEUE, $userRow[$this->fieldIdclickObjectId], 'IDCLICK');
						return TRUE;
					}
					break;
				case self::SYNC_STATUS_ERROR:
					if(ENVIRONMENT == 'development'){
						trigger_error('发现一条同步错误的状态，Idclick_uid为'.$idclickUid, E_USER_ERROR);
					} else {
						Log::write('发现一条同步错误的状态，Idclick_uid为'.$idclickUid, __METHOD__);
						trigger_error('A system error occurred.', E_USER_WARNING);						
					}
			}		
		} else { //如果还没有idclickUid的信息
			return $this->_insertOne($idclickUid);
		}
	}
	
	/**
	 * 插入新用户记录
	 *
	 * 使用事务插入新用户，并推送进消息队列，如果出错即进行回滚。
	 *
	 * @param string $idclickUid: Idclick用户ID
	 * @return boolean: TRUE, FALSE
	 */
	private function _insertOne($idclickUid){
		$sql = 'INSERT INTO `'.$this->tableName.'` ('.$this->fieldIdclickObjectId.') VALUES (:'.$this->fieldIdclickObjectId.')';
		try{
			$this->dbh->beginTransaction();			
			$statement = $this->dbh->prepare($sql);
			$statement->bindValue(':'.$this->fieldIdclickObjectId, $idclickUid, PDO::PARAM_STR);
			if($statement->execute() && $this->_createMessageAndPut($idclickUid)
					&& $this->updateSyncStatus(self::SYNC_STATUS_QUEUE, $idclickUid, 'IDCLICK')){
				$this->dbh->commit();
				return TRUE;
			} else {
				$this->dbh->rollBack();
				return FALSE;
			}
		} catch (PDOException $e){
			$this->dbh->rollBack();
			if(ENVIRONMENT == 'development'){
				trigger_error('在Customer表新插入一行失败，事务已回滚，idclick_uid为'.$idclickUid.' ==》'.$e->getMessage(), E_USER_ERROR);
			} else {
				Log::write('在Customer表新插入一行失败，事务已回滚，idclick_uid为'.$idclickUid.' ==》'.$e->getMessage(), __METHOD__);					
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
