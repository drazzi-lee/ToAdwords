<?php

$start_time = microtime(TRUE);
require_once '../bootstrap.inc.php';

use ToAdwords\RealtimeCall;

//$keywords = array('女式帽子', '秋季女帽', '冬季女帽');
$keywords = array('女帽');
$languages = array('1017');
$locations = array('2156');
RealtimeCall::estimateKeywordsTraffic($keywords, 100, 1000, $languages, $locations);
//Realtimecall::estimateKey();
$end_time = microtime(TRUE);
echo "executed time: ".number_format(1000*($end_time-$start_time),2)."ms\n";
