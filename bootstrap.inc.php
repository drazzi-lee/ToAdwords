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

define('ENVIRONMENT', 'development');
//define('ENVIRONMENT', 'production');

define('TOADWORDS_ROOT', __DIR__);
define('TOADWORDS_SRC', TOADWORDS_ROOT . DIRECTORY_SEPARATOR . 'src');
define('TOADWORDS_ADWORDS_INITFILE', TOADWORDS_ROOT . DIRECTORY_SEPARATOR . 'init.php');

//日志配置
define('TOADWORDS_LOG_PATH', TOADWORDS_ROOT . DIRECTORY_SEPARATOR . 'log'.DIRECTORY_SEPARATOR);
define('TOADWORDS_LOG_RECORD', TRUE);

//数据库配置
define('TOADWORDS_DSN', 'mysql:dbname=toadwords;host=127.0.0.1;charset=utf8');
define('TOADWORDS_USER', 'root');
define('TOADWORDS_PASS', '123456');

//消息队列配置
define('HTTPSQS_HOST', '10.0.2.19');
define('HTTPSQS_PORT', '1218');
define('HTTPSQS_AUTH', '123456');
define('HTTPSQS_CHARSET', 'utf-8');
define('HTTPSQS_QUEUE_COMMON', 'common');
define('HTTPSQS_QUEUE_RETRY', 'retry');
define('HTTPSQS_QUEUE_DIE', 'die');

//处理结果返回格式
//define('RESULT_FORMAT', 'ARRAY');
define('RESULT_FORMAT', 'JSON');

function load($namespace){
	$splitpath = explode('\\', $namespace);
	$path = TOADWORDS_SRC . DIRECTORY_SEPARATOR . 'ToAdwords';
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
		$fullpath = $path . DIRECTORY_SEPARATOR 
				. $name . '.class.php';
		return include_once($fullpath);
	}
	return false;
}

spl_autoload_register(__NAMESPACE__.'\load');