<?php
require_once '../bootstrap.inc.php';

use ToAdwords\Util\Log;
use ToAdwords\Util\Httpsqs;
use ToAdwords\Util\Message;
use ToAdwords\MessageHandler;
use ToAdwords\Exceptions\MessageException;

use \Exception;

$httpsqs = new Httpsqs(HTTPSQS_HOST, HTTPSQS_PORT, HTTPSQS_AUTH);
$queueCommon = HTTPSQS_QUEUE_COMMON;
Log::setPath(TOADWORDS_LOG_PATH . 'daemon_common' . DIRECTORY_SEPARATOR);

$result = $httpsqs->gets($queueCommon);
$pos = $result['pos'];
$data = $result['data'];
if($data != 'HTTPSQS_GET_END' && $data != 'HTTPSQS_ERROR'){
	try{
		$dataDecode = json_decode($data, TRUE);
		Log::write('得到消息内容：'.print_r($dataDecode, TRUE), __METHOD__);
		if($dataDecode['module'] == 'Customer'){	
			$message = new Message($dataDecode['module'], $dataDecode['action'], $dataDecode['data']);		
			$messageHandler = new MessageHandler();		
			$messageHandler->handle($message);
			unset($dataDecode, $message, $messageHandler);
		}		
	} catch(MessageException $e) {
		Log::write('[ERROR]消息错误'.$e->getMessage(), __METHOD__);
	} catch(Exception $e){
		Log::write('[ERROR]捕捉到未定义异常：：'.$e->getMessage(), __METHOD__);
	}
}