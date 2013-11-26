<?php

namespace ToAdwords\Model;

use ToAdwords\Model\BaseModel;

class SysStatusModel extends BaseModel{
	protected static $tableName = 'sys_status';
	protected static $moduleName = 'SysStatus';
	
	public function getValue($key){
		$sysStatusRow = $this->getOne('name,value', 'name='.$key);
		return $sysStatusRow['value'];
	}
}
