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
     * 当前数据操作时需要的数据库对象
     */
    protected $database;
	
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
					&& $this->createMessageAndPut(array('idclick_uid' => $idclickUid), self::ACTION_CREATE)
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
}