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
use ToAdwords\Util\Log;

class CustomerAdapter extends AdwordsAdapter{
	protected static $moduleName = 'Customer';
	protected static $currentModelName = 'ToAdwords\Model\CustomerModel';

	/**
	 * Create new user. 
	 *
	 * @param array $data: array('idclick_uid'=> 456). 
	 * @return boolean: TRUE, FALSE
	 */
	public function create(array $data){
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
	}
}
