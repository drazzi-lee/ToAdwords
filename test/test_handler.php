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
$logFile = TOADWORDS_LOG_PATH . 'daemon_common.log';


$result = $httpsqs->gets($queueCommon);
$pos = $result['pos'];
$data = $result['data'];
if($data != 'HTTPSQS_GET_END' && $data != 'HTTPSQS_ERROR'){
	try{
		$dataDecode = json_decode($data, TRUE);
		if($dataDecode['module'] == 'Customer'){	
			$message = new Message($dataDecode['module'], $dataDecode['action'], $dataDecode['data']);		
			$messageHandler = new MessageHandler();		
			$messageHandler->handle($message);
			unset($dataDecode, $message, $messageHandler);
		}		
	} catch(MessageException $e) {
		Log::write($e->getMessage(), __METHOD__, $logFile);
	} catch(Exception $e){
		Log::write('捕捉到未定义异常：：'.$e->getMessage(), __METHOD__, $logFile);
	}
}