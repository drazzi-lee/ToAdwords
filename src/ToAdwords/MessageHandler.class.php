<?php
namespace ToAdwords;

use ToAdwords\Object\Adwords\Customer;
use ToAdwords\Object\Adwords\Campaign;
use ToAdwords\Object\Adwords\AdGroup;
use ToAdwords\Object\Adwords\AdGroupAd;
use ToAdwords\Util\Log;
use ToAdwords\Util\Message;
use ToAdwords\Util\Httpsqs;
use ToAdwords\Exceptions\MessageException;

/**
 * 消息处理类
 *
 */
class MessageHandler{

	private $httpsqs;

	private $lastLogPath;

	public function __construct(){
		$this->httpsqs = new Httpsqs(HTTPSQS_HOST, HTTPSQS_PORT, HTTPSQS_AUTH);
		$this->lastLogPath = Log::getPath();
		Log::setPath(TOADWORDS_LOG_PATH . 'message' . DIRECTORY_SEPARATOR);
	}

	public function handle(Message $message, Httpsqs $httpsqs){
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
			throw new MessageException('消息还未设置完整，不能入队。');
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
						call_user_func_array($callback, array('QUEUE'));
						break;
					case HTTPSQS_QUEUE_RETRY:
						call_user_func_array($callback, array('RETRY'));
						break;
					default:
						throw new MessageException('[ERROR] 消息队列名错误，队列名：'
								.$queueName.', 消息体：'.$message);
				}
			}
			Log::write('[MESSAGE_PUT]「队列」#'.$queueName.' 「内容」#'.$message, __METHOD__);
			return TRUE;
		} else {
			throw new MessageException('[ERROR] 入队失败，请检查HTTPSQS服务器状态及参数配置。');
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
			Log::write('[MESSAGE_GET] 获取到消息，消息位置:'.$pos, __METHOD__);
			$dataDecode = json_decode($data, TRUE);
			try{
			$message = new Message();
			$message->setModule($dataDecode['module']);
			$message->setAction($dataDecode['action']);
			$message->setInformation($dataDecode['data']);
			Log::write('[MESSAGE_GET] 消息有效，「队列」#'.$queueName.' 「内容」#'.$message, __METHOD__);
			return $message;
			} catch(MessageException $e){
				Log::write('[MESSAGE_GET] 消息无效：'.$e->getMessage(), __METHOD__);		
			}
		} else {
			return FALSE;
		}
	}

	public function __destruct(){
		Log::setPath($this->lastLogPath);
	}
}
