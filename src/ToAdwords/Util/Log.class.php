<?php

namespace ToAdwords\Util;

class Log{
	static function write($message, $method, $destination = ''){
		$now = date('[ c ]');
		if(empty($destination))
			$destination = TOADWORDS_LOG_PATH.date('y_m_d').'.log';
		if(is_file($destination) && floor(2097131) <= filesize($destination))
			rename($destination, dirname($destination).DIRECTORY_SEPARATOR.basename($destination).'-'.time());
		error_log("{$now} #{$method}# {$message}\r\n", 3, $destination);
	}
}
