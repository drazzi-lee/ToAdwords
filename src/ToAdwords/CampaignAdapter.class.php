<?php

namespace ToAdwords;

use ToAdwords\AdwordsAdapter;
use ToAdwords\CustomerAdapter;
use ToAdwords\Util\Log;
use ToAdwords\Util\Message;
use ToAdwords\Object\Idclick\Member;
use ToAdwords\Object\Idclick\AdPlan;
use ToAdwords\Exceptions\DataCheckException;
use ToAdwords\Exceptions\SyncStatusException;
use ToAdwords\Exceptions\DependencyException;
use ToAdwords\Exceptions\MessageException;
use \Exception;
use \PDOException;


/**
 * 广告系列
 */
class CampaignAdapter extends AdwordsAdapter{
	protected $tableName = 'campaign';
	protected $moduleName = 'Campaign';
	
	protected $adwordsObjectIdField = 'campaign_id';
	protected $idclickObjectIdField = 'idclick_planid';
	
	/**
	 * 添加广告计划
	 *
	 * @param array $data: 要添加的数据，数据结构为
	 * 		$data = array(
	 * 			'idclick_planid'	=> 12345,
	 * 			'idclick_uid'		=> 441,		//创建时必需
	 * 			'campaign_name'		=> 'campaign_name',
	 *			'areas'				=> '10031, 10032',
	 *			'languages'			=> '10031, 10032',
	 *			'bidding_type'		=> 1,
	 *			'budget_amount'		=> 200.00,
	 *			'delivery_method'	=> 'ACCELERATED',
	 *			'max_cpc'			=> 2.00,
	 *			'campaign_status'	=> 'ACTIVE',		//ACTIVE PAUSE DELETE	
	 * 		);
	 * @return array $result
	 */
	public function create(array $data){		
		if(self::IS_CHECK_DATA && !$this->_checkData($data, self::ACTION_CREATE)){		
			$this->result['status'] = -1;
			$this->result['description'] = self::DESC_DATA_CHECK_FAILURE;
			return $this->result;
		}
		
		try{			
			$customerAdapter = new CustomerAdapter();
			$idclickMember = new Member($data['idclick_uid']);
			$data['last_action'] = self::ACTION_CREATE;
			$data['customer_id'] = $customerAdapter->getAdaptedId($idclickMember);
			
			if($this->insertOne($data)){
				$this->processed++;
				$this->result['success']++;
				$this->result['description'] = '广告计划添加成功';
			}
			return $this->_generateResult();
		} catch (PDOException $e){
			echo $e->getMessage();
		} catch (DataCheckException $e){
			echo $e->getMessage();
		} catch (SyncStatusException $e){
			echo $e->getMessage();
		} catch (DependencyException $e){
			echo $e->getMessage();
		} catch (Exception $e){
			echo $e->getMessage();
		}
	}
	
	/**
	 * 更新广告计划
	 *
	 * @param array $data: 要添加的数据，数据结构为
	 * 		$data = array(
	 * 			'idclick_planid'	=> 12345,
	 * 			'idclick_uid'		=> 441,
	 * 			'campaign_name'		=> 'campaign_name',
	 *			'areas'				=> '10031, 10032',
	 *			'languages'			=> '10031, 10032',
	 *			'bidding_type'		=> 1,
	 *			'budget_amount'		=> 200.00,
	 *			'delivery_method'	=> 'ACCELERATED',
	 *			'max_cpc'			=> 2.00,
	 * 		);
	 * @return array $result
	 */
	public function update(array $data){
		if(self::IS_CHECK_DATA && !$this->_checkData($data, 'UPDATE')){
			$this->result['status'] = -1;
			$this->result['description'] = self::DESC_DATA_CHECK_FAILURE;
			return $this->result;
		}
		
		$conditions = array(
					'idclick_planid'	=> $data['idclick_planid'],
					'idclick_uid'		=> $data['idclick_uid'],
				);
		$newStatus = array_diff_key($data, $conditions);
		$newStatus['last_action'] = self::ACTION_UPDATE;
		//$newStatus['sync_status'] = self::SYNC_STATUS_RECEIVE;
		if(FALSE !== $this->where($conditions)->save($newStatus)){
			$this->processed++;
			$this->result['success']++;
		} else {
			$this->processed++;
			$this->result['failure']++;
		}
		return $this->_generateResult();		
	}
	
	/**
	 * 删除广告计划
	 * @param array $data
	 */
	public function delete(array $data){
		if(self::IS_CHECK_DATA && !$this->_checkData($data, 'DELETE')){
			$this->result['status'] = -1;
			$this->result['description'] = self::DESC_DATA_CHECK_FAILURE;
			return $this->result;
		}
		
		$conditions = array(
					'idclick_planid'	=> $data['idclick_planid'],
				);
		$newStatus = array(
					'campaign_status'	=> 'DELETE',
					'last_action'		=> self::ACTION_DELETE,
				);
		
		if(FALSE !== $this->where($conditions)->save($newStatus)){
			$this->processed++;
			$this->result['success']++;
		} else {			
			$this->processed++;
			$this->result['failure']++;
		}
		return $this->_generateResult();		
	}
	
	/**
	 * 插入新广告系列记录
	 */
	private function insertOne($data){
		$fields = $this->_arrayToString(array_keys($data));
		$preparedPlaceholders = $this->_arrayToSpecialString(array_keys($data));
		$preparedParams = array_combine(explode(',',$preparedPlaceholders), array_values($data));
		$sql = 'INSERT INTO `'.$this->tableName.'` ('.$fields.') VALUES ('.$preparedPlaceholders.')';
		try{
			$campaignRow = $this->getOne('idclick_planid','idclick_planid='.$data['idclick_planid']);
			if(!empty($campaignRow)){
				throw new DataCheckException('广告计划已存在，idclick_planid为.'.$data['idclick_planid']);
			}
			$this->dbh->beginTransaction();			
			$statement = $this->dbh->prepare($sql);
			$adPlan = new AdPlan($data['idclick_planid']);
			if($statement->execute($preparedParams) && $this->_createMessageAndPut($data)
					&& $this->updateSyncStatus(self::SYNC_STATUS_QUEUE, $adPlan)){
				$this->dbh->commit();
				return TRUE;
			} else {
				throw new Exception('顺序执行插表、发送消息、更新同步状态为QUEUE出错。');
			}
		} catch (DataCheckException $e){
			$this->result['status'] = -1;
			$this->result['description'] = '数据验证未通过。'.$e->getMessage();
			return FALSE;
		} catch (MessageException $e){
			$this->result['status'] = -1;
			$this->result['description'] = '消息过程异常：'.$e->getMessage();
			return FALSE;
		} catch (PDOException $e){
			$this->dbh->rollBack();
			$this->result['status'] = -1;
			$this->result['description'] = '在Campaign表新插入一行失败，事务已回滚，idclick_planid为'.$data['idclick_planid']
									.' ==》'.$e->getMessage();
			Log::write('在Campaign表新插入一行失败，事务已回滚，idclick_planid为'.$data['idclick_planid']
									.' ==》'.$e->getMessage(), __METHOD__);	
			return FALSE;
		} catch (Exception $e){
			$this->dbh->rollBack();
			$this->result['status'] = -1;
			$this->result['description'] = $e->getMessage();
			return FALSE;
		}
	}
	
	/**
	 * 构建消息并推送至消息队列
	 *
	 *
	 */
	private function _createMessageAndPut($data){
		$information = $data;
		$message = new Message($this->moduleName, self::ACTION_CREATE, $information);
		return $message->put();
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}
