<?php
require_once '../src/ToAdwords/bootstrap.inc.php';

use ToAdwords\Util\Log;
use ToAdwords\Util\Httpsqs;
use ToAdwords\Util\Message;
use ToAdwords\MessageHandler;
use ToAdwords\Exceptions\MessageException;

use \Exception;

$httpsqs = new Httpsqs(HTTPSQS_HOST, HTTPSQS_PORT, HTTPSQS_AUTH);
$queue_common = HTTPSQS_QUEUE_COMMON;


$result = $httpsqs->gets($queue_common);
$pos = $result['pos'];
$data = $result['data'];
if($data != 'HTTPSQS_GET_END' && $data != 'HTTPSQS_ERROR'){
	try{
		$data_decode = json_decode($data, TRUE);
		if($data_decode['module'] == 'Customer'){
			$message = new Message($data_decode['module'], $data_decode['action'], $data_decode['data']);		
			$messageHandler = new MessageHandler();		
			$messageHandler->handle($message);
			unset($data_decode, $message, $messageHandler);
		}		
	} catch(MessageException $e) {
		Log::write($e->getMessage(), __METHOD__, './daemon_common.log');
	} catch(Exception $e){
		Log::write('捕捉到未定义异常：：'.$e->getMessage(), __METHOD__, './daemon_common.log');
	}
}