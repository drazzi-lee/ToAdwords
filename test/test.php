<?php

include 'CampaignModel.class.php';
include 'GroupadModel.class.php';
include 'GroupModel.class.php';

class TestAction{
	public function __construct(){
		header('Content-Type: text/html;charset=utf-8');
		header("Cache-Control: private, no-cache, no-store, proxy-revalidate, no-transform");
		echo '<pre>';
	}
	
	public function index(){		
		echo $campaignModel->createCampaign('441', null, null);
	}
	
	public function createCampaign(){
		$data = array(
				'idclick_planid' => 61634,
				'idclick_uid'	=> 503,
				'campaign_name' => 'campaign_name #' . uniqid(),
				'areas' => '2156',
				'languages' => '1017,1018',
				'bidding_type' => 0,
				'budget_amount' => 20000.00,
				'delivery_method' => 'ACCELERATED',
				'max_cpc' => 10.00, 
				'campaign_status' => 'ACTIVE'
		);
		$campaignModel = new CampaignModel();
		var_dump($campaignModel->createCampaign($data));
	}
	
	public function deleteCampaign(){
		$data = array (
				'idclick_planid' => 61634,
				'idclick_uid'		=> 502,
		);
		$campaignModel = new CampaignModel();
		var_dump($campaignModel->deleteCampaign($data));
	}
	
	public function updateCampaign(){
		$data = array (
				'idclick_planid' => 61634,
				'idclick_uid'		=> 503,
				'budget_amount'		=> 32334,
				'campaign_status'	=> 'ACTIVE',
		);
		$campaignModel = new CampaignModel();
		var_dump($campaignModel->updateCampaign($data));
	}
	
	public function createAdgroup(){
		$data = array(
	    	'idclick_groupid'	=> 123460,
			'idclick_planid'	=> 61634,
        	'adgroup_name'		=> 'group_name123460',
        	'keywords'			=> array('keywords1', 'keywords2'),
        	'max_cpc'		=> 200.00,	
			'adgroup_status'	=> 'ACTIVE',
        );
		$group = new GroupModel();
		var_dump($group->createAdgroup($data));
	}
	
	public function updateAdgroup(){
		$data = array(
	    	'idclick_groupid'	=> 123460,
			'idclick_planid'	=> 61634,
        	'adgroup_name'		=> 'group_name2602',
        	'keywords'			=> array('keywords3', 'keywords2', 'keywords1'),
        	'max_cpc'		=> 201.00,
        );
		$group = new GroupModel();
		var_dump($group->updateAdgroup($data));
	}
	
	public function deleteAdgroup(){
		$data = array(
	    	'idclick_groupid'	=> 123456,
        );
		$group = new GroupModel();
		var_dump($group->deleteAdgroup($data));
	}
	
	public function createAd(){
		$data = array(
			'idclick_adid'		=> 12348,
			'idclick_groupid'	=> 123459, 
			'ad_headline'		=> 'headline',
			'ad_description1'	=> 'description1',
			'ad_description2'	=> 'description2',
			'ad_url'			=> 'http://www.izptec.com/go.php',
			'ad_displayurl'		=> 'http://www.izptec.com/',
			'ad_status'			=> 'PAUSE'
		);
		$groupAd = new GroupadModel();
		var_dump($groupAd->createAd($data));
	}
	
	public function updateAd(){
		$data = array(
			'idclick_adid'		=> 12348,
			'idclick_groupid'	=> 123459, 
			'ad_headline'		=> 'headline——l',
			'ad_description1'	=> 'description1....',
			'ad_description2'	=> 'description2....',
			'ad_url'			=> 'http://www.izptec.com/go1.php',
			'ad_status'			=> 'ACTIVE',
		);
		$groupAd = new GroupadModel();
		var_dump($groupAd->updateAd($data));
	}
	
	public function deleteAd(){
		$data = array(
			'idclick_adid'		=> 12345,
		);
		$groupAd = new GroupadModel();
		var_dump($groupAd->deleteAd($data));
	}
}
$testAction = new TestAction();
$testAction->updateAd();
