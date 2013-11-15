<?php

/**
 * CustomerAdapter.class.php
 *
 * Defines a class CustomerAdapter, handle relation between idclick members and adwords customers.
 *
 * @author Li Pengfei
 * @email drazzi.lee@gmail.com
 * @version 1.0
 */
namespace ToAdwords;

use ToAdwords\AdwordsAdapter;
use ToAdwords\Model\CustomerModel;
use ToAdwords\Util\Message;
use ToAdwords\MessageHandler;
use ToAdwords\Definition\Operation;
use ToAdwords\Definition\SyncStatus;
use ToAdwords\Util\Log;
use ToAdwords\Adwords\CustomerManager;

use \Exception;

class CustomerAdapter extends AdwordsAdapter{
	protected static $moduleName = 'Customer';
	public static $currentModelName = 'ToAdwords\Model\CustomerModel';
	protected static $currentManagerName   = 'ToAdwords\Adwords\CustomerManager';

	/**
	 * Create new user. 
	 *
	 * @param array $data: array('idclick_uid'=> 456). 
	 * @return boolean: TRUE, FALSE
	 */
	public function create(array $data){
		try{
			Log::write('Got data'. print_r($data, TRUE), __METHOD__);
			$customerModel = new CustomerModel();
			$customerModel->insertOne($data);

			$message = new Message();
			$message->setModule(self::$moduleName);
			$message->setAction(Operation::CREATE);
			$message->setInformation($data);

			$messageHandler = new MessageHandler();
			$messageHandler->put($message, array($customerModel, 'updateSyncStatus'));
			unset($message);
			unset($messageHandler);

			$this->result['status'] = 1;
			$this->result['description'] = self::$moduleName . ' create success!';
			$this->result['success']++;
			$this->process++;

			return $this->generateResult();
		} catch (Exception $e){
			Log::write('[warning] ' . get_class($e) . ' ' . $e->getMessage(), __METHOD__);
		}
	}

	/**
	 * Create Adwords Customer
	 *
	 * Call AdwordsManager to create a customer account on mcc.
	 *
	 * @param array $data: default NULL
	 * @return boolean: TRUE on success, FALSE on failure.
	 */
	public function createAdwordsObject($data){
		try{
			if(!isset($data['idclick_uid'])){
				throw new Exception('idclick uid not found.');
			}
			$customerManager = new CustomerManager();
			$customerId = $customerManager->create();
			Log::write("[notice] Account with customer_id #{$customerId} was created.\n", __METHOD__);
			$customerModel = new self::$currentModelName();
			$customerModel->updateSyncStatus(SyncStatus::SYNCED, $data['idclick_uid']);
			return TRUE;
		} catch(Exception $e){
			Log::write("[warning] An error has occurred: {$e->getMessage()}\n", __METHOD__);
			return FALSE;
		}
	}

	public function updateAdwordsObject(){
		Log::write("Method does not supported.\n", __METHOD__);
		return FALSE;
	}

	public function deleteAdwordsObject(){
		Log::write("Method does not supported.\n", __METHOD__);
		return FALSE;
	}
}
