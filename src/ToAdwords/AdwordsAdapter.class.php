<?php

namespace ToAdwords;

use ToAdwords\Util\Log;
use ToAdwords\Util\Message;
use ToAdwords\CustomerAdapter;
use ToAdwords\CampaignAdapter;
use ToAdwords\AdGroupAdapter;
use ToAdwords\AdGroupAdAdapter;
use ToAdwords\Exception\DataCheckException;
use ToAdwords\Exception\DependencyException;
use ToAdwords\MessageHandler;
use ToAdwords\Definition\Operation;

use \Exception;

abstract class AdwordsAdapter{
	protected static $moduleName;
	protected static $adwordsObjectIdField;
	protected static $idclickObjectIdField;
	protected static $currentModelName;
	protected static $parentModelName;
	protected static $parentAdapterName;

	protected $result = array(
				'status'		=> null,
				'description'	=> null,
				'success'		=> 0,
				'failure'		=> 0,
			);
	protected $processed = 0;

	public function run(array $data){
		if(ENVIRONMENT == 'development'){
			Log::write("Received new data:\n" . print_r($data, TRUE), __METHOD__);
		}
		
		$currentModel = new static::$currentModelName();
		if(empty($data[$currentModel::$idclickObjectIdField])){
			throw new DataCheckException('Field #'.$currentModel::$idclickObjectIdField.' is required.');
		}
		$row = $currentModel->getAdapteInfo($data[$currentModel::$idclickObjectIdField]);
		if(!empty($row)){
			return $this->update($data);
		} else {
			return $this->create($data);
		}
	}

	public function create(array $data){
		try{
			if(ENVIRONMENT == 'development'){
				Log::write("Received new data:\n" . print_r($data, TRUE), __METHOD__);
			}
			self::prepareData($data, Operation::CREATE);
			$parentModel = new static::$parentModelName();
			$parentInfo = $parentModel->getAdapteInfo($data[$parentModel::$idclickObjectIdField]);
			if($parentInfo === FALSE){
				if(static::$parentAdapterName == 'ToAdwords\CustomerAdapter'){
					$parentAdapter = new static::$parentAdapterName();	
					$parentAdapter->create(array($parentModel::$idclickObjectIdField => $data[$parentModel::$idclickObjectIdField]));
				} else {
					throw new DependencyException('dependency error, parent module #' . get_class($parentModel) . ' not found. parentObjectId #'.$data[$parentModel::$idclickObjectIdField]);
				}
			} else {
				if($parentModel->isValidAdwordsId($parentInfo[$parentModel::$adwordsObjectIdField])){
					$data[$parentModel::$adwordsObjectIdField] = $parentInfo[$parentModel::$adwordsObjectIdField];
				}
			}

			$currentModel = new static::$currentModelName();
			$currentModel->insertOne($data);

			$message = new Message();
			$message->setModule(static::$moduleName);
			$message->setAction(Operation::CREATE);
			$message->setInformation($data);

			$messageHandler = new MessageHandler();
			$messageHandler->put($message, array($currentModel, 'updateSyncStatus'));
			unset($message);
			unset($messageHandler);

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

	public function update($data){
		try{
			if(ENVIRONMENT == 'development'){
				Log::write("Received new data:\n" . print_r($data, TRUE), __METHOD__);
			}
			self::prepareData($data, Operation::UPDATE);

			$parentModel = new static::$parentModelName();
			$currentModel = new static::$currentModelName();
			$currentRow = $currentModel->getOne($currentModel::$idclickObjectIdField . ',' . $parentModel::$idclickObjectIdField, $currentModel::$idclickObjectIdField . '=' . $data[$currentModel::$idclickObjectIdField]);
			if(empty($currentRow)){
				throw new DataCheckException(get_class($currentModel) . ' could not find, ' . $currentModel::$idclickObjectIdField . ' #' . $data[$currentModel::$idclickObjectIdField]);
			} else if($currentRow[$parentModel::$idclickObjectIdField] != $data[$parentModel::$idclickObjectIdField]){
				throw new DataCheckException('Field #' . $parentModel::$idclickObjectIdField . ' does not match.');
			}

			$data['last_action'] = isset($data['last_action']) ? $data['last_action'] : Operation::UPDATE;
			$conditions = $currentModel::$idclickObjectIdField . '=' . $data[$currentModel::$idclickObjectIdField];
			$conditions .= ' AND ' . $parentModel::$idclickObjectIdField . '=' .$data[$parentModel::$idclickObjectIdField];
			$conditionsArray = array(
				$currentModel::$idclickObjectIdField 	=> $data[$currentModel::$idclickObjectIdField],
				$parentModel::$idclickObjectIdField 	=> $data[$parentModel::$idclickObjectIdField],
				);
			$newData = array_diff_key($data, $conditionsArray);
			$currentModel->updateOne($conditions, $newData);

			$message = new Message();
			$message->setModule(static::$moduleName);
			$message->setAction($data['last_action']);
			$message->setInformation($data);

			$messageHandler = new MessageHandler();
			$messageHandler->put($message, array($currentModel, 'updateSyncStatus'));
			unset($message);
			unset($messageHandler);

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

	public function delete($data){
		if(ENVIRONMENT == 'development'){
			Log::write("Received new data:\n" . print_r($data, TRUE), __METHOD__);
		}

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
		}
	}
}
