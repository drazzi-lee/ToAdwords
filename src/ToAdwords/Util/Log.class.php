<?php

namespace ToAdwords\Util;

class Log{
	private static $logPath = TOADWORDS_LOG_PATH;
	public static function write($message, $method, $destination = ''){
		$now = date('[ c ]');
		if(empty($destination))
			$destination = self::$logPath . date('y_m_d').'.log';
		if(is_file($destination) && floor(2097131) <= filesize($destination))
			rename($destination, dirname($destination) . DIRECTORY_SEPARATOR 
									. basename($destination) . '-' . time());
		error_log("{$now} #{$method}# {$message}\n", 3, $destination);
	}
	
	public static function setPath($path){
		if(!file_exists($path)){
			mkdir($path);
		}
		if(is_dir($path)){
			self::$logPath = $path;
			return TRUE;
		}
		return FALSE;
	}
	
	public static function getPath(){
		return self::$logPath;
	}
}
