<?php

$start_time = microtime(TRUE);
require_once '../bootstrap.inc.php';

use ToAdwords\Util\AdwordsManager;

$adwordsManager = new AdwordsManager();
//$adwordsManager->createAccount();

//$clientCustomerId = '953-736-3155';
//$adwordsManager->createCampaign($clientCustomerId);
//$campaignId = '144239485';
//$adwordsManager->updateCampaign($clientCustomerId, $campaignId);

//测试创建广告计划
if(false){
	$clientCustomerId = '5436784026';
	$data = array(
			'campaign_name'		=> '彩虹手机套餐',
			'areas'				=> '20171,20163',
			'languages'			=> '1000,1017,1018',
			'bidding_type'		=> 'MANUAL_CPC',
			'budget_amount'		=> '53',
			'delivery_method'	=> 'ACCELERATED',
			'max_cpc'			=> '0',
			'campaign_status'	=> 'ACTIVE',
			);
	$adwordsManager->createCampaign($clientCustomerId, $data);
}

//测试更新广告计划的语言、区域
if(true){
	$clientCustomerId = '5436784026';
	$campaignId = '140734278';
	$languages = array('1000','1017');
	$adwordsManager->setCampaignTargetingCriteria($clientCustomerId, $campaignId, $languages, 'LANGUAGE');	
}

$end_time = microtime(TRUE);
echo "executed time: ".number_format(1000*($end_time-$start_time),2)."ms\n";
