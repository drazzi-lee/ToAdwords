<?php

namespace ToAdwords;

use ToAdwords\AdwordsAdapter;
use ToAdwords\Util\Log;
use ToAdwords\Util\Message;
use ToAdwords\Model\CustomerModel;
use ToAdwords\Definition\Operation;
use \PDO;
use \PDOException;
use \AdWordsUser;
use \Exception;


/**
 * 用户
 */
class CustomerAdapter extends AdwordsAdapter{
    /**
     * 当前数据操作需要的表名
     */
	protected $tableName = 'customer';

    /**
     * 当前操作模块名称
     */
	protected $moduleName = 'Customer';
	
    /**
     * 当前操作对象在数据库中的字段名
     */
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
		try{
			$customerModel = new CustomerModel();
			$customerModel->insertOne(array('idclick_uid' => $idclickUid));

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
		} catch (PDOException $e){
			Log::write('在Customer表新插入一行失败，idclick_uid为'.$idclickUid
									.' ==》'.$e->getMessage(), __METHOD__);					
			return FALSE;
		} catch (Exception $e){
			Log::write($e->getMessage(), __METHOD__);					
			return FALSE;
		}
	}
}
