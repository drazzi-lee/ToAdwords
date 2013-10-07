#!/usr/bin/php

<?php

include '';
include 'httpsqs_client.php';

$httpsqs = new httpsqs($host, $port, $auth, $charset);

$queue_name = 'common';

while(true){
	$result = $httpsqs->gets($name);	
	$pos = $result['pos'];
	$data = json_decode($result['data']);

	if($data != 'HTTPSQS_GET_END' && $data != 'HTTPSQS_ERROR'){
		//
	} else {
		sleep(1);
	}
	
}
