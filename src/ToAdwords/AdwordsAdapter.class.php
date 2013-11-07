<?php

/**
 * AdwordsAdapter.class.php
 *
 * Defines an abstract class AdwordsAdapter, parent class of all adapters.
 *
 * @author Li Pengfei
 * @email drazzi.lee@gmail.com
 * @version 1.0
 */
namespace ToAdwords;

use ToAdwords\Util\Log;
use ToAdwords\Util\Message;
use ToAdwords\Exception\DataCheckException;
use ToAdwords\Exception\DependencyException;
use ToAdwords\MessageHandler;
use ToAdwords\Definition\Operation;
use ToAdwords\Definition\SyncStatus;
use ToAdwords\Model\CustomerModel;
use ToAdwords\Model\CampaignModel;
use ToAdwords\Model\AdGroupModel;

use \Exception;

abstract class AdwordsAdapter{
	protected static $moduleName;
	protected static $adwordsObjectIdField;
	protected static $idclickObjectIdField;
	public static $currentModelName;
	protected static $parentModelName;
	protected static $parentAdapterName;

	protected $result = array(
				'status'		=> null,
				'description'	=> null,
				'success'		=> 0,
				'failure'		=> 0,
			);
	protected $processed = 0;

	/**
	 * As an interface for amc thrift.
	 */
	public function run(array $data){
		if(ENVIRONMENT == 'development'){
			Log::write("Received new data:\n" . print_r($data, TRUE), __METHOD__);
		}
		
		$currentModel = new static::$currentModelName();
		if(empty($data[$currentModel::$idclickObjectIdField])){
			throw new DataCheckException('Field #'.$currentModel::$idclickObjectIdField.' is required.');
		}

		$idclickField = $currentModel::$idclickObjectIdField;
		$row = $currentModel->getOne($idclickField, 
									$idclickField . '=' . $data[$idclickField]);
		if(!empty($row)){
			return $this->update($data);
		} else {
			return $this->create($data);
		}
	}

	/**
	 * Create an relation between idclick object and adwords object.
	 *
	 * @param array $data:
	 * @return string: JSON/Array
	 */
	public function create(array $data){
		try{
			Log::write("Received new data:\n" . print_r($data, TRUE), __METHOD__);
			self::prepareData($data, Operation::CREATE);
			
			//check parent dependency
			$parentModel = new static::$parentModelName();
			$idclickField = $parentModel::$idclickObjectIdField;
			$parentInfo = $parentModel->getOne($idclickField, 
									$idclickField . '=' . $data[$idclickField]);
			if($parentInfo === FALSE){
				if(static::$parentAdapterName == 'ToAdwords\CustomerAdapter'){
					$parentAdapter = new static::$parentAdapterName();	
					$parentAdapter->create(array(
								$parentModel::$idclickObjectIdField => 
									$data[$parentModel::$idclickObjectIdField]
								));
				} else {
					throw new DependencyException('dependency error, parent module #' .
								get_class($parentModel) . ' not found. parentObjectId #' .
								$data[$parentModel::$idclickObjectIdField]);
				}
			}
			$data['last_action'] = Operation::CREATE;
			
			//create record and send message to queue.
			$currentModel = new static::$currentModelName();
			$currentModel->insertOne($data);
			$this->sendMessage($data, array($currentModel, 'updateSyncStatus'));

			$this->result['status'] = 1;
			$this->result['description'] = static::$moduleName . ' create success!';
			$this->result['success']++;
			$this->process++;

			return $this->generateResult();
		} catch (Exception $e){
			$this->result['status'] = -1;
			$this->result['description'] = get_class($e) . ' ' . $e->getMessage(); 

			return $this->generateResult();
		}
	}

