<?php

namespace ToAdwords;

use ToAdwords\AdwordsAdapter;
use ToAdwords\CampaignAdapter;
use ToAdwords\Util\Log;
use ToAdwords\Object\Idclick\AdPlan;
use ToAdwords\Object\Idclick\AdGroup;
use ToAdwords\Exceptions\DependencyException;
use ToAdwords\Exceptions\SyncStatusException;
use ToAdwords\Exceptions\DataCheckException;
use ToAdwords\Exceptions\MessageException;

use \Exception;
use \PDOException;

/**
 * 广告组
 */
class AdGroupAdapter extends AdwordsAdapter{
	protected $tableName = 'adgroup';
	protected $moduleName = 'AdGroup';
	
	protected $adwordsObjectIdField = 'adgroup_id';
	protected $idclickObjectIdField = 'idclick_groupid';
	
	protected $dataCheckFilter = array(
				'CREATE'	=> array(
					'requiredFields'	=> array(
						'idclick_groupid','idclick_planid','adgroup_name','keywords',
						'adgroup_status',
					),
					'prohibitedFields'	=> array('sync_status', 'adgroup_id', 'campaign_id'),
				),
				'UPDATE'	=> array(
					'requiredFields'	=> array('idclick_groupid','idclick_planid'),
					'prohibitedFields'	=> array('sync_status', 'adgroup_id', 'campaign_id'),
				),
				'DELETE'	=> array(
					'requiredFields'	=> array('idclick_groupid','idclick_planid'),
					'prohibitedFields'	=> array('sync_status', 'adgroup_id', 'campaign_id'),
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
			if(empty($data['idclick_groupid']) || empty($data['idclick_planid'])){
				throw new DataCheckException('基本数据缺失，idclick_planid及idclick_groupid为必需。');
			}
			
			$adGroupRow = $this->getOne('idclick_groupid','idclick_groupid='.$data['idclick_groupid']);
			if(!empty($adGroupRow)){
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
	 * 添加广告组
	 *
	 * @param array $data: 要添加的数据，数据结构为
	 * 		$data = array(
	 *			'idclick_groupid'	=> 123456,
	 * 			'idclick_planid'	=> 12345,
	 * 			'idclick_uid'		=> 441,			
	 *			'adgroup_name'		=> 'group_name',
	 *			'keywords'			=> array('keywords1', 'keywords2'),
	 *			'budget_amount'		=> 200.00,	
	 * 		);
	 * @return array $result
	 */
	public function create($data){
		try{
			if(self::IS_CHECK_DATA){
				$this->checkData($data, self::ACTION_CREATE);
			}
			
			$adGroupRow = $this->getOne('idclick_groupid','idclick_groupid='.$data['idclick_groupid']);
			if(!empty($adGroupRow)){
				throw new DataCheckException('广告组已存在，idclick_groupid为'.$data['idclick_groupid']);
			}
			
			$campaignAdapter = new CampaignAdapter();
			$adPlan = new AdPlan($data['idclick_planid']);			
			$data['campaign_id'] = $campaignAdapter->getAdaptedId($adPlan);
			
			$this->dbh->beginTransaction();
			$adGroup = new AdGroup($data['idclick_groupid']);
			if($this->insertOne($data) && $this->createMessageAndPut($data, self::ACTION_CREATE)
					&& $this->updateSyncStatus(self::SYNC_STATUS_QUEUE, $adGroup)){
				$this->dbh->commit();
				$this->processed++;
				$this->result['success']++;
				$this->result['description'] = '广告组添加成功';
				return $this->generateResult();
			} else {
				throw new Exception('顺序执行插表、发送消息、更新同步状态为QUEUE出错。');
			}
		} catch (DataCheckException $e){
			$this->result['status'] = -1;
			$this->result['description'] = '数据验证未通过：'.$e->getMessage();
			return $this->generateResult();
		} catch (DependencyException $e){
			$this->result['status'] = -1;
			$this->result['description'] = '依赖关系错误：'.$e->getMessage();
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
			$this->result['description'] = '数据表新插入一行失败，事务已回滚，idclick_groupid为'.$data['idclick_groupid']
									.' ==》'.$e->getMessage();
			Log::write('数据表新插入一行失败，事务已回滚，idclick_groupid为'.$data['idclick_groupid']
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
	 * 更新广告组
	 *
	 * @param array $data: 结构为：
	 *	  $data = array(
	 *	   		'idclick_groupid'	=> 123456,
	 *	 		'idclick_uid'		=> 441,			
	 *	   		'adgroup_name'		=> 'group_name2',
	 *	   		'keywords'			=> array('keywords3', 'keywords2', 'keywords1'),
	 *	   		'budget_amount'		=> 201.00,
	 *	   );
	 * @return array $result
	 */	
	public function update($data){
		try{
			if(self::IS_CHECK_DATA){
				$this->checkData($data, self::ACTION_UPDATE);
			}
			
			$adGroupRow = $this->getOne('idclick_groupid,idclick_planid','idclick_groupid='
																	. $data['idclick_groupid']);
			if(empty($adGroupRow)){
				throw new DataCheckException('广告组未找到，idclick_groupid为' 
																	. $data['idclick_groupid']);
			} else if($adGroupRow['idclick_planid'] != $data['idclick_planid']){
				throw new DataCheckException('找到广告组ID #' . $data['idclick_groupid']
						. '，但idclick_planid不符，提供的idclick_planid #' . $data['idclick_planid']
						. '  记录中的idclick_planid #' . $adGroupRow['idclick_planid']);
			}
			
			$data['last_action'] = isset($data['last_action']) ? $data['last_action'] : self::ACTION_UPDATE;
			$conditions = 'idclick_groupid='.$data['idclick_groupid'];
			$conditionsArray = array(
						'idclick_groupid'	=> $data['idclick_groupid'],
					);
			$newData = array_diff_key($data, $conditionsArray);
			
			$this->dbh->beginTransaction();
			$adGroup = new AdGroup($data['idclick_groupid']);
			if($this->updateOne($conditions, $newData) && $this->createMessageAndPut($data, $data['last_action'])
					&& $this->updateSyncStatus(self::SYNC_STATUS_QUEUE, $adGroup)){
				$this->dbh->commit();
				$this->processed++;
				$this->result['success']++;
				$this->result['description'] = '广告计划'.$data['last_action'].'操作成功';
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
		$infoForRemove['adgroup_status'] = 'DELETE';
		$infoForRemove[$this->idclickObjectIdField] = $data[$this->idclickObjectIdField];
		
		return $this->update($infoForRemove);
	}
}