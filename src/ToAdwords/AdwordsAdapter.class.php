<?php

namespace ToAdwords;

use ToAdwords\Util\Log;
use ToAdwords\Util\Message;
use ToAdwords\CustomerAdapter;
use ToAdwords\CampaignAdapter;
use ToAdwords\AdGroupAdapter;
use ToAdwords\AdGroupAdAdapter;
//use ToAdwords\Model\CustomerModel;
//use ToAdwords\Model\CampaignModel;
//use ToAdwords\Model\AdGroupModel;
//use ToAdwords\Model\AdGroupAdModel;
use ToAdwords\Exception\DataCheckException;
use ToAdwords\MessageHandler;
use ToAdwords\Definition\SyncStatus;
use ToAdwords\Definition\Operation;

use \Exception;

abstract class AdwordsAdapter{
	protected static $moduleName;
	protected static $adwordsObjectIdField;
	protected static $idclickObjectIdField;
	protected static $currentModelName;
	protected static $parentModelName;
	protected static $parentAdapterName;

	/**
	 * 处理结果
	 *
	 * $result = array(
	 *			'status'	=> 1,	//1：成功；0：失败或者部分失败
	 *									-1: 提供参数不完整或解析失败
	 *			'success'	=> 7,	//成功添加的内容计数
	 *			'failure'	=> 0	//如果status不为1，则failure有计数
	 *			'description'	=> 文字描述
	 * 		)
	 * @var array $result
	 */
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
		
		$currentModel = new self::$currentModelName();
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
		self::prepareData($data, Operation::CREATE);
		$parentModel = new static::$parentModelName();
		$parentInfo = $parentModel->getAdapteInfo($data[$parentModel::$idclickObjectIdField]);
		if(empty($parentInfo)){
			if(static::$parentAdapter == 'CustomerAdapter'){
				$parentAdapter = new static::$parentAdapterName();	
				$parentAdapter->create(array($parentModel::$idclickObjectIdField => $data[$parentModel::$idclickObjectIdField]));
			} else {
				throw new DependencyException('dependency error, parent module #' . get_class($parentModel) . ' not found.');
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

		$messageHandler = new MessageHandler();
		$messageHandler->put($message, array($currentModel, 'updateSyncStatus'));

		$this->result['description'] = static::$moduleName . 'create success!';

		return $this->generateResult();
	}

	public function update($data){
		//准备数据，检查数据完整性，过滤数据或者进行数据转换
		//更新数据
		//发送数据至消息队列

	}

	public function delete($data){
		//准备数据，检查数据完整性，过滤数据或者进行数据转换
		//更新数据
		//发送数据至消息队列
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
	protected function arrayToString(array $array){
		return implode(',', $array);
	}

	protected function arrayToSpecialString(array $array){
		return ':'.implode(',:', $array);
	}

	/**
	 * Generate process result.
	 */
	protected function generateResult(){
		$this->result['status'] = 1;
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
				$data[$key] = $this->arrayToString($item);
				if(ENVIRONMENT == 'development'){
					Log::write('[WARNING] Field #' . $key . ' Array to String conversion.', __METHOD__);
				}
			}

			//转换idclick数据格式为adwords数据格式
			if($key === 'bidding_type'){
				switch($data['bidding_type']){
					case '0': $data['bidding_type'] = 'MANUAL_CPC'; break;
					case '1': $data['bidding_type'] = 'BUDGET_OPTIMIZER'; break;
					default: throw new DataCheckException('unknown bidding_type ##'.$data['bidding_type']);
				}
			}
		}
	}
}
