<?php

/**
 * bootstrap.inc.php
 *
 * 配置ToAdwords模块初始选项
 * 
 * Li Pengfei <lipengfei@izptec.com>
 */
namespace ToAdwords;

if(!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50300){
	trigger_error('此ToAdwords扩展模块在低于5.3版本运行可能出现未知错误', E_USER_ERROR);
	die();
}

define('TOADWORDS_LOG_PATH', dirname(__FILE__).DIRECTORY_SEPARATOR.'Log'.DIRECTORY_SEPARATOR);
define('TOADWORDS_LOG_RECORD', TRUE);

function load($namespace){
	$splitpath = explode('\\', $namespace);
	$path = '';
	$name = '';
	$firstword = true;
	for ($i = 0; $i < count($splitpath); $i++) {
		if ($splitpath[$i] && !$firstword) {
			if ($i == count($splitpath) - 1)
				$name = $splitpath[$i];
			else
				$path .= DIRECTORY_SEPARATOR . $splitpath[$i];
		}
		if ($splitpath[$i] && $firstword) {
			if ($splitpath[$i] != __NAMESPACE__)
				break;
			$firstword = false;
		}
	}
	if (!$firstword) {
		$fullpath = __DIR__ . $path . DIRECTORY_SEPARATOR . $name . '.class.php';
		return include_once($fullpath);
	}
	return false;
}

function loadPath($absPath){
	return include_once($absPath);
}

spl_autoload_register(__NAMESPACE__.'\load');
