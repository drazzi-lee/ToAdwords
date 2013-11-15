<?php

/**
 * DbMysql.class.php
 *
 * Defines class DbMysql for Model use. 
 *
 * @author Li Pengfei
 * @email drazzi.lee@gmail.com
 * @version 1.0
 */

namespace ToAdwords\Model\Driver;

use \PDO;

/**
 * DbMysql, provide a single instance PDO. 
 */
class DbMysql{
	private static $objInstance; 

	/* 
	 * Class Constructor - Create a new database connection if one doesn't exist 
	 * Set to private so no-one can create a new instance via ' = new DB();' 
	 */ 
	private function __construct() {} 

	/* 
	 * Like the constructor, we make __clone private so nobody can clone the instance 
	 */ 
	private function __clone() {} 

	/* 
	 * Returns DB instance or create initial connection 
	 * @param 
	 * @return $objInstance; 
	 */ 
	public static function getInstance(  ) { 

		if(!self::$objInstance){ 
			self::$objInstance = new PDO(TOADWORDS_DSN, TOADWORDS_USER, TOADWORDS_PASS); 
			self::$objInstance->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE); 
			self::$objInstance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} 

		return self::$objInstance; 

	} # end method 
	
	/**
	 * Release pdo connection to mysql.
	 */
	public static function close() {
		self::$objInstance = null;
	} # end method 

	/* 
	 * Passes on any static calls to this class onto the singleton PDO instance 
	 * @param $chrMethod, $arrArguments 
	 * @return $mix 
	 */ 
	final public static function __callStatic( $chrMethod, $arrArguments ) { 

		$objInstance = self::getInstance(); 

		return call_user_func_array(array($objInstance, $chrMethod), $arrArguments); 

	} # end method 
}
