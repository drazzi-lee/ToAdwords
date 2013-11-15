<?php

require_once '../bootstrap.inc.php';
use ToAdwords\Util\Log;
use ToAdwords\Util\Httpsqs;
use ToAdwords\Util\Message;
use ToAdwords\MessageHandler;
use ToAdwords\Exceptions\MessageException;
use ToAdwords\Model\Driver\DbMysql;

use \Exception;

$httpsqs = new Httpsqs(HTTPSQS_HOST, HTTPSQS_PORT, HTTPSQS_AUTH);
$queueName = HTTPSQS_QUEUE_RETRY;
Log::setPath(TOADWORDS_LOG_PATH . 'daemon/'.$queueName . DIRECTORY_SEPARATOR);

Log::write('[notice] Start retry.php as daemon.', 'Common');
print("[notice] Start retry.php as daemon.\n");

$pid = pcntl_fork();
if($pid === -1){
	//'Process could not be forked.';
	Log::write('[error] Process could not be forked.', 'Common');
	print("[error] Process could not be forked.\n");
} else if($pid){
	//Parent return.
	Log::write('[notice] Parent process exit.', 'Retry::Parent');
	print("[notice] Retry::Parent process exit.\n");
	return TRUE;                        
} else {
	Log::write('[notice] Retry::Child process begin to run.', 'Retry::Child');
	print("[notice] Retry::Child process begin to run.\n");
	while(TRUE){
		try{
			$result = $httpsqs->gets($queueName);
		} catch(Exception $e){
			Log::write('[error] try to connect httpsqs server failed.');
		}
		$position = $result['pos'];
		$data     = $result['data'];

		if($data != 'HTTPSQS_GET_END' && $data != 'HTTPSQS_ERROR'){
			$dataDecode = json_decode($data, TRUE);
			if($dataDecode === NULL){
				Log::write("[warning] message not valid:\n" . print_r($result, TRUE), 'QueueCommon::Get');
				continue;
			} else {
				Log::write('[notice] new message: ' . print_r($result, TRUE), 'QueueCommon::Get');
			}
			try{
				$message = new Message();
				$message->setModule($dataDecode['module']);
				$message->setAction($dataDecode['action']);
				$message->setInformation($dataDecode['data']);
				$message->errorCount = $dataDecode['errorCount'];

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
			DbMysql::close();
		} else {
			if(ENVIRONMENT == 'development'){
				//Log::write("[notice] got nothing, current status:\n" . print_r($httpsqs->status($queueName), TRUE), 'QueueCommon::Get');
			}
			unset($result, $position, $data);
			sleep(30);
			//break; //use CuteDaemon to wake up later.
		}
	}
}