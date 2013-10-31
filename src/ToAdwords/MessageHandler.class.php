<?php

/**
 * MessageHandler.class.php
 *
 * Defines a class MessageHandler, handle messages including get & put operations with Httpsqs server.
 *
 * @author Li Pengfei
 * @email drazzi.lee@gmail.com
 * @version 1.0
 */
namespace ToAdwords;

use ToAdwords\Object\Adwords\Customer;
use ToAdwords\Object\Adwords\Campaign;
use ToAdwords\Object\Adwords\AdGroup;
use ToAdwords\Object\Adwords\AdGroupAd;
use ToAdwords\Util\Log;
use ToAdwords\Util\Message;
use ToAdwords\Util\Httpsqs;
use ToAdwords\Exception\MessageException;

class MessageHandler{

	private $httpsqs;

	private $lastLogPath;

	public function __construct(){
		try{
		$this->httpsqs = new Httpsqs(HTTPSQS_HOST, HTTPSQS_PORT, HTTPSQS_AUTH);
		$this->lastLogPath = Log::getPath();
		Log::setPath(TOADWORDS_LOG_PATH . 'message' . DIRECTORY_SEPARATOR);
		} catch(Exception $e){
			Log::setPath($this->lastLogPath);
			Log::write('[warning] httpsqs construct error, check your settings.', __METHOD__);
		}
	}

	public function handle(Message $message, Httpsqs $httpsqs){
		if(ENVIRONMENT == 'development'){
			Log::write("[notice] try to handle new data:\n" . $message, __METHOD__);
		}
		$module = null;
		$result = null;
	
		switch($message->getModule()){
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
				throw new MessageException('解析错误，不能识别的module::'.$message['module']
						.' 消息位置：'.$pos);
		}
		
		switch($message->getAction()){
			case 'CREATE':
				$result = $module->create($message->getInformation());
				break;
			case 'UPDATE':
				$result = $module->update($message->getInformation());
				break;
			case 'DELETE':
				$result = $module->delete($message->getInformation());
				break;
			default:
				throw new MessageException('解析错误，不能识别的action::'.$message['action']
						.' 消息位置：'.$pos);
		}
		
		if(!$result){
			$message_retry = array(
				'module'		=> $message->getModule(),
				'action'		=> $message->getAction(),
				'data'			=> $message->getInformation(),
				'error_count'	=> 1,
			);
			$httpsqs->put(HTTPSQS_QUEUE_RETRY, json_encode($message_retry));			
			throw new MessageException('发送消息失败，消息内容：'.$message.' || 已进入重试队列');
		}
	}
	
	public function put(Message $message, $callback = null, $queueName = HTTPSQS_QUEUE_COMMON){
		if(!$message->check()){
			throw new MessageException('message incomplete.');
		}
		$message_combine = array(
				'module' 	=> $message->getModule(),
				'action' 	=> $message->getAction(),
				'data' 		=> $message->getInformation(),
				);
		$messagePrepared = json_encode($message_combine);
		if($this->httpsqs->put($queueName, $messagePrepared)){
			if(is_callable($callback)){
				switch($queueName){
					case HTTPSQS_QUEUE_COMMON:
						call_user_func_array($callback, array('QUEUE', $message->getPid()));
						break;
					case HTTPSQS_QUEUE_RETRY:
						call_user_func_array($callback, array('RETRY', $message->getPid()));
						break;
					default:
						Log::write('[warning] queue name error: #'
								.$queueName.", the message will be ignored:\n".$message, __METHOD__);
				}
			}
			Log::write('[notice] new message put. #'.$queueName." content:\n".$message, __METHOD__);
			return TRUE;
		} else {
			Log::write("[warning] put message into queue failed, daemon process will handle it.\n" . $message, __METHOD__);
		}
	}

	public function get($callback = null, $queueName = HTTPSQS_QUEUE_COMMON){
		if(is_callable($callback)){
			call_user_func_array($callback, array('SENDING'));
		}

		$result = $this->httpsqs->gets($queueName);
		$pos = $result['pos'];
		$data = $result['data'];

		if($data != 'HTTPSQS_GET_END' && $data != 'HTTPSQS_ERROR'){
			Log::write('[notice] 获取到消息，消息位置:'.$pos, __METHOD__);
			$dataDecode = json_decode($data, TRUE);
			try{
			$message = new Message();
			$message->setModule($dataDecode['module']);
			$message->setAction($dataDecode['action']);
			$message->setInformation($dataDecode['data']);
			Log::write('[notice] 消息有效，「队列」#'.$queueName.' 「内容」#'.$message, __METHOD__);
			return $message;
			} catch(MessageException $e){
				Log::write('[warning] 消息无效：'.$e->getMessage(), __METHOD__);		
			}
		} else {
			return FALSE;
		}
	}

	public function __destruct(){
		Log::setPath($this->lastLogPath);
	}
}