	/**
	 * Update relation between idclick object and adwords object.
	 *
	 * @param array $data:
	 * @return string: JSON/Array
	 */
	public function update(array $data){
		try{
			Log::write("Received new data:\n" . print_r($data, TRUE), __METHOD__);
			self::prepareData($data, Operation::UPDATE);
			
			//check whether it exists.
			$parentModel = new static::$parentModelName();
			$currentModel = new static::$currentModelName();
			$currentRow = $currentModel->getOne(
								$currentModel::$idclickObjectIdField .',' . $parentModel::$idclickObjectIdField, 
								$currentModel::$idclickObjectIdField . '=' . $data[$currentModel::$idclickObjectIdField]
								);
			if(empty($currentRow)){
				throw new DataCheckException(
								get_class($currentModel) . ' could not found, ' . $currentModel::$idclickObjectIdField .
								' #' . $data[$currentModel::$idclickObjectIdField]
								);
			} else if($currentRow[$parentModel::$idclickObjectIdField] != $data[$parentModel::$idclickObjectIdField]){
				throw new DataCheckException('Field #' . $parentModel::$idclickObjectIdField . ' does not match.');
			}
			
			//update record and send message to queue.
			$data['last_action'] = isset($data['last_action']) ? $data['last_action'] : Operation::UPDATE;
			$conditions = $currentModel::$idclickObjectIdField . '=' . $data[$currentModel::$idclickObjectIdField];
			$conditions .= ' AND ' . $parentModel::$idclickObjectIdField . '=' .$data[$parentModel::$idclickObjectIdField];
			$conditionsArray = array(
				$currentModel::$idclickObjectIdField 	=> $data[$currentModel::$idclickObjectIdField],
				$parentModel::$idclickObjectIdField 	=> $data[$parentModel::$idclickObjectIdField],
				);
			$newData = array_diff_key($data, $conditionsArray);
			$currentModel->updateOne($conditions, $newData);			
			$this->sendMessage($data, array($currentModel, 'updateSyncStatus'));

			$this->result['status'] = 1;
			$this->result['description'] = static::$moduleName . ' ' .strtolower($data['last_action']) . ' success!';
			$this->result['success']++;
			$this->process++;

			return $this->generateResult();
		} catch (Exception $e){
			$this->result['status'] = -1;
			$this->result['description'] = get_class($e) . ' ' . $e->getMessage(); 

			return $this->generateResult();
		}

	}

	/**
	 * Remove relation between idclick object and adwords object.
	 *
	 * @param array $data:
	 * @return string: JSON/Array
	 */
	public function delete(array $data){
		Log::write("Received new data:\n" . print_r($data, TRUE), __METHOD__);

		try{
			self::prepareData($data, Operation::DELETE);
		} catch(DataCheckException $e){
			$this->result['status'] = -1;
			$this->result['description'] = 'data check failure: ' . $e->getMessage();
			return $this->generateResult();
		}

		$currentModel = new static::$currentModelName();
		$parentModel = new static::$parentModelName();
		$infoForRemove = array();
		$infoForRemove['last_action'] = Operation::DELETE;
		$infoForRemove[$currentModel::$statusField] = 'DELETE';
		$infoForRemove[$currentModel::$idclickObjectIdField] = $data[$currentModel::$idclickObjectIdField];
		$infoForRemove[$parentModel::$idclickObjectIdField] = $data[$parentModel::$idclickObjectIdField];

		return $this->update($infoForRemove);
	}
	
