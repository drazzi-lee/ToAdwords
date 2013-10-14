<?php

namespace ToAdwords\Util;

use ToAdwords\Exceptions\MessageException;

class Message{
	private static $operators = array('CREATE', 'UPDATE', 'DELETE');
	private static $modules = array('Customer', 'Campaign', 'AdGroup', 'AdGroupAd');
	
	private $module;
	private $action;
	private $information;
	private $needRecheck; //消息中暂不需要这一信息，方案是取出消息阶段自动对上级依赖关系进行检查。

	public function __construct(){

	}

	public function check(){
		return isset($this->module) && isset($this->action) && isset($this->information);
	}
	
	public function __toString(){
		return '[消息] 模块：'.$this->module.' | 动作：'.$this->action
						.' | 消息内容：'.print_r($this->information, true);
	}

	public function setModule($module){
		if(in_array($module, self::$modules)){			
			$this->module = $module;
		} else {
			throw new MessageException('构建消息错误：尚未支持的模块::'.$module);
		}
	}
	
	public function getModule(){
		return $this->module;
	}

	public function setAction($action){
		if(in_array($action, self::$operators)){
			$this->action = $action;
		} else {
			throw new MessageException('构建消息错误：尚未支持的动作::'.$action);
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
