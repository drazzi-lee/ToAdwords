<?php

require_once 'init.php';

class CampaignModel extends Model{
	/**
	 * 是否检查数据完整性
	 */
	const IS_CHECK_DATA = TRUE;
	
	/**
	 * 数据执行动作定义
	 */
	const ACTION_CREATE = 'CREATE';
	const ACTION_UPDATE = 'UPDATE';
	const ACTION_DELETE = 'DELETE';
	
	/**
	 * 数据同步状态定义 
	 */
	const SYNC_STATUS_RECEIVE = 'RECEIVE';
	const SYNC_STATUS_QUEUE = 'QUEUE';
	const SYNC_STATUS_SYNCED = 'SYNCED';
	const SYNC_STATUS_ERROR = 'ERROR';	
	
	/**
	 * 结果描述文字定义
	 */
	const DESC_DATA_CHECK_FAILURE = '提供数据不完整，请检查数据或设置IS_CHECK_DATA为FALSE';
	const DESC_DATA_PROCESS_SUCCESS = '成功处理了所有数据';
	const DESC_DATA_PROCESS_WARNING = '执行完毕，有部分数据未正常处理：：';
	
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
	private $result = array(
				'success'	=> 0,
				'failure'	=> 0,
			);
	private $processed = 0;

	/**
	 * 添加广告计划
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
	 *			'campaign_status'	=> 'ACTIVE',		//ACTIVE PAUSE DELETE	
	 * 		);
	 * @return array $result
	 */
	public function createCampaign(array $data){
		if(self::IS_CHECK_DATA && !$this->_checkData($data, self::ACTION_CREATE)){
			$this->result['status'] = -1;
			$this->result['description'] = self::DESC_DATA_CHECK_FAILURE;
			return $this->result;
		}
		
		$data['last_action'] = self::ACTION_CREATE;
		$data['customer_id'] = D('Adwords/User')->getCustomerId($data['idclick_uid']);
		$data['sync_status'] = self::SYNC_STATUS_RECEIVE;
		
		if($this->add($data)){
			$this->processed++;
			$this->result['success']++;
			if($this->_queuePut(self::ACTION_CREATE, $data)){
				$conditions = array (
					'idclick_planid' => $data ['idclick_planid'] 
				);
				$status = array (
					'sync_status' => self::SYNC_STATUS_QUEUE 
				);
				$this->where($conditions)->save($status);
			}
		} else {
			$this->processed++;
			$this->result['failure']++;
		}
		$this->addup_result();
		
		/**
		 * 同步Google Adwords
		 * 
		 * 仅限DEMO版需要，正式版需要从消息队列中获取数据。
		 */
		if(false){
			try{			
				$user = new AdwordsUser();
				$user->SetClientCustomerId($data['customer_id']);
				$user->LogAll();
				
				//暂不同步。
				$campaignId = $this->_createAdwordsCampaign($user, $data);
				if(!empty($campaignId)){
					$campaignAdwordsData = array(
								'campaign_id'	=> $campaignId,
								'sync_status'	=> self::SYNC_STATUS_SYNCED,
							);
					$this->where($conditions)->save($campaignAdwordsData);
				}		
			} catch (Exception $e) {
				Log::write('请求Google Adwords Api出错：' . $e->getMessage());
			}
		}
		
		return $this->result;
	}
	
	/**
	 * 更新广告计划
	 *
	 * @param array $data: 要添加的数据，数据结构为
	 * 		$data = array(
	 * 			'idclick_planid'	=> 12345,
	 * 			'idclick_uid'		=> 441,
	 * 			'campaign_name'		=> 'campaign_name',
	 *			'areas'				=> array(10031, 10032),
	 *			'languages'			=> array(10031, 10032),
	 *			'bidding_type'		=> 1,
	 *			'budget_amount'		=> 200.00,
	 *			'delivery_method'	=> 'ACCELERATED',
	 *			'max_cpc'			=> 2.00,
	 * 		);
	 * @return array $result
	 */
	public function updateCampaign(array $data){
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
		$newStatus['sync_status'] = self::SYNC_STATUS_RECEIVE;
		if(isset($data['areas'])){
			$newStatus['areas'] = $this->_arrayToString($data['areas']['positive']);
		}
		if(isset($data['languages'])){
			$newStatus['languages'] = $this->_arrayToString($data['languages']['positive']);
		}
		if(FALSE !== $this->where($conditions)->save($newStatus)){
			$this->processed++;
			$this->result['success']++;
		} else {
			$this->processed++;
			$this->result['failure']++;
		}
		$this->addup_result();
		return $this->result;
		
	}
	