	/**
	 * Create Adwords Object 
	 *
	 * Call AdwordsManager to create an adwords Object on specify customer's account.
	 *
	 * @param array $data: 
	 * @return boolean: TRUE on success, FALSE on failure.
	 */
	public function createAdwordsObject(array $data){
		try{
			$currentModel = new static::$currentModelName();
			$parentModel = new static::$parentModelName();
			$customerModel = new CustomerModel();
			
			//check required fields.
			$requiredFields = array(
				$customerModel::$idclickObjectIdField,
				$currentModel::$idclickObjectIdField,
				$parentModel::$idclickObjectIdField,
			);
			$this->checkFieldExists($requiredFields, $data);
			
			//get customer id.
			$data[$customerModel::$adwordsObjectIdField] =
						$this->getAdwordsCustomerId($data[$customerModel::$idclickObjectIdField]);
			if(empty($data[$customerModel::$adwordsObjectIdField]) || 
					$data[$customerModel::$adwordsObjectIdField] === FALSE){
				throw new Exception('customer has not synced yet.');	
			}
			
			//get parent adwords object id.
			$data[$parentModel::$adwordsObjectIdField] = 
						$this->getParentAdwordsObjectId($data[$parentModel::$idclickObjectIdField]);
			if(empty($data[$parentModel::$adwordsObjectIdField]) || 
					$data[$parentModel::$adwordsObjectIdField] === FALSE){
				throw new Exception('parent has not synced yet.');	
			}
			
			//call manager to create.
			$currentManager = new static::$currentManagerName($data[$customerModel::$adwordsObjectIdField]);
			$currentAdwordsObjectId = $currentManager->create($data);
			Log::write("[notice] " . static::$moduleName . " with {$currentModel::$adwordsObjectIdField}". 
											" #{$currentAdwordsObjectId} was created.\n", __METHOD__);
			
			//update current sync status.
			$currentModel = new static::$currentModelName();
			$currentModel->updateOne($currentModel::$idclickObjectIdField . '=' . $data[$currentModel::$idclickObjectIdField],
												array($currentModel::$adwordsObjectIdField	=> $currentAdwordsObjectId));
			$currentModel->updateSyncStatus(SyncStatus::SYNCED, $data[$currentModel::$idclickObjectIdField]);
			return TRUE;
		} catch(Exception $e){
			Log::write("[warning] An error has occurred: {$e->getMessage()}\n", __METHOD__);
			return FALSE;
		}
	}
	
	/**
	 * Update Adwords Object 
	 *
	 * Call AdwordsManager to update an adwords Object on specify customer's account.
	 *
	 * @param array $data: 
	 * @return boolean: TRUE on success, FALSE on failure.
	 */
	public function updateAdwordsObject(array $data){
		try{
			$currentModel = new static::$currentModelName();
			$parentModel = new static::$parentModelName();
			$customerModel = new CustomerModel();
			
			//check required fields.
			$requiredFields = array(
				$customerModel::$idclickObjectIdField,
				$currentModel::$idclickObjectIdField,
			);
			$this->checkFieldExists($requiredFields, $data);

			//get customer id.
			$data[$customerModel::$adwordsObjectIdField] =
						$this->getAdwordsCustomerId($data[$customerModel::$idclickObjectIdField]);
			if(empty($data[$customerModel::$adwordsObjectIdField]) || 
					$data[$customerModel::$adwordsObjectIdField] === FALSE){
				throw new Exception('customer has not synced yet.');	
			}
			
			//get current adwords object id if null
			if(empty($data[$currentModel::$adwordsObjectIdField])){
				$data[$currentModel::$adwordsObjectIdField] =
						$this->getCurrentAdwordsObjectId($data[$currentModel::$idclickObjectIdField]);
				if(empty($data[$currentModel::$adwordsObjectIdField]) || 
						$data[$currentModel::$adwordsObjectIdField] === FALSE){
					throw new Exception('current model has not synced yet.');	
				}
			}
			
			//get parent adwords object id.
			if(empty($data[$parentModel::$adwordsObjectIdField])){
				$data[$parentModel::$adwordsObjectIdField] = 
						$this->getParentAdwordsObjectId($data[$parentModel::$idclickObjectIdField]);
				if(empty($data[$parentModel::$adwordsObjectIdField]) || 
						$data[$parentModel::$adwordsObjectIdField] === FALSE){
					throw new Exception('parent has not synced yet.');	
				}
			}
			
			$currentManager = new static::$currentManagerName($data[$customerModel::$adwordsObjectIdField]);
			$result = $currentManager->update($data);
			Log::write("[notice] " . static::$moduleName . " with {$customerModel::$adwordsObjectIdField}". 
							" #{$data[$customerModel::$adwordsObjectIdField]} was updated.\n", __METHOD__);
			$currentModel->updateSyncStatus(SyncStatus::SYNCED, $data[$currentModel::$idclickObjectIdField]);
			return TRUE;
		} catch(Exception $e){
			Log::write("[warning] An error has occurred: {$e->getMessage()}\n", __METHOD__);
			return FALSE;
		}
	}
	
