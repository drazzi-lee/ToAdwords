#!/usr/bin/php
<?php

require_once '../src/ToAdwords/bootstrap.inc.php';
use ToAdwords\Util\Log;
use ToAdwords\Util\Httpsqs;
use ToAdwords\Util\Message;
use ToAdwords\MessageHandler;

$httpsqs = new Httpsqs(HTTPSQS_HOST, HTTPSQS_PORT, HTTPSQS_AUTH);
$queue_common = HTTPSQS_QUEUE_COMMON;

while(true){
	$result = $httpsqs->gets($queue_common));
	$pos = $result['pos'];
	$data = $result['data'];
	if($data != 'HTTPSQS_GET_END' && $data != 'HTTPSQS_ERROR'){
		$data_decode = json_decode($data);		
		$message = new Message($data_decode['module'], $data_decode['action'], $data_decode['data']);
		
		$messageHandler = new MessageHandler();
		
		$messageHandler->handle($message);
		
		/* $module = null;
		switch($message['module']){
			case 'Customer':
				$module = new Customer();	
				break;
			case 'Campaign':
				$module = new Campaign();
				break;
			case 'AdGroup':
				$module = new AdGroup();
				break;
			case 'AdGroupAd':
				$module = new AdGroupAd();
				break;
			default:
				throw new Exception('不能识别的module::'.$message['module'].' 消息位置：'.$pos);
		}

		switch($message['action']){
			case 'CREATE':
				$result = $module->create($message['data']);
				break;
			case 'UPDATE':
				$result = $module->update($message['data']);
				break;
			case 'DELETE':
				$result = $module->delete($message['data']);
				break;
			default:
				throw new Exception('不能识别的action::'.$message['action'].' 消息位置：'.$pos);
		}

		//如果消息发送失败，则进入重试队列
		if(!$result){
			$message_retry = $message;
			$message_retry['error_count'] = 1;
			$httpsqs->put(HTTPSQS_QUEUE_RETRY, json_encode($message_retry));
			Log::write('发送消息失败，消息位置：'.$pos.' 消息内容：'
				.$data, __METHOD__, './daemon_common.log');
		} */

	} else {
		sleep(1);
	}
}

