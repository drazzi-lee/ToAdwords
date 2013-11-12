<?php

/**
 * AdwordsBase.class.php
 *
 * Base Object to classes who operate Google Adwords Object by API.
 *
 * @author Li Pengfei
 * @email drazzi.lee@gmail.com
 * @version 1.0
 */

namespace ToAdwords\Adwords;

use ToAdwords\Util\Log;

class AdwordsBase{
	protected $user;
	protected static $moneyMultiples = 1000000;

	public function __construct(){
		// import init file from google library.
		require_once(TOADWORDS_ADWORDS_INITFILE);
		$this->user = new \AdWordsUser();
		if(ENVIRONMENT == 'development'){
			$this->user->LogAll();
		}
	}
	
	protected function getService($serviceName){
		return $this->user->GetService($serviceName, ADWORDS_VERSION);
	}
	
	protected function mappingStatus($status){
		return static::$statusMap[$status];
	}
	
	protected function isValidLanguageId($languageId){
		return $languageId >= 1000 && $languageId <= 1044;
	}
	
	protected function isValidLocationId($locationId){
		return $locationId >= 2004 && $locationId <= 9060922;
	}
}