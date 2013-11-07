<?php

/**
 * AdGroupManager.class.php
 *
 * Base Object to classes who operate Google Adwords Object by API.
 *
 * @author Li Pengfei
 * @email drazzi.lee@gmail.com
 * @version 1.0
 */
namespace ToAdwords\Adwords;

class AdGroupManager extends AdwordsBase{
	private $adGroupService;
	private $budgetService;
	
	protected static $statusMap = array(
		'ACTIVE'	=> 'ENABLED',
		'PAUSE'		=> 'PAUSED',
		'DELETE'	=> 'DELETED',
	);
	
	public function __construct($customerId){
		if(empty($customerId)){
			throw new \Exception('CustomerId is required.');
		}
		parent::__construct();
		$this->user->SetClientCustomerId($customerId);
		$this->adGroupService = $this->getService('AdGroupService');
		$this->budgetService = $this->getService('BudgetService');
	}
	
	/**
	 * Create AdGroup on Adwords Customer Account.
	 *
	 * @param array $data: $data of AdGroup.
	 * @return string: AdGroup's id.
	 * @throw Exception
	 * @todo keywords 
	 * @see http://stackoverflow.com/questions/8781850/
	 * @see https://code.google.com/p/google-api-adwords-php/source/browse/examples/AdWords/v201306/BasicOperations/AddKeywords.php
	 */
	public function create($data){
		if(empty($data['adgroup_name'])){
			throw new \Exception('adgroup name is required.');
		}
		if(empty($data['campaign_id'])){
			throw new \Exception('campaign id is required.');
		}
		
		$operations = array();
		// Create ad group.
		$adGroup = new \AdGroup();
		$adGroup->campaignId = $data['campaign_id'];
		$adGroup->name = $data['adgroup_name'];

		// Set bids (required).
		$bid = new \CpcBid();
		$bid->bid = new \Money($data['max_cpc'] * self::$moneyMultiples); //default max cpc.
		//$bid->contentBid = new \Money($data['max_cpc'] * self::$moneyMultiples); # display network bid.
		$biddingStrategyConfiguration = new \BiddingStrategyConfiguration();
		$biddingStrategyConfiguration->bids[] = $bid;
		$adGroup->biddingStrategyConfiguration = $biddingStrategyConfiguration;

		// Set additional settings (optional).
		$adGroup->status = $this->mappingStatus($data['adgroup_status']);

		// Create operation.
		$operation = new \AdGroupOperation();
		$operation->operand = $adGroup;
		$operation->operator = 'ADD';
		$operations[] = $operation;

		// Make the mutate request.
		$result = $this->adGroupService->mutate($operations);	
		$adGroup = $result->value[0];

		// Add Keywords on AdGroup
		$this->addKeywords($adGroup->id, explode(',', $data['keywords']));
		return $adGroup->id;
	}
	
	/**
	 * Update AdGroup on Adwords Customer Account.
	 *
	 * @param array $data: $data of AdGroup.
	 * @return boolean: TRUE on success, FALSE on failure.
	 * @throw Exception
	 * @todo keywords,max_cpc
	 */
	public function update($data){
		if(empty($data['adgroup_id'])){
			throw new \Exception('adgroup id is required.');
		}

		// Create ad group using an existing ID.
		$adGroup = new \AdGroup();
		$adGroup->id = $data['adgroup_id'];
		
		if(isset($data['adgroup_status'])){
			$adGroup->status = $this->mappingStatus($data['adgroup_status']);
		}
		if(isset($data['adgroup_name'])){
			$adGroup->name = $data['adgroup_name'];
		}

		// Update the bid.
		if(isset($data['max_cpc'])){
			$bid = new \CpcBid();
			$bid->bid = new \Money($data['max_cpc'] * self::$moneyMultiples); //default max cpc.
			$biddingStrategyConfiguration = new \BiddingStrategyConfiguration();
			$biddingStrategyConfiguration->bids[] = $bid;
			$adGroup->biddingStrategyConfiguration = $biddingStrategyConfiguration;
		}
		// Update Keywords.
		
		// Create operation.
		$operation = new \AdGroupOperation();
		$operation->operand = $adGroup;
		$operation->operator = 'SET';
		$operations = array($operation);
		// Make the mutate request.
		$result = $this->adGroupService->mutate($operations);
		return TRUE;
	}
	
	/**
	 * Update AdGroup on Adwords Customer Account.
	 *
	 * @param array $data: $data of AdGroup.
	 * @return boolean: TRUE on success, FALSE on failure.
	 * @throw Exception
	 */
	public function delete($data){
		if(empty($data['adgroup_id'])){
			throw new \Exception('adgroup id is required.');
		}
		// Create ad group with DELETED status.
		$adGroup = new \AdGroup();
		$adGroup->id = $data['adgroup_id'];
		$adGroup->status = 'DELETED';
		// Rename the ad group as you delete it, to avoid future name conflicts.
		$adGroup->name = 'Deleted ' . date('Ymd his');

		// Create operations.
		$operation = new \AdGroupOperation();
		$operation->operand = $adGroup;
		$operation->operator = 'SET';

		$operations = array($operation);

		// Make the mutate request.
		$result = $this->adGroupService->mutate($operations);
		// Display result.
		$adGroup = $result->value[0];
		return TRUE;
	}

	/**
	 *
	 * @param string $adGroupId:
	 * @param array $keywords:
	 * @return boolean: TRUE on success
	 * @throw \Exception;
	 * @see http://stackoverflow.com/questions/8781850/
	 */
	private function addKeywords($adGroupId, $keywords){
		$adGroupCriterionService = $this->getService('AdGroupCriterionService');

		$operations = array();
		foreach($keywords as $keyword){
			$keywordObj = new \Keyword();
			$keywordObj->text = $keyword;
			$keywordObj->matchType = 'BROAD';

			$keywordAdGroupCriterion = new \BiddableAdGroupCriterion();
			$keywordAdGroupCriterion->adGroupId = $adGroupId;
			$keywordAdGroupCriterion->criterion = $keywordObj;

			$keywordAdGroupCriterionOperation = new \AdGroupCriterionOperation();
			$keywordAdGroupCriterionOperation->operand = $keywordAdGroupCriterion;
			$keywordAdGroupCriterionOperation->operator = 'ADD';

			$operations[] = $keywordAdGroupCriterionOperation;
		}
		$result = $adGroupCriterionService->mutate($operations);

		return TRUE;
	}
}
