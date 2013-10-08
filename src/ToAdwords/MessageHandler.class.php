<?php
namespace ToAdwords;

use ToAdwords\Object\Adwords\Customer;
use ToAdwords\Object\Adwords\Campaign;
use ToAdwords\Object\Adwords\AdGroup;
use ToAdwords\Object\Adwords\AdGroupAd;
use ToAdwords\Util\Log;
use ToAdwords\Util\Message;
use ToAdwords\Exceptions\MessageException;

/**
 * 消息处理类
 *
 */
class MessageHandler{
	

	public function handle(Message $message){
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
			$message_retry = $message;
			$message_retry['error_count'] = 1;
			$httpsqs->put(HTTPSQS_QUEUE_RETRY, json_encode($message_retry));			
			throw new MessageException('发送消息失败，消息位置：'.$pos.' 消息内容：'
						.$data.' || 已进入重试队列');
		}
	}
	
	public function call(){
	
	
	}
}