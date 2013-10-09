#!/usr/bin/php
<?php

require_once '../bootstrap.inc.php';
use ToAdwords\Util\Httpsqs;

$httpsqs = new Httpsqs(HTTPSQS_HOST, HTTPSQS_PORT, HTTPSQS_AUTH);
$queue_retry = HTTPSQS_QUEUE_RETRY;

while(true){
	$result = $httpsqs->gets($queue_retry));
	$pos = $result['pos'];
	$data = $result['data'];
	if($data != 'HTTPSQS_GET_END' && $data != 'HTTPSQS_ERROR'){
		//...
		$message = json_decode($data);
		//...

	} else {
		sleep(1);
	}
}

