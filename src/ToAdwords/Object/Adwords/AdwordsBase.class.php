<?php

namespace ToAdwords\Object\Adwords;
use ToAdwords\Object\Base;

abstract class AdwordsBase extends Base{

	/**
	 * 数据库操作相关设置
	 */
	protected $dbh = null;

	/**
	 * 此构造过程需要内部直接处理异常。
	 */
	public function __construct(){
		parent::__construct();
		try {
			$this->dbh = new PDO(TOADWORDS_DSN, TOADWORDS_USER, TOADWORDS_PASS);
			$this->dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			$this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e){
			if(ENVIRONMENT == 'development'){
				trigger_error('数据库连接错误，实例化'.__CLASS__.'失败。', E_USER_ERROR);
			} else {
				Log::write('数据库连接错误，实例化'.__CLASS__.'失败。', __METHOD__);
				trigger_error('A system error occurred.', E_USER_WARNING);				
			}
		} catch (Exception $e){
			if(ENVIRONMENT == 'development'){
				trigger_error('未知错误，实例化'.__CLASS__.'失败。', E_USER_ERROR);
			} else {
				Log::write('未知错误，实例化'.__CLASS__.'失败。', __METHOD__);
				trigger_error('A system error occurred.', E_USER_WARNING);
			}
		}
	}

}