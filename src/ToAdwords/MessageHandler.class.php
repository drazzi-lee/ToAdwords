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

use ToAdwords\CustomerAdapter;
use ToAdwords\CampaignAdapter;
use ToAdwords\AdGroupAdapter;
use ToAdwords\AdGroupAdAdapter;
use ToAdwords\Util\Log;
use ToAdwords\Util\Message;
use ToAdwords\Util\Httpsqs;
use ToAdwords\Exception\MessageException;
use ToAdwords\Definition\SyncStatus;

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

	/**
	 * 调用Google AdWords接口执行消息体
	 *
	 * @param \ToAdwords\Util\Message $message: 消息
	 * @param \ToAdwords\Util\Httpsqs $httpsqs: Httpsqs消息队列
	 * @return boolean
	 * @throws MessageException
	 */
	public function handle(Message $message, Httpsqs $httpsqs){
		if(ENVIRONMENT == 'development'){
			Log::write("[notice] try to handle new data:\n" . $message, __METHOD__);
		}

		$module = null;
		$result = null;

		switch($message->getModule()){
			case 'Customer':
				$module = new CustomerAdapter();
				break;
			case 'Campaign':
				$module = new CampaignAdapter();
				break;
			case 'AdGroup':
				$module = new AdGroupAdapter();
				break;
			case 'AdGroupAd':
				$module = new AdGroupAdAdapter();
				break;
			default:
				throw new MessageException('解析错误，不能识别的module::'.$message->getModule()
						.' 消息位置：'.$pos);
		}

		$currentModel = new $module::$currentModelName();

		//enter retry times greater than 5.
		if($message->errorCount > 5){
			$this->put($message, array($currentModel, 'updateSyncStatus'), HTTPSQS_QUEUE_DIE);
			throw new MessageException('[DIE]发送消息失败，消息内容：'.$message.' || 不再重试。');
		}

		$information = $message->getInformation();
		$currentModel->updateSyncStatus(SyncStatus::SENDING, $information[$currentModel::$idclickObjectIdField]);

		switch($message->getAction()){
			case 'CREATE':
				$result = $module->createAdwordsObject($information);
				break;
			case 'UPDATE':
				// @todo filter the unchanged data to optimize runtime.
				$result = $module->updateAdwordsObject($information);
				break;
			case 'DELETE':
				$result = $module->deleteAdwordsObject($information);
				break;
			default:
				throw new MessageException('解析错误，不能识别的action::'.$message->getAction()
						.' 消息位置：'.$pos);
		}

		if(FALSE === $result){
			$message->errorCount++;
			$this->put($message, array($currentModel, 'updateSyncStatus'), HTTPSQS_QUEUE_RETRY);
			throw new MessageException('发送消息失败，消息内容：'.$message.' || 已进入重试队列');
		}

		return $result;
	}

	/**
	 * 把消息放入指定的消息队列名，并执行回调方法
	 *
	 * @param \ToAdwords\Util\Message $message： 需要入队的消息
	 * @param type $callback： 回调函数
	 * @param type $queueName: 消息队列名称
	 * @return boolean
	 * @todo 在1.1版本中，计划添加消息至队列成功时，通过守护进程去启动这一功能。
	 */
	public function put(Message $message, $callback = null, $queueName = HTTPSQS_QUEUE_COMMON){
		if(!$message->check()){
			Log::write("[warning] message incomplete.\n" . $message, __METHOD__);
		}
		$message_combine = array(
				'module' 		=> $message->getModule(),
				'action' 		=> $message->getAction(),
				'data' 			=> $message->getInformation(),
				'errorCount' 	=> $message->errorCount,
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
					case HTTPSQS_QUEUE_DIE:
						call_user_func_array($callback, array('ERROR', $message->getPid()));
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

	/**
	 * 从指定的消息队列中取出一条消息。
	 *
	 * @param type $callback: 回调函数名
	 * @param type $queueName: 消息队列名
	 * @return \ToAdwords\Util\Message|boolean
	 */
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

	/**
	 * 呼叫发送消息的守护进程，如果守护进程不存在，则启动它
	 *
	 * @param type $queueName: 收到新消息的队列名。
	 * @return void.
	 * @note 计划在后续1.1版本中加入，暂不使用。
	 * @todo 本方法还未开发完成。
	 */
	private function callDaemon($queueName){
		//check the process of given queueName is running, if not run it.
		$key = null;
		switch($queueName){
			case HTTPSQS_QUEUE_COMMON:
				$key = 'HTTPSQS_QUEUE_COMMON_PROCESS';
				break;
			case HTTPSQS_QUEUE_RETRY:
				$key = 'HTTPSQS_QUEUE_RETRY_PROCESS';
				break;
			case HTTPSQS_QUEUE_DIE:
				$key = 'HTTPSQS_QUEUE_DIE_PROCESS';
				break;
			default:
				Log::write('[warning] queue name error: #'
						.$queueName.", call Daemon will be ignored:\n".$message, __METHOD__);
		}
		$sysStatusModel = new SysStatusModel();
		$sysStatusModel->getValue('HTTPSQS_QUEUE_COMMON_PROCESS');
	}

	/**
	 * 设置日志路径为原路径。
	 */
	public function __destruct(){
		Log::setPath($this->lastLogPath);
	}
}
