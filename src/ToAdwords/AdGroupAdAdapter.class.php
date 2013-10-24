<?php

namespace ToAdwords;

use ToAdwords\AdwordsAdapter;

/**
 * 广告
 */
class AdGroupAdAdapter extends AdwordsAdapter{
	protected static $moduleName        = 'AdGroupAd';
	protected static $currentModelName  = 'ToAdwords\Model\AdGroupAdModel';
	protected static $parentModelName   = 'ToAdwords\Model\AdGroupModel';
	protected static $parentAdapterName = 'ToAdwords\AdGroupAdapter';
	protected static $dataCheckFilter   = array(
			'CREATE'    => array(
				'requiredFields'    => array(
					'idclick_adid','idclick_groupid','ad_status'
					),
				'prohibitedFields'	=> array('sync_status', 'ad_id', 'adgroup_id'),
				),
			'UPDATE'	=> array(
				'requiredFields'	=> array('idclick_adid','idclick_groupid','ad_status'),
				'prohibitedFields'	=> array('sync_status', 'ad_id', 'adgroup_id'),
				),
			'DELETE'	=> array(
				'requiredFields'	=> array('idclick_adid','idclick_groupid','ad_status'),
				'prohibitedFields'	=> array('sync_status', 'ad_id', 'adgroup_id'),
				),
			);
	
	/**
	 * 更新广告
	 *
	 * @param array $data: 要添加的数据，数据结构为
	 * 		$data = array(
	 * 			'idclick_adid'		=> 12345,
	 * 			'idclick_groupid'	=> 123456,
	 *			'ad_headline'		=> 'headline', //可选
	 *			'ad_description1'	=> 'description1', //可选
	 *			'ad_description2'	=> 'description2', //可选
	 *			'ad_url'			=> 'http://www.izptec.com/go.php', //可选
	 *			'ad_displayurl'		=> 'http://www.izptec.com/', //可选
	 * 		);
	 * @return array $result
	 */	
	public function update($data){
		try{
			if(self::IS_CHECK_DATA){
				$this->checkData($data, Operation::UPDATE);
			}
			
			$adGroupAdRow = $this->getOne('idclick_adid,idclick_groupid','idclick_adid='
																		. $data['idclick_adid']);
			if(empty($adGroupAdRow)){
				throw new DataCheckException('广告已存在，idclick_adid为'.$data['idclick_adid']);
			} else if($adGroupAdRow['idclick_groupid'] != $data['idclick_groupid']){
				throw new DataCheckException('找到广告ID #' . $data['idclick_adid']
						. '，但idclick_planid不符，提供的idclick_planid #' . $data['idclick_groupid']
						. '  记录中的idclick_planid #' . $adGroupRow['idclick_groupid']);
			}
			
			$data['last_action'] = isset($data['last_action']) ? $data['last_action'] : Operation::UPDATE;
			$conditions = 'idclick_adid='.$data['idclick_adid'];
			$conditionsArray = array(
						'idclick_adid'	=> $data['idclick_adid'],
					);
			$newData = array_diff_key($data, $conditionsArray);
			
			$this->dbh->beginTransaction();
			$ad = new Ad($data['idclick_adid']);
			if($this->updateOne($conditions, $newData) && $this->createMessageAndPut($data, $data['last_action'])
					&& $this->updateSyncStatus(SyncStatus::QUEUE, $ad)){
				$this->dbh->commit();
				$this->processed++;
				$this->result['success']++;
				$this->result['description'] = '广告'.$data['last_action'].'操作成功';
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
		$infoForRemove['ad_status'] = 'DELETE';
		$infoForRemove[$this->idclickObjectIdField] = $data[$this->idclickObjectIdField];
		
		return $this->update($infoForRemove);
	}
}
