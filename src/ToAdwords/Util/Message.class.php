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

	public function __construct($module, $action, array $information, array $needRecheck = array()){
		if(in_array($module, self::$modules)){			
			$this->module = $module;
		} else {
			throw new MessageException('尚未支持的模块::'.$module);
		}
		if(in_array($action, self::$operators)){
			$this->action = $action;
		} else {
			throw new MessageException('未被允许的操作::'.$action);
		}
		$this->information = $information;
		$this->needRecheck = $needRecheck;
	}
	
	public function __toString(){
		$info = print_r($this->information, true);
		return '[消息] 模块：'.$this->module.' | 动作：'.$this->action
							.' | 消息内容：'.$info;
	}
	
	public function put(){
		Log::write($this, __METHOD__);
		
		$message_combine = array(
				'module' 	=> $this->module,
				'action' 	=> $this->action,
				'data' 		=> $this->information,
				);
		$message = json_encode($message_combine);
		$httpsqs = new Httpsqs(HTTPSQS_HOST, HTTPSQS_PORT, HTTPSQS_AUTH);
		return $httpsqs->put(HTTPSQS_QUEUE_COMMON, $message);
	}
	
	public function getModule(){
		return $this->module;
	}
	
	public function getAction(){
		return $this->action;
	}
	
	public function getInformation(){
		return $this->information;
	}
	
	public function getNeedRecheck(){
		return $this->information;
	}
}
