<?php

$start_time = microtime(TRUE);
require_once '../bootstrap.inc.php';

use ToAdwords\Util\AdwordsManager;

$adwordsManager = new AdwordsManager();
//$adwordsManager->createAccount();

$clientCustomerId = '953-736-3155';
//$adwordsManager->createCampaign($clientCustomerId);
$campaignId = '144239485';
//$adwordsManager->updateCampaign($clientCustomerId, $campaignId);
//$end_time = microtime(TRUE);
$adwordsManager->estimateKeywordsTraffic(array());
$end_time = microtime(TRUE);
echo "executed time: ".number_format(1000*($end_time-$start_time),2)."ms\n";
