<?php

namespace ToAdwords;

use ToAdwords\AdwordsAdapter;

/**
 * 广告组
 */
class AdGroupAdapter extends AdwordsAdapter{
	protected static $moduleName        = 'AdGroup';
	protected static $currentModelName  = 'ToAdwords\Model\AdGroupModel';
	protected static $parentModelName   = 'ToAdwords\Model\CampaignModel';
	protected static $parentAdapterName = 'ToAdwords\CampaignAdapter';
	protected static $dataCheckFilter 	= array(
			'CREATE'	=> array(
				'requiredFields'	=> array(
					'idclick_planid','idclick_groupid','adgroup_status'
					),
				'prohibitedFields'	=> array('sync_status', 'adgroup_id', 'campaign_id'),
				),
			'UPDATE'	=> array(
				'requiredFields'	=> array('idclick_planid','idclick_groupid','adgroup_status'),
				'prohibitedFields'	=> array('sync_status', 'adgroup_id', 'campaign_id'),
				),
			'DELETE'	=> array(
				'requiredFields'	=> array('idclick_planid','idclick_groupid','adgroup_status'),
				'prohibitedFields'	=> array('sync_status', 'adgroup_id', 'campaign_id'),
				),
			);
	
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
				$this->checkData($data, Operation::UPDATE);
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
			
			$data['last_action'] = isset($data['last_action']) ? $data['last_action'] : Operation::UPDATE;
			$conditions = 'idclick_groupid='.$data['idclick_groupid'];
			$conditionsArray = array(
						'idclick_groupid'	=> $data['idclick_groupid'],
					);
			$newData = array_diff_key($data, $conditionsArray);
			
			$this->dbh->beginTransaction();
			$adGroup = new AdGroup($data['idclick_groupid']);
			if($this->updateOne($conditions, $newData) && $this->createMessageAndPut($data, $data['last_action'])
					&& $this->updateSyncStatus(SyncStatus::QUEUE, $adGroup)){
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
			Log::write("从AMC接口收到数据=========================\n"
					. print_r($data, TRUE), __METHOD__);
		}
		try{
			if(self::IS_CHECK_DATA){
				$this->checkData($data, Operation::DELETE);
			}
		} catch (DataCheckException $e){
			$this->result['status'] = -1;
			$this->result['description'] = '数据验证未通过：'.$e->getMessage();
			return $this->generateResult();
		}
		
		$infoForRemove = array();
		$infoForRemove['last_action'] = Operation::DELETE;
		$infoForRemove['adgroup_status'] = 'DELETE';
		$infoForRemove[$this->idclickObjectIdField] = $data[$this->idclickObjectIdField];
		
		return $this->update($infoForRemove);
	}
}
