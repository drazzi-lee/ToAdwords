<?php

require_once '../bootstrap.inc.php';
use ToAdwords\Util\Log;
use ToAdwords\Util\Httpsqs;
use ToAdwords\Util\Message;
use ToAdwords\MessageHandler;

use \Exception;

$httpsqs = new Httpsqs(HTTPSQS_HOST, HTTPSQS_PORT, HTTPSQS_AUTH);
$queueName = HTTPSQS_QUEUE_COMMON;
Log::setPath(TOADWORDS_LOG_PATH . 'daemon/'.$queueName . DIRECTORY_SEPARATOR);



$data = '{"module":"AdGroupAd","action":"CREATE","data":{"idclick_adid":"32","idclick_groupid":"28","ad_headline":"123\u5e7f\u544a","ad_description1":"11111111111","ad_description2":"22222222222222","ad_url":"http:\/\/www.baidu.com","ad_displayurl":"www.baidu.com","ad_status":"PAUSE","idclick_planid":"29","idclick_uid":"1026","last_action":"CREATE"},"errorCount":0}';
if($data != 'HTTPSQS_GET_END' && $data != 'HTTPSQS_ERROR'){
	$dataDecode = json_decode($data, TRUE);
	if($dataDecode === NULL){
		Log::write("[warning] message not valid:\n" . print_r($result, TRUE), 'QueueCommon::Get');
	} else {
		Log::write('[notice] new message: ' . print_r($dataDecode, TRUE), 'QueueCommon::Get');
	}
	try{
		$message = new Message();
		$message->setModule($dataDecode['module']);
		$message->setAction($dataDecode['action']);
		$message->setInformation($dataDecode['data']);

		$messageHandler = new MessageHandler();
		$handle_result = $messageHandler->handle($message, $httpsqs);
		unset($dataDecode, $message, $messageHandler);
		if(TRUE === $handle_result){
			Log::write('[notice] handle message success. position: #'.$position, 'QueueCommon::Get');
		} else {
			Log::write('[error] try to handle message failed. position: #'.$position, 'QueueCommon::Get');
		}
		unset($handle_result);
	} catch(Exception $e){
		unset($message, $messageHandler);
		Log::write('[error] ' . get_class($e) . ' ' . $e->getMessage(), 'QueueCommon::Get');
	}
} else {
	if(ENVIRONMENT == 'development'){
		Log::write("[notice] got nothing, current status:\n" . print_r($httpsqs->status($queueName), TRUE), 'QueueCommon::Get');
	}
	unset($result, $position, $data);
}