	/**
	 * Delete Adwords Object 
	 *
	 * Call AdwordsManager to update an adwords Object on specify customer's account.
	 *
	 * @param array $data: 
	 * @return boolean: TRUE on success, FALSE on failure.
	 */
	public function deleteAdwordsObject(array $data){
		try{
			$currentModel = new static::$currentModelName();
			$customerModel = new CustomerModel();
			
			//check required fields.
			$requiredFields = array(
				$customerModel::$idclickObjectIdField,
				$currentModel::$idclickObjectIdField,
				$currentModel::$adwordsObjectIdField,
			);
			$this->checkFieldExists($requiredFields, $data);		
			
			//get customer id.
			$data[$customerModel::$adwordsObjectIdField] =
						$this->getAdwordsCustomerId($data[$customerModel::$idclickObjectIdField]);
			if(empty($data[$customerModel::$adwordsObjectIdField]) || 
					$data[$customerModel::$adwordsObjectIdField] === FALSE){
				throw new Exception('customer with idclick_uid #'
							. $data[$customerModel::$idclickObjectIdField] .' has not synced yet.');	
			}
			
			$currentManager = new static::$currentManagerName($data[$customerModel::$adwordsObjectIdField]);
			$result = $currentManager->delete($data);
			Log::write("[notice] " . static::$moduleName . " with {$customerModel::$adwordsObjectIdField}". 
											" #{$currentAdwordsObjectId} was updated.\n", __METHOD__);
			$currentModel->updateSyncStatus(SyncStatus::SYNCED, $data[$currentModel::$idclickObjectIdField]);
			return TRUE;
		} catch(Exception $e){
			Log::write("[warning] An error has occurred: {$e->getMessage()}\n", __METHOD__);
			return FALSE;
		}
	}
	
	/**
	 * Get adwords customer id by idclick_uid
	 *
	 * @param integer $idclickUid
	 * @return integer on success, FALSE on failure
	 */
	protected function getAdwordsCustomerId($idclickUid){
		$customerModel = new CustomerModel();
		$customerInfo = $customerModel->getAdapteInfo($idclickUid);
		if($customerInfo === FALSE){
			return FALSE;
		} else {
			if($customerInfo[$customerModel::$syncStatusField] == SyncStatus::SYNCED &&
					$customerModel->isValidAdwordsId($customerInfo[$customerModel::$adwordsObjectIdField])){
				return $customerInfo[$customerModel::$adwordsObjectIdField];
			}
		}
	}
	
	/**
	 * Get parent adwords object id
	 *
	 * @param integer $parentIdclickObjectId
	 * @return integer on success, FALSE on failure
	 */
	protected function getParentAdwordsObjectId($parentIdclickObjectId){
		$parentModel = new static::$parentModelName();
		$parentInfo = $parentModel->getAdapteInfo($parentIdclickObjectId);
		if($parentInfo === FALSE){
			return FALSE;
		} else {
			if($parentModel->isValidAdwordsId($parentInfo[$parentModel::$adwordsObjectIdField])){
				return $parentInfo[$parentModel::$adwordsObjectIdField];
			} else {
				return FALSE;
			}
		}
	}
	
	/**
	 * Get current adwords object id
	 *
	 * @param integer $parentIdclickObjectId
	 * @return integer on success, FALSE on failure
	 */
	protected function getCurrentAdwordsObjectId($currentIdclickObjectId){
		$currentModel = new static::$currentModelName();
		$currentInfo = $currentModel->getAdapteInfo($currentIdclickObjectId);
		if($currentInfo === FALSE){
			return FALSE;
		} else {
			if($currentModel->isValidAdwordsId($currentInfo[$currentModel::$adwordsObjectIdField])){
				return $currentInfo[$currentModel::$adwordsObjectIdField];
			} else {
				return FALSE;
			}
		}
	}
	
