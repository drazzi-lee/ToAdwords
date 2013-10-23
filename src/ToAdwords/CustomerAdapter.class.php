<?php

namespace ToAdwords;

use ToAdwords\AdwordsAdapter;
use ToAdwords\MessageHandler;
use ToAdwords\Util\Log;
use ToAdwords\Util\Message;
use ToAdwords\Model\CustomerModel;
use ToAdwords\Definition\Operation;
use ToAdwords\Exception\ModelException;
use ToAdwords\Exception\MessageException;

use \AdWordsUser;
use \Exception;


/**
 * 用户
 */
class CustomerAdapter extends AdwordsAdapter{

    /**
     * Current Module Name. 
     */
	protected static $moduleName = 'Customer';
	

	/**
	 * Create new user. 
	 *
	 * @param array $data: array('idclick_uid'=> 456). 
	 * @return boolean: TRUE, FALSE
	 */
	protected function create($data){
		try{
			$customerModel = new CustomerModel();
			$customerModel->insertOne($data);

			$message = new Message();
			$message->setModule($this->moduleName);
			$message->setAction(Operation::CREATE);
			$message->setInformation($data);

			$messageHandler = new MessageHandler();
		 	$messageHandler->put($message, array($customerModel, 'updateSyncStatus');
			return TRUE;
		} catch (MessageException $e){
			Log::write('[MESSAGE_ERROR]'.$e->getMessage(), __METHOD__);	
			return FALSE;
		} catch (ModelException $e){
			Log::write('[MODEL_ERROR] idclick_uid #'.$idclickUid
									.' --> '.$e->getMessage(), __METHOD__);					
			return FALSE;
		} catch (Exception $e){
			Log::write($e->getMessage(), __METHOD__);					
			return FALSE;
		}
	}
}
