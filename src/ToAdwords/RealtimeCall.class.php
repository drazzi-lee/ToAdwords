<?php

/**
 * RealtimeCall.class.php
 *
 * Provide methods to operate Google Adwords Object by API.
 *
 * @author Li Pengfei
 * @email drazzi.lee@gmail.com
 * @version 1.0
 */
namespace ToAdwords;

use ToAdwords\Util\AdwordsManager;

class RealtimeCall{

	static public function estimateKeywordsTraffic($keywords, $maxCpc, $budget, $languages, $locations){
		$adwordsManager = new AdwordsManager();		
		return $adwordsManager->estimateKeywordsTraffic($keywords, $maxCpc, $budget, $languages, $locations);
	}

	static public function estimateKey(){
		$adwordsManager = new AdwordsManager();
		return $adwordsManager->estimateKey();
	}
}