	/**
	 * Build and send message to httpsqs queue.
	 *
	 * @param array $data
	 * @param callback $calback
	 * @return void.
	 */
	protected function sendMessage(array $data, $callback = null){
		$message = new Message();
		$message->setModule(static::$moduleName);
		$message->setAction($data['last_action']);
		$message->setInformation($data);

		$messageHandler = new MessageHandler();
		$messageHandler->put($message, $callback);
		unset($message, $messageHandler);//force release for logging path change in __destruct
	}

	/**
	 * Join array elements with comma.
	 *
	 * Returns a string containing a string representation of all the array elements in the same
	 * order, with the glue "," string between each element.
	 *
	 * @param array $array
	 * @return string
	 */
	protected static function arrayToString(array $array){
		return implode(',', $array);
	}

	protected static function arrayToSpecialString(array $array){
		return ':'.implode(',:', $array);
	}

	/**
	 * Generate process result.
	 */
	protected function generateResult(){
		if(ENVIRONMENT == 'development'){
			Log::write("[RETURN] Processed end with result:\n"
							. print_r($this->result, TRUE), __METHOD__);
		}
		if(RESULT_FORMAT == 'JSON'){
			return json_encode($this->result);
		} else {
			return $this->result;
		}
	}

	/**
	 * Check whether the $data array has all fields required.
	 */
	protected function checkFieldExists($requiredFields, array $data){
		foreach($requiredFields as $requiredField){
			if(empty($data[$requiredField])){
				throw new Exception('field #'.$requiredField.' is required.');
			}
		}
		return TRUE;
	}
	
	/**
	 * Check whether the data meets the requirements.
	 *
	 * According to the current module's dataCheckFilter, verify that the data is valid, while
	 * filtering out the fields prohibited.
	 *
	 * @return void.
	 */
	protected static function prepareData(&$data, $action){
		$filter = static::$dataCheckFilter[$action];
		foreach($filter['requiredFields'] as $item){
			if(!isset($data[$item])){
				if(ENVIRONMENT == 'development'){
					Log::write('[ERROR] Field #' . $item . ' is required.', __METHOD__);
				}
				throw new DataCheckException('[ERROR] Field #' . $item . ' is required.');
				break;
			}
		}
		foreach($filter['prohibitedFields'] as $item){
			if(isset($data[$item])){
				if(ENVIRONMENT == 'development'){
					Log::write('[WARNING] A prohibited fields found, Field #'
												. $item . ' Value #'. $data[$item], __METHOD__);
				}
				unset($data[$item]);
			}
		}
		foreach($data as $key => $item){
			if(is_array($item)){
				$data[$key] = self::arrayToString($item);
				if(ENVIRONMENT == 'development'){
					Log::write('[WARNING] Field #' . $key . ' Array to String conversion.', __METHOD__);
				}
			}

			//转换idclick数据格式为adwords数据格式
			if($key === 'bidding_type'){
				switch($data['bidding_type']){
					case '0': $data['bidding_type'] = 'BUDGET_OPTIMIZER'; break;
					case '1': $data['bidding_type'] = 'MANUAL_CPC'; break;
					default: throw new DataCheckException('unknown bidding_type ##'.$data['bidding_type']);
				}
			}

			if($key === 'budget_amount'){
				if($data['budget_amount'] < 1){
					throw new DataCheckException('budget_amount must greater or equal than 1.');
				}
			}
		}

		//add idclick_uid
		if(!isset($data['idclick_uid'])){
			if(isset($data['idclick_planid'])){
				$campaignModel = new CampaignModel();
				$campaignInfo = $campaignModel->getOne('idclick_uid', 'idclick_planid='.$data['idclick_planid']);
				$data['idclick_uid'] = $campaignInfo['idclick_uid'];
			} else if(isset($data['idclick_groupid'])){
				$adGroupModel = new AdGroupModel();
				$adGroupInfo = $adGroupModel->getOne('idclick_planid', 'idclick_groupid='.$data['idclick_groupid']);
				$data['idclick_planid'] = $adGroupInfo['idclick_planid'];		
				self::prepareData($data, $action);
			} 
		}
	}
}
