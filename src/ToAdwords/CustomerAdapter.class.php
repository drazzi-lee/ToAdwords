<?php

namespace ToAdwords;

use ToAdwords\AdwordsAdapter;
use ToAdwords\Model\CustomerModel;
use ToAdwords\Util\Message;
use ToAdwords\MessageHandler;

/**
 * 用户
 */
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
		$customerModel = new CustomerModel();
		$customerModel->insertOne($data);

		$message = new Message();
		$message->setModule($this->moduleName);
		$message->setAction(Operation::CREATE);
		$message->setInformation($data);

		$messageHandler = new MessageHandler();
		$messageHandler->put($message, array($customerModel, 'updateSyncStatus'));

		return $this->generateResult();
	}
}