	/**
	 * 
	 * @param unknown_type $data
	 */
	public function deleteCampaign(array $data){
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
					'sync_status'		=> self::SYNC_STATUS_RECEIVE,
				);
		
		if(FALSE !== $this->where($conditions)->save($newStatus)){
			$this->processed++;
			$this->result['success']++;
		} else {			
			$this->processed++;
			$this->result['failure']++;
		}
		$this->addup_result();
		return $this->result;		
	}
	
	//取得Adwords中的campaign_id
	public function getCampaignId(string $idclickPlanId){
		if(!$idclickPlanId){
			return NULL;
		}

		$condition = array(
			'idclick_planid' 	=> $idclickPlanId,
		);
		$campaignRow = $this->where($condition)->find();
		if(!empty($userRow['campaign_id'])){
			return $userRow['campaign_id'];
		//} else if(!empty($userRow)) {
		//	if(self::SYNC_STATUS_QUEUE == $userRow['sync_status']){
		//		return -2; //已在队列中
		//	}
		//	$campaignId = $this->_createAdwordsCampaign($uid);
		//	$data = array(
		//		'adwords_customerid'	=> $adwordsCustomerId,
		//		'last_action'			=> 'CREATE',
		//	);
		//	$result = $this->where($condition)->data($data)->save();
		//	if(!$result){
		//		Log::write('更新数据失败' . $this->getLastSql()
		//			. ' ## adwords_customerid: ' . $adwordsCustomerId);
		//	}
		//	return $adwordsCustomerId;
		} else {
			// $adwordsCustomerId = $this->_createCustomer($uid);
			// $data = array(
			// 	'idclick_uid'			=> $uid,
			// 	'adwords_customerid'	=> $adwordsCustomerId,
			// 	'last_action'			=> 'CREATE',
			// );
			// $result = $this->add($data);
			// if(!$result){
			// 	Log::write('添加数据失败' . $this->getLastSql()
			// 		. ' ## adwords_customerid: ' . $adwordsCustomerId);
			// }
			// return $adwordsCustomerId;
			return FALSE;
		}
	
	}
	
	private function _createAdwordsCampaign(AdWordsUser $user, array $data){
		// Create campaign.
		$campaign = new Campaign();
		$campaign->name = $data['campaign_name'];
		$campaign->status = 'ACTIVE';
		
		
		return $campaign->id;
	}
	
	/**
	 * Put message in queue.
	 * 
	 * @param string $action: self::ACTION_CREATE ACTION_UPDATE ACTION_DELETE
	 * @param array $data
	 */
	private function _queuePut($action, array $data) {
	
		// put data to queue.
		$message_combine = array(
					'module' 	=> 'CAMPAIGN',
					'action' 	=> $action,
					'data' 		=> $data,
				);
		$message = json_encode($message_combine);
		include_once 'httpsqs_client.php'; 
		$httpsqs = new httpsqs('192.168.6.14', '1218', 'mypass123', 'utf-8');
		return $httpsqs->put('adwords', $message);
	}
	
	/**
	 * Check whether the data comlete.
	 * 
	 * @param array $data
	 * @param string $process
	 * @return boolean
	 */
	private function _checkData(array $data, $process){
		if(empty($data) || empty($process)){
			return FALSE;
		}
		switch($process){
			case 'CREATE':
				if(empty($data['idclick_planid']) || empty($data['idclick_uid'])
						|| empty($data['campaign_name'])){
					return FALSE;
				}
				break;
			case 'UPDATE':
				if(empty($data['idclick_planid']) || empty($data['idclick_uid'])){
					return FALSE;
				}
				break;
			case 'DELETE':
				if(empty($data['idclick_planid'])){
					return FALSE;
				}
				break;
			default:
				return FALSE;
		}
		
		//...check data
		return TRUE;	
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
	private function _arrayToString(array $array){
		return implode(',', $array);
	}
	
	/**
	 * 整合执行结果
	 */
	protected function addup_result(){
		if($this->processed == $this->result['success']){
			$this->result['status'] = 1;
			$this->result['description'] = self::DESC_DATA_PROCESS_SUCCESS . "，共有{$this->result['success']}条。";
		} else {
			$this->result['status'] = 0;
			$this->result['description'] = self::DESC_DATA_PROCESS_WARNING . "，成功{$this->result['success']}条，失败{$this->result['failure']}条。";
		}
	}
	
}
