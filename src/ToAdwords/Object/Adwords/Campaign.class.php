<?php

namespace ToAdwords\Object\Adwords;

require_once('init.php');

use ToAdwords\CampaignAdapter;
use ToAdwords\CustomerAdapter;
use ToAdwords\Model\CustomerModel;
use ToAdwords\Object\Adwords\AdwordsBase;

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
			/**
			 * 1、如果消息中没有有效customerId，则重新从数据库customer表中取得；如果此时无法从数据库
			 *	customer表中取得，则此次消息执行失败，返回FALSE。
			 * 2、已取得有效customerId，调取Google Adwords Api新建Campaign；
			 * 3、判断结果，新建成功则更新campaign表中campaignId字段，并置SYNC_STATUS为SYNCED. 返回
			 *   TRUE.  【NOTICE】如果新建成功，更新数据库失败|更新状态失败，则日志报警或发信报警。
			 * 返回TRUE; 如新建失败，则此次消息执行视为失败，返回FALSE。
			 * == MessageHandler会将失败消息转入重试队列 ==
			 */
			$customerModel = new CustomerModel();
			if(!$customerModel->isValidAdwordsId($data['customer_id'])){
				$customerInfo = $customerModel->getAdapteInfo($data['idclick_uid']);
				$data['adwords_customerid'] = $customerInfo['customer_id'];
				//need to check adwords_customerid is valid.
				if(!$customerModel->isValidAdwordsId($data['customer_id'])){
					return FALSE;
				}
			}
		
			$user = new AdWordsUser();
			$user->SetClientCustomerId($clientCustomerId);
			$user->LogAll();
		
		} catch(Exception $e){
			echo $e->getMessage();
			return FALSE;
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
