<?php

namespace ToAdwords\Util;

use \Exception;
use ToAdwords\Util\Log;
use ToAdwords\Util\Httpsqs;

class Message{
	private static $operators = array('CREATE', 'UPDATE', 'DELETE');
	private static $modules = array('Customer', 'Campaign', 'AdGroup', 'AdGroupAd');
	
	private $module;
	private $action;
	private $information;
	private $needRecheck; //消息中暂不需要这一信息，方案是取出消息阶段自动对上级依赖关系进行检查。

	public function __construct(){

	}
	
	public function __toString(){
		return '[消息] 模块：'.$this->module.' | 动作：'.$this->action
						.' | 消息内容：'.print_r($this->information, true);
	}

	public function put($queueName = HTTPSQS_QUEUE_COMMON, $callback = null){
		Log::write($this, __METHOD__);
		
		$message_combine = array(
				'module' 	=> $this->module,
				'action' 	=> $this->action,
				'data' 		=> $this->information,
				);
		$message = json_encode($message_combine);
		$httpsqs = new Httpsqs(HTTPSQS_HOST, HTTPSQS_PORT, HTTPSQS_AUTH);
		if($httpsqs->put($queueName, $message)){
			if(is_callable($callback))
				call_user_func($callback, $queueName);
		}
	}

	public function get($queueName = HTTPSQS_QUEUE_COMMON, $callback = null){
		$httpsqs = new Httpsqs(HTTPSQS_HOST, HTTPSQS_PORT, HTTPSQS_AUTH);
		call_user_func($callback, 'SENDING');
		return json_decode($httpsqs->get($queueName), TRUE);
	}

	public function setModule($module){
		if(in_array($module, self::$modules)){			
			$this->module = $module;
		} else {
			throw new MessageException('尚未支持的模块::'.$module);
		}
	}
	
	public function getModule(){
		return $this->module;
	}

	public function setAction($action){
		if(in_array($action, self::$operators)){
			$this->action = $action;
		} else {
			throw new MessageException('尚未支持的动作::'.$action);
		}
	}
	
	public function getAction(){
		return $this->action;
	}

	public function setInformation(array $information){
		$this->information = $information;
	}
	
	public function getInformation(){
		return $this->information;
	}
	
	public function setNeedRecheck(array $needRecheck){
		$this->needRecheck = $needRecheck;
	}

	public function getNeedRecheck(){
		return $this->information;
	}
}
