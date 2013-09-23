<?php

include_once dirname(__FILE__)."/httpsqs_client.php";   
$httpsqs = new httpsqs('192.168.6.14', '1218', 'mypass123', 'utf-8');
while(true){
	$result = $httpsqs->gets('adwords');
	$pos = $result["pos"]; //当前队列消息的读取位置点
	$data = $result["data"]; //当前队列消息的内容
	if ($data != "HTTPSQS_GET_END" && $data != "HTTPSQS_ERROR"){
		//...去做应用操作...
		echo $data;
	} else {
		sleep(1); //暂停1秒钟后，再次循环
	}
}

function run($data){

}
