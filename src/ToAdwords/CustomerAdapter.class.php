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
	 * @param string $idclickUid: Idclick user id.
	 * @return boolean: TRUE, FALSE
	 */
	protected function create($idclickUid){
		try{
			$data = array(self::$idclickObjectIdField => $idclickUid);
			$customerModel = new CustomerModel();
			$customerModel->insertOne($data);

			$message = new Message();
			$message->setModule($this->moduleName);
			$message->setAction(Operation::CREATE);
			$message->setInformation($data);

			$messageHandler = new MessageHandler();
		 	$messageHandler->put($message, array($this, 'updateSyncStatus');
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
