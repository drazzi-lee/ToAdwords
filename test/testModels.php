<?php

include_once '../bootstrap.inc.php';

use ToAdwords\Model\CampaignModel;
use ToAdwords\Model\AdGroupModel;

use \PDOException;

if(false){
	$data = array(
			'idclick_planid' => 51601,
			'idclick_uid' => 523,
			'campaign_name' => 'campaign_name #' . uniqid(),
			'areas' => '10031,10032',
			'languages' => '10031,10032',
			'bidding_type' => 1,
			'budget_amount' => 20000.00,
			'delivery_method' => 'ACCELERATED',
			'max_cpc' => 10.00, 
			'campaign_status' => 'ACTIVE'
			);

	$updatedata = array(
			'campaign_name' => 'campaign_name #' . uniqid(),
			'budget_amount' => 20002.00,
			'delivery_method' => 'ACCELERATED',
			'max_cpc' => 10.00, 
			'campaign_status' => 'PAUSE'
			);

	$campaignModel = new CampaignModel();
	try{
		//$campaignModel->insertOne($data);
		$campaignModel->updateOne('idclick_planid=51601 AND idclick_uid=523', $updatedata);
		echo $campaignModel->getLastSql();
	} catch(PDOException $e){
		echo 'PDO Exception:'. $e->getMessage() . "\n";
		echo 'Details :' . $campaignModel->getLastSql() . "\n";
	}
}

if(true){
	$data = array(
			'idclick_groupid'	=> 123456,
			'idclick_planid'	=> 516587,
			'adgroup_name'		=> 'group_name',
			'keywords'			=> 'keywords1,keywords2'),
			'budget_amount'		=> 200.00,	
		);

	$updatedata = array(
			'adgroup_name'		=> 'group_name' . uniqid(),
			'budget_amount'		=> 202.00,	
	);
	$adGroup = new AdGroupModel();
	try{
		$adGroup->insertOne($data);
		echo $adGroup->getLastSql();
	} catch(PDOException $e){
		echo 'PDO Exception:'. $e->getMessage() . "\n";
		echo 'Details :' . $adGroup->getLastSql() . "\n";
	}
}
