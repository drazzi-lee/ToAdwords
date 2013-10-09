<?php

namespace ToAdwords;

use ToAdwords\AdwordsAdapter;
use ToAdwords\CustomerAdapter;
use ToAdwords\Util\Log;
use ToAdwords\Util\Message;
use ToAdwords\Object\Idclick\Member;
use ToAdwords\Object\Idclick\AdPlan;
use ToAdwords\Exceptions\SyncStatusException;
use ToAdwords\Exceptions\DataCheckException;
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
	
	protected $dataCheckFilter = array(
				'CREATE'	=> array(
					'requiredFields'	=> array(
						'idclick_planid','idclick_uid','campaign_name','languages',
						'areas','bidding_type','budget_amount','max_cpc','campaign_status'
					),
					'prohibitedFields'	=> array('sync_status','campaign_id','customer_id'),
				),
				'UPDATE'	=> array(
					'requiredFields'	=> array('idclick_planid','idclick_uid'),
					'prohibitedFields'	=> array('sync_status','campaign_id','customer_id'),
				),
				'DELETE'	=> array(
					'requiredFields'	=> array('idclick_planid','idclick_uid'),
					'prohibitedFields'	=> array('sync_status','campaign_id','customer_id'),
				),
			);
	
	/**
	 * 封装update create为run方法供thrift使用
	 */
	public function run(array $data){
		try{
			if(ENVIRONMENT == 'development'){
				Log::write('从AMC接口收到数据=========================\r\n'
					.print_r($data, TRUE), __METHOD__);
			}
			if(empty($data['idclick_planid']) || empty($data['idclick_uid'])){
				throw new DataCheckException('基本数据缺失。idclick_uid及idclick_planid为必需。');
			}
			
			$campaignRow = $this->getOne('idclick_planid','idclick_planid='.$data['idclick_planid']);
			if(!empty($campaignRow)){
				return $this->update($data);
			} else {
				return $this->create($data);
			}
		} catch (DataCheckException $e){
			$this->result['status'] = -1;
			$this->result['description'] = '数据验证未通过：'.$e->getMessage();
			return $this->generateResult();
		}
	}
	
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
		try{
			if(self::IS_CHECK_DATA){
				$this->checkData($data, self::ACTION_CREATE);
			}
			
			$campaignRow = $this->getOne('idclick_planid','idclick_planid='.$data['idclick_planid']);
			if(!empty($campaignRow)){
				throw new DataCheckException('广告计划已存在，idclick_planid为'.$data['idclick_planid']);
			}
			
			$customerAdapter = new CustomerAdapter();
			$idclickMember = new Member($data['idclick_uid']);
			$data['customer_id'] = $customerAdapter->getAdaptedId($idclickMember);
			if($data['customer_id'] === TRUE){
				unset($data['customer_id']);
			}
			
			if(isset($data['bidding_type'])){
				switch($data['bidding_type']){
					case '0': $data['bidding_type'] = 'MANUAL_CPC'; break;
					case '1': $data['bidding_type'] = 'BUDGET_OPTIMIZER'; break;
					default: throw new DataCheckException('未知的bidding_type ##'.$data['bidding_type']);
				}
			}
			
			$this->dbh->beginTransaction();
			$adPlan = new AdPlan($data['idclick_planid']);
			if($this->insertOne($data) && $this->createMessageAndPut($data, self::ACTION_CREATE)
					&& $this->updateSyncStatus(self::SYNC_STATUS_QUEUE, $adPlan)){
				$this->dbh->commit();
				$this->processed++;
				$this->result['success']++;
				$this->result['description'] = '广告计划添加成功';
				return $this->generateResult();
			} else {
				throw new Exception('顺序执行插表、发送消息、更新同步状态为QUEUE出错。');
			}			
		} catch (DataCheckException $e){
			$this->result['status'] = -1;
			$this->result['description'] = '数据验证未通过：'.$e->getMessage();
			return $this->generateResult();
		} catch (MessageException $e){
			$this->result['status'] = -1;
			$this->result['description'] = '消息过程异常：'.$e->getMessage();
			return $this->generateResult();
		} catch (SyncStatusException $e){
			$this->result['status'] = -1;
			$this->result['description'] = '异常的同步状态：'.$e->getMessage();
			return $this->generateResult();
		}catch (PDOException $e){
			$this->dbh->rollBack();
			$this->result['status'] = -1;
			$this->result['description'] = '数据表新插入一行失败，事务已回滚，idclick_planid为'.$data['idclick_planid']
									.' ==》'.$e->getMessage();
			Log::write('数据表新插入一行失败，事务已回滚，idclick_planid为'.$data['idclick_planid']
									.' ==》'.$e->getMessage(), __METHOD__);	
			return $this->generateResult();
		} catch (Exception $e){
			if($this->dbh->inTransaction()){
				$this->dbh->rollBack();
			}
			$this->result['status'] = -1;
			$this->result['description'] = $e->getMessage();
			return $this->generateResult();
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
		try{
			if(self::IS_CHECK_DATA){
				$this->checkData($data, self::ACTION_UPDATE);
			}
			
			$campaignRow = $this->getOne('idclick_planid,idclick_uid', 
											'idclick_planid='.$data['idclick_planid']);
			if(empty($campaignRow)){
				throw new DataCheckException('广告计划未找到，idclick_planid为'
															.$data['idclick_planid']);
			} else if($campaignRow['idclick_uid'] != $data['idclick_uid']){
				throw new DataCheckException('找到广告计划ID #' . $data['idclick_planid']
						. '，但idclick_uid不符，提供的idclick_uid #' . $data['idclick_uid']
						. '  记录中的idclick_uid #' . $campaignRow['idclick_uid']);
			}			
			
			$data['last_action'] = isset($data['last_action']) ? $data['last_action'] : self::ACTION_UPDATE;
			$conditions = 'idclick_planid='.$data['idclick_planid'];
			$conditionsArray = array(
						'idclick_planid'	=> $data['idclick_planid'],
					);
			$newData = array_diff_key($data, $conditionsArray);
			
			if(isset($data['bidding_type'])){
				switch($data['bidding_type']){
					case '0': $data['bidding_type'] = 'MANUAL_CPC'; break;
					case '1': $data['bidding_type'] = 'BUDGET_OPTIMIZER'; break;
					default: throw new DataCheckException('未知的bidding_type ##'.$data['bidding_type']);
				}
			}
			
			$this->dbh->beginTransaction();
			$adPlan = new AdPlan($data['idclick_planid']);
			if($this->updateOne($conditions, $newData) && $this->createMessageAndPut($data, $data['last_action'])
					&& $this->updateSyncStatus(self::SYNC_STATUS_QUEUE, $adPlan)){
				$this->dbh->commit();
				$this->processed++;
				$this->result['success']++;
				$this->result['description'] = '广告计划操作成功';
				return $this->generateResult();
			} else {
				throw new Exception('顺序操作数据表、发送消息、更新同步状态为QUEUE出错。');
			}			
		} catch (DataCheckException $e){
			$this->result['status'] = -1;
			$this->result['description'] = '数据验证未通过：'.$e->getMessage();
			return $this->generateResult();
		} catch (MessageException $e){
			$this->result['status'] = -1;
			$this->result['description'] = '消息过程异常：'.$e->getMessage();
			return $this->generateResult();
		} catch (SyncStatusException $e){
			$this->result['status'] = -1;
			$this->result['description'] = '异常的同步状态：'.$e->getMessage();
			return $this->generateResult();
		} catch (PDOException $e){
			$this->dbh->rollBack();
			$this->result['status'] = -1;
			$this->result['description'] = '数据表操作失败，事务已回滚，idclick_planid为'.$data['idclick_planid']
									.' ==》'.$e->getMessage();
			Log::write('数据表操作失败，事务已回滚，idclick_planid为'.$data['idclick_planid']
									.' ==》'.$e->getMessage(), __METHOD__);	
			return $this->generateResult();
		} catch (Exception $e){
			if($this->dbh->inTransaction()){
				$this->dbh->rollBack();
			}
			$this->result['status'] = -1;
			$this->result['description'] = $e->getMessage();
			return $this->generateResult();
		}	
	}
	
	/**
	 * 删除广告计划
	 *
	 * @param array $data: 要删除的数据，数据结构为
	 * 		$data = array(
	 * 			'idclick_planid'	=> 12345, 
	 * 			'idclick_uid'		=> 441,
	 * 		);
	 * @return array $result
	 */
	public function delete(array $data){
		if(ENVIRONMENT == 'development'){
			Log::write('从AMC接口收到数据=========================\r\n'
				.print_r($data, TRUE), __METHOD__);
		}
		
		try{
			if(self::IS_CHECK_DATA){
				$this->checkData($data, self::ACTION_DELETE);
			}
		} catch (DataCheckException $e){
			$this->result['status'] = -1;
			$this->result['description'] = '数据验证未通过：'.$e->getMessage();
			return $this->generateResult();
		}
		
		$infoForRemove = array();
		$infoForRemove['last_action'] = self::ACTION_DELETE;
		$infoForRemove['campaign_status'] = 'DELETE';
		$infoForRemove[$this->idclickObjectIdField] = $data[$this->idclickObjectIdField];

		return $this->update($infoForRemove);
	}
}