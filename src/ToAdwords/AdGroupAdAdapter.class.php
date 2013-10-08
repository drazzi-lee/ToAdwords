<?php

namespace ToAdwords;

use ToAdwords\AdwordsAdapter;
use ToAdwords\AdGroupAdapter;
use ToAdwords\Util\Log;
use ToAdwords\Object\Idclick\AdGroup;
use ToAdwords\Object\Idclick\Ad;
use ToAdwords\Exceptions\DependencyException;
use ToAdwords\Exceptions\SyncStatusException;
use ToAdwords\Exceptions\DataCheckException;
use ToAdwords\Exceptions\MessageException;

use \Exception;
use \PDOException;

/**
 * 广告
 */
class AdGroupAdAdapter extends AdwordsAdapter{
	protected $tableName = 'adgroupad';
	protected $moduleName = 'AdGroupAd';
	
	protected $adwordsObjectIdField = 'ad_id';
	protected $idclickObjectIdField = 'idclick_adid';
	
	protected $dataCheckFilter = array(
				'CREATE'	=> array(
					'requiredFields'	=> array(
						'idclick_adid','idclick_groupid',
					),
					'prohibitedFields'	=> array('sync_status', 'ad_id', 'adgroup_id'),
				),
				'UPDATE'	=> array(
					'requiredFields'	=> array('idclick_adid'),
					'prohibitedFields'	=> array('sync_status', 'ad_id', 'adgroup_id'),
				),
				'DELETE'	=> array(
					'requiredFields'	=> array('idclick_adid'),
					'prohibitedFields'	=> array('sync_status', 'ad_id', 'adgroup_id'),
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
			
			if(empty($data['idclick_groupid']) || empty($data['idclick_adid'])){
				throw new DataCheckException('基本数据缺失。idclick_groupid及idclick_adid为必需。');
			}
			
			$adGroupAdRow = $this->getOne('idclick_adid','idclick_adid='.$data['idclick_adid']);
			if(!empty($adGroupAdRow)){
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
	 * 添加广告
	 *
	 * @param array $data: 要添加的数据，数据结构为
	 * 		$data = array(
	 * 			'idclick_adid'		=> 12345,
	 * 			'idclick_uid'		=> 441,			
	 *			'idclick_groupid'	=> 123456,
	 *			'ad_headline'		=> 'headline',
	 *			'ad_description1'	=> 'description1',
	 *			'ad_description2'	=> 'description2',
	 *			'ad_url'			=> 'http://www.izptec.com/go.php',
	 *			'ad_displayurl'		=> 'http://www.izptec.com/',
	 * 		);
	 * @return array $result
	 */
	public function create($data){
		try{
			if(self::IS_CHECK_DATA && !$this->checkData($data, self::ACTION_CREATE)){		
				throw new DataCheckException(self::DESC_DATA_CHECK_FAILURE);
			}
			
			$adGroupAdRow = $this->getOne('idclick_adid','idclick_adid='.$data['idclick_adid']);
			if(!empty($adGroupAdRow)){
				throw new DataCheckException('广告已存在，idclick_adid为'.$data['idclick_adid']);
			}
			
			$adGroupAdapter = new AdGroupAdapter();
			$adGroup = new AdGroup($data['idclick_groupid']);
			$data['adgroup_id'] = $adGroupAdapter->getAdaptedId($adGroup);
			
			$this->dbh->beginTransaction();
			$ad = new Ad($data['idclick_adid']);
			if($this->insertOne($data) && $this->createMessageAndPut($data, self::ACTION_CREATE)
					&& $this->updateSyncStatus(self::SYNC_STATUS_QUEUE, $ad)){
				$this->dbh->commit();
				$this->processed++;
				$this->result['success']++;
				$this->result['description'] = '广告添加成功';
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
			if(self::IS_CHECK_DATA && !$this->checkData($data, self::ACTION_UPDATE)){		
				throw new DataCheckException(self::DESC_DATA_CHECK_FAILURE);
			}
			
			$adGroupAdRow = $this->getOne('idclick_adid','idclick_adid='.$data['idclick_adid']);
			if(empty($adGroupAdRow)){
				throw new DataCheckException('广告已存在，idclick_adid为'.$data['idclick_adid']);
			}
			
			$data['last_action'] = isset($data['last_action']) ? $data['last_action'] : self::ACTION_UPDATE;
			$conditions = 'idclick_adid='.$data['idclick_adid'];
			$conditionsArray = array(
						'idclick_adid'	=> $data['idclick_adid'],
					);
			$newData = array_diff_key($data, $conditionsArray);
			
			$this->dbh->beginTransaction();
			$adGroupAd = new AdGroupAd($data['idclick_adid']);
			if($this->updateOne($conditions, $newData) && $this->createMessageAndPut($data, $data['last_action'])
					&& $this->updateSyncStatus(self::SYNC_STATUS_QUEUE, $adGroupAd)){
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
			Log::write('从AMC接口收到数据=========================\r\n'
				.print_r($data, TRUE), __METHOD__);
		}
			
		if(self::IS_CHECK_DATA && !$this->checkData($data, self::ACTION_DELETE)){
			$this->result['status'] = -1;
			$this->result['description'] = self::DESC_DATA_CHECK_FAILURE;
			return $this->_generateResult();
		}
		
		$infoForRemove = array();
		$infoForRemove['last_action'] = self::ACTION_DELETE;
		$infoForRemove[$this->idclickObjectIdField] = $data[$this->idclickObjectIdField];
		
		return $this->update($infoForRemove);
	}
}