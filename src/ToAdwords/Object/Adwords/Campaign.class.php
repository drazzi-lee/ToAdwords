<?php

namespace ToAdwords\Object\Adwords;

require_once('init.php');

use ToAdwords\CampaignAdapter;
use ToAdwords\CustomerAdapter;
use ToAdwords\Object\Adwords\AdwordsBase;
use ToAdwords\Object\Idclick\AdPlan;
use ToAdwords\Object\Idclick\Member;

use \AdWordsUser;

class Campaign extends AdwordsBase{

	private $idclickPlanId;
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
			
			if($this->customerId == 1 || empty($this->customerId)){
				$customerAdapter = new CustomerAdapter;
				$member = new Member($this->idclickUid);
				$this->customerId = $customerAdapter->getAdaptedId($member);		
			}
		
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
	
	public function setIdclickPlanId($idclickPlanId){
		$this->idclickPlanId = $idclickPlanId;
	}
	
	public function setIdclickUid($idclickUid){
		$this->idclickUid = $idclickUid;
	}
	
	public function setCampaignId($campaignId){
		$this->campaignId = $campaignId;
	}
	
	public function setCustomerId($customerId){
		$this->customerId = $customerId;
	}

	public function setCampaignName($campaignName){
		$this->campaignName = $campaignName;
	}
	
	public function setLanguages($languages){
		$this->languages = $languages;
	}
	
	public function setAreas($areas){
		$this->areas = $areas;
	}
	
	public function setBiddingType($biddingType){
		$this->biddingType = $biddingType;	
	}
	
	public function setBudgetAmount($budgetAmount){
		$this->budgetAmount = $budgetAmount;	
	}
	
	public function setDeliveryMethod($deliveryMethod){
		$this->deliveryMethod = $deliveryMethod;	
	} 
	
	public function setMaxCpc($maxCpc){
		$this->maxCpc = $maxCpc;	
	}
	
    public function setCampaignStatus($campaignStatus){
		$this->campaignStatus = $campaignStatus;	
	} 
	
    public function setLastAction($lastAction){
		$this->lastAction = $lastAction;	
	}	
}
