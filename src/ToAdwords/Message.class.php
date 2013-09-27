<?php

namespace ToAdwords;

use \Exception;

class Message{
	private static $operators = array('CREATE', 'UPDATE', 'DELETE');
	private static $modules = array('Customer', 'Campaign', 'AdGroup', 'AdGroupAd');
	
	private $module;
	private $action;
	private $information;
	private $needRecheck;

	public function __construct($module, $action, array $information, array $needRecheck = array()){
		if(in_array($module, self::$modules)){			
			$this->module = $module;
		} else {
			throw new Exception('尚未支持的模块::'.$module);
		}
		if(in_array($action, self::$operators)){
			$this->action = $action;
		} else {
			throw new Exception('未被允许的操作::'.$action);
		}
		$this->information = $information;
		$this->needRecheck = $needRecheck;
	}
	
	public function __toString(){
		return '[消息] 模块：'.$this->module.' | 动作：'.$this->action.' | 消息内容：'.var_dump($information);
	}
	
	public function put(){
		return TRUE;
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