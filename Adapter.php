<?php

namespace ToAdwords;

abstract class Adapter {
	
	/**
	 * 是否检查数据完整性
	 */
	const IS_CHECK_DATA = TRUE;
	
	/**
	 * 数据执行动作定义
	 */
	const ACTION_CREATE = 'CREATE';
	const ACTION_UPDATE = 'UPDATE';
	const ACTION_DELETE = 'DELETE';
	
	/**
	 * 数据同步状态定义 
	 */
	const SYNC_STATUS_RECEIVE = 'RECEIVE';
	const SYNC_STATUS_QUEUE = 'QUEUE';
	const SYNC_STATUS_SYNCED = 'SYNCED';
	const SYNC_STATUS_ERROR = 'ERROR';	
	
	/**
	 * 结果描述文字定义
	 */
	const DESC_DATA_CHECK_FAILURE = '提供数据不完整，请检查数据或设置IS_CHECK_DATA为FALSE';
	const DESC_DATA_PROCESS_SUCCESS = '成功处理了所有数据';
	const DESC_DATA_PROCESS_WARNING = '执行完毕，有部分数据未正常处理：：';
	
	protected static $currentAction;
	protected static $result = array(
				'status'		=> null,
				'description'	=> null,
				'success'		=> 0,
				'failure'		=> 0
			);
	public static $moduleName;
	
	public function __construct(){
		self::$moduleName = get_class($this);
	}
	
	public function create(){
		
	}
	
	public function update(){
		
	}
	
	public function delete(){
		
	}
	
	protected function getResult(){
		
	}
}

?>