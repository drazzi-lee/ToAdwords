<?php

namespace ToAdwords\Model;

use ToAdwords\BaseModel;

use \PDO;
use \PDOException;
use \Exception;


/**
 * CustomerModel 
 */
class CustomerModel extends BaseModel{
    /**
     * 当前数据操作需要的表名
     */
	protected $tableName = 'customer';

    /**
     * 当前操作对象在数据库中的字段名
     */
	protected $adwordsObjectIdField = 'adwords_customerid';
	protected $idclickObjectIdField = 'idclick_uid';

	/**
	 * 新建用户
	 *
	 * @param string $idclickUid: Idclick用户ID
	 * @return boolean: TRUE, FALSE
	 */
	public function create($idclickUid){
		try{
			return	$this->insertOne(array('idclick_uid' => $idclickUid));
		} catch(PDOException $e){
			Log::write('[ERROR] 数据操作失败：' . $e->getMessage(), __METHOD__);
			throw new ModelException('[ERROR] 数据操作失败，上次Sql语句：' . $this->getLastSql()); 
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
	protected function create($idclickUid){
		try{
			$this->dbh->beginTransaction();
			$member = new Member($idclickUid);
			if($this->insertOne(array('idclick_uid' => $idclickUid, 'last_action' => 'CREATE')) 
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
