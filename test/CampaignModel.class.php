<?php

require_once('src/ToAdwords/bootstrap.inc.php');

use ToAdwords\CampaignAdapter;

/**
 * 广告系列数据模型 CampaignModel
 *
 * 此模型为虚拟类，模型实例化时并不产生实际数据连接。
 */
class CampaignModel{
	private CampaignAdapter $campaignAdapter;

	public function __construct(){
		$this->campaignAdapter = new CampaignAdapter();
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
	 *			'campaign_status'	=> 'ACTIVE',
	 * 		);
	 * @return array $result
	 */
	public function createCampaign(array $data){
		return $this->campaignAdapter->create($data);
	}
	
	/**
	 * 更新广告系列
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
	public function updateCampaign(array $data){
		return $this->campaignAdapter->update($data);
	}
	
	/**
	 * 删除广告系列
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
	public function deleteCampaign(array $data){
		return $this->campaignAdapter->delete($data);
	}
}
