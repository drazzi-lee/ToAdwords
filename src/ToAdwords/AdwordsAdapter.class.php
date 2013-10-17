<?php

namespace ToAdwords;

require_once 'Adapter.interface.php';

use ToAdwords\Adapter;
use ToAdwords\Util\Log;
use ToAdwords\Util\Message;
use ToAdwords\CustomerAdapter;
use ToAdwords\Exception\DataCheckException;
use ToAdwords\MessageHandler;
use ToAdwords\Definition\SyncStatus;

use \Exception;

abstract class AdwordsAdapter implements Adapter{

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

	public function create($data){
		//准备数据，检查数据完整性，过滤数据或者进行数据转换
		//判断上级信赖是否创建
		//插入数据
		//发送数据至消息队列
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
		if($this->result['status'] == -1){
			if(ENVIRONMENT == 'development'){
				Log::write("[RESULT_RETURN] Data check failure:\n"
								. print_r($this->result, TRUE), __METHOD__);
			}
			if(RESULT_FORMAT == 'JSON'){
				return json_encode($this->result);
			} else {
				return $this->result;
			}
		}

		$this->result['status'] = 1;
		if(ENVIRONMENT == 'development'){
			Log::write("[RESULT_RETURN] Processed end with result:\n"
							. print_r($this->result, TRUE), __METHOD__);
		}
		if(RESULT_FORMAT == 'JSON'){
			return json_encode($this->result);
		} else {
			return $this->result;
		}
	}

	/**
	 * check whether the data meets the requirements.
	 *
	 * according to the current module's datacheckfilter, verify that the data is valid, while
	 * filtering out the fields prohibited.
	 *
	 * @return void.
	 */
	protected function prepareData(&$data, $action){
		$filter = $this->datacheckfilter[$action];
		foreach($filter['prohibitedfields'] as $item){
			if(isset($data[$item])){
				if(environment == 'development'){
					Log::write('[SYSTEM_WARNING] a prohibited fields found, field #'
												. $item . ' value #'. $data[$item], __method__);
				}
				unset($data[$item]);
			}
		}
		foreach($filter['requiredFields'] as $item){
			if(!isset($data[$item])){
				if(ENVIRONMENT == 'development'){
					Log::write('[SYSTEM_WARNING] Field #' . $item . ' is required.', __METHOD__);
				}
				throw new DataCheckException('Field #' . $item . ' is required.');
				break;
			}
		}
	}
}
