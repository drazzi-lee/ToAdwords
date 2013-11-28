<?php

/**
 * SysStatusModel.class.php
 *
 * Defines class SysStatusModel, operatting table `sys_status`.
 *
 * @author Li Pengfei
 * @email drazzi.lee@gmail.com
 * @version 1.0
 */
namespace ToAdwords\Model;

use ToAdwords\Model\BaseModel;

/**
 * 本类暂时不可用，计划在1.1版本加入此功能
 */
class SysStatusModel extends BaseModel{
	protected static $tableName = 'sys_status';
	protected static $moduleName = 'SysStatus';

	public function getValue($key){
		$sysStatusRow = $this->getOne('name,value', 'name='.$key);
		return $sysStatusRow['value'];
	}
}
