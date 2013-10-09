<?php

namespace ToAdwords\Object\Adwords;

require_once('init.php');

use ToAdwords\CampaignAdapter;
use ToAdwords\Object\Adwords\AdwordsBase;
use ToAdwords\Object\Idclick\AdPlan;

use \AdWordsUser;

class Campaign extends AdwordsBase{

	private $idclickPlanid;
	private $idclickUid;
	private $campaignId;
	private $customerId;
	private $campaignName;
	private $languages;
	private $areas;
	private $biddingType;
	private $budgetAmount;
	private $deliveryMethod;
	private $maxCpc;
	private $campaignStatus;	
	private $lastAction;
	
	public function __construct(){
	
	}
	
	public function create($data){
		try{
			
		
			$user = new AdWordsUser();
			$user->SetClientCustomerId($clientCustomerId);
			$user->LogAll();
		
		}
	
	}
	
	public function update($data){
	
	}
	
	public function delete($data){
	
	}
	
	private function checkDependency(){
		
	}
}