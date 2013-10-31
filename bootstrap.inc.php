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
define('TOADWORDS_PASS', 'qjklw');

//消息队列配置
define('HTTPSQS_HOST', '10.0.2.19');
define('HTTPSQS_PORT', '1218');
define('HTTPSQS_AUTH', '123456');
define('HTTPSQS_CHARSET', 'utf-8');
define('HTTPSQS_QUEUE_COMMON', 'common');
define('HTTPSQS_QUEUE_RETRY', 'retry');

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

/**
 * 笔记：
 *
 * 1、创建过程：
 *		A. 查询上级依赖是否已在数据库创建？
 *			1）还未在数据库创建：
 *				a. 在数据库中创建上级依赖元素，并发送创建本元素的动作至消息队列，
 *				直接返回创建结果: 创建成功则继续，否则直接中断返回；然后同样创建
 *				本元素，并发送创建本元素的动作至消息队列，直接返回创建结果: 创建
 *				成功则继续，否则直接中断返回。
 *			2）已在数据库创建，查询上级依赖是否已在GOOGLE创建？
 *				a. 已创建，获取上级依赖ID，在数据库中创建本元素，并发送创建本元素
 *  			的动作至消息队列，返回结果；
 *				b. 未创建，已进入队列，在数据库中创建本元素，并发送创建本元素的动
 *      		作至消息队列。消息取出过程会自动重新取父级依赖ID
 *				c. 未创建，未进入队列，记录信息异常，根据实际情况选择是否补充发送
 *				有关动作进入消息队列。
 *	2、更新过程：
 *		A. 查询本元素是否已在数据库创建？
 *			1）还未创建：
 *				a. 中断返回，异常DataCheckException；
 *			2）已创建，发送更新消息动作，消息结构体不需要包含实际消息。（消息被取
 *			出的执行阶段直接从数据库中获取最新本元素信息，如果已删除则不需要再执行
 *			消息）
 *	3、删除过程：
 *		同更新过程，消息取出时直接删除。考虑一下删除过程单独使用一个消息队列。存在
 *		风险？
 *
 * 依赖判断：
		取出消息首先对父级依赖进行判断，如父级依赖为空，则重新取数据，再执行消息体
		如果重新仍未取得父级依赖，则重新发送消息至重试消息队列。
		
  ###消息队列： 暂为两个，一个为正常消息队列，另一个为重试消息队列
  
  
  ###广告组、广告创建时，会对idclick层面的父级ID进行是否添加认证，如果未添加，则会
	要求添加
 *
 */
