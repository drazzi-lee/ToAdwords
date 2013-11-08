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

class CampaignManager extends AdwordsBase{
	private $campaignService;
	private $budgetService;
	
	protected static $statusMap = array(
		'ACTIVE'	=> 'ACTIVE',
		'PAUSE'		=> 'PAUSED',
		'DELETE'	=> 'DELETED',
	);
	
	public function __construct($customerId){
		if(empty($customerId)){
			throw new \Exception('$customerId is required.');
		}
		parent::__construct();
		$this->user->SetClientCustomerId($customerId);
		$this->campaignService = $this->getService('CampaignService');
		$this->budgetService = $this->getService('BudgetService');
	}
	
	/**
	 * Create campaign on Adwords Customer Account.
	 *
	 * @param array $data: $data of campaign.
	 * @return string: campaign's id.
	 * @throw Exception
	 */
	public function create($data){
		$budget = new \Budget();
		$budget->name = 'Budget #' . uniqid();
		$budget->period = 'DAILY';
		$budget->amount = new \Money($data['budget_amount'] * self::$moneyMultiples);
		$budget->deliveryMethod = $data['delivery_method'];

		$operations = array();
		$operation = new \BudgetOperation();
		$operation->operand = $budget;
		$operation->operator = 'ADD';
		$budgetOperations[] = $operation;
		$result = $this->budgetService->mutate($budgetOperations);
		$budget = $result->value[0];
		
		// Create campaign.
		$campaign = new \Campaign();
		$campaign->name = $data['campaign_name'];
		$campaign->budget = new \Budget();
		$campaign->budget->budgetId = $budget->budgetId;

		// Set bidding strategy (required).
		$biddingStrategyConfiguration = new \BiddingStrategyConfiguration();
		$biddingStrategyConfiguration->biddingStrategyType = $data['bidding_type'];

		// You can optionally provide a bidding scheme in place of the type.
		switch($data['bidding_type']){
			case 'MANUAL_CPC':
				$biddingScheme = new \ManualCpcBiddingScheme();
				break;
			case 'BUDGET_OPTIMIZER':
				$biddingScheme = new \BudgetOptimizerBiddingScheme();
				if(isset($data['max_cpc']) && $data['max_cpc'] > 0.01){
					$biddingScheme->bidCeiling = new \Money($data['max_cpc'] * self::$moneyMultiples);
				}
				break;
			default:
				throw new \Exception('currently not supported bidding_tuype #'.$data['bidding_type']);
		}
		$biddingScheme->enhancedCpcEnabled = FALSE;
		$biddingStrategyConfiguration->biddingScheme = $biddingScheme;

		$campaign->biddingStrategyConfiguration = $biddingStrategyConfiguration;

		// Set keyword matching setting (required).
		$keywordMatchSetting = new \KeywordMatchSetting();
		$keywordMatchSetting->optIn = TRUE;
		$campaign->settings[] = $keywordMatchSetting;

		// Set network targeting (recommended).
		// 目前暂定仅搜索网络
		$networkSetting = new \NetworkSetting();
		$networkSetting->targetContentNetwork = FALSE;
		$campaign->networkSetting = $networkSetting;

		// Set additional settings (optional).
		$campaign->status = $this->mappingStatus($data['campaign_status']);
		$campaign->startDate = date('Ymd', strtotime('now'));
		//$campaign->adServingOptimizationStatus = 'UNAVAILABLE';

		// Create operation.
		$operation = new \CampaignOperation();
		$operation->operand = $campaign;
		$operation->operator = 'ADD';
		$operations[] = $operation;

		// Make the mutate request.
		$result = $this->campaignService->mutate($operations);

		// Display results.
		$campaign = $result->value[0];

		//add targeting criteria.
		$languages = explode(',', $data['languages']);
		if(count($languages) > 0){
			$this->addCriteria($campaign->id, $languages, 'LANGUAGE');
		}
		$locations = explode(',', $data['areas']);
		if(count($locations) > 0){
			$this->addCriteria($campaign->id, $locations, 'LOCATION');
		}
		
		return $campaign->id;
	}
	
	/**
	 * Update campaign on Adwords Customer Account.
	 *
	 * @param array $data: $data of campaign.
	 * @return string: campaign's id.
	 * @throw Exception
	 * @see https://developers.google.com/adwords/api/docs/guides/location-targeting#updating-targets
	 * @note Need to use the REMOVE + ADD combination when updating geo targets for campaign.
	 */
	public function update($data){
		if(!isset($data['campaign_id'])){
			throw new \Exception('campaign id is required.');
		}
		
		// Create campaign using an existing ID.
		$campaign = new \Campaign();
		$campaign->id = $data['campaign_id'];

		if(isset($data['campaign_status'])){
			$campaign->status = $this->mappingStatus($data['campaign_status']);
		}

		if(isset($data['campaign_name'])){
			$campaign->name = $data['campaign_name'];	
		}
		
		if(isset($data['bidding_type']) && isset($data['max_cpc'])){
			// Set bidding strategy (required).
			$biddingStrategyConfiguration = new \BiddingStrategyConfiguration();
			$biddingStrategyConfiguration->biddingStrategyType = $data['bidding_type'];
			
			// You can optionally provide a bidding scheme in place of the type.
			switch($data['bidding_type']){
				case 'MANUAL_CPC':
					$biddingScheme = new \ManualCpcBiddingScheme();
					break;
				case 'BUDGET_OPTIMIZER':
					$biddingScheme = new \BudgetOptimizerBiddingScheme();
					if(isset($data['max_cpc']) && $data['max_cpc'] > 0.01){
						$biddingScheme->bidCeiling = new \Money($data['max_cpc'] * self::$moneyMultiples);
					}
					break;
				default:
					throw new \Exception('currently not supported bidding_tuype #'.$data['bidding_type']);
			}
			$biddingScheme->enhancedCpcEnabled = FALSE;
			$biddingStrategyConfiguration->biddingScheme = $biddingScheme;
			$campaign->biddingStrategyConfiguration = $biddingStrategyConfiguration;
		}
		
		if(isset($data['budget_amount']) && isset($data['delivery_method'])){
			$budget = new \Budget();
			$budget->name = 'Budget #' . uniqid();
			$budget->period = 'DAILY';
			$budget->amount = new \Money($data['budget_amount'] * self::$moneyMultiples);
			
			$budget->deliveryMethod = $data['delivery_method'];

			$operations = array();
			$operation = new \BudgetOperation();
			$operation->operand = $budget;
			$operation->operator = 'ADD';
			$budgetOperations[] = $operation;
			$result = $this->budgetService->mutate($budgetOperations);
			$budget = $result->value[0];
			$campaign->budget = new \Budget();
			$campaign->budget->budgetId = $budget->budgetId;
		}
		
		if(isset($data['languages']) && count(explode(',', $data['languages'])) > 0){
			$currentLanguages = $this->getCriteria($data['campaign_id'], 'LANGUAGE');
			$this->delCriteria($data['campaign_id'], $currentLanguages, 'LANGUAGE');
			$this->addCriteria($data['campaign_id'], explode(',', $data['languages']), 'LANGUAGE');
		}
		
		if(isset($data['locations']) && count(explode(',', $data['locations'])) > 0){
			$currentLocations = $this->getCriteria($data['campaign_id'], 'LOCATION');
			$this->delCriteria($data['campaign_id'], $currentLocations, 'LOCATION');
			$this->addCriteria($data['campaign_id'], explode(',', $data['locations']), 'LOCATION');
		}
		
		// Create operation.
		$operation = new \CampaignOperation();
		$operation->operand = $campaign;
		$operation->operator = 'SET';

		$operations = array($operation);

		// Make the mutate request.
		$result = $this->campaignService->mutate($operations);		
		return TRUE;
	}
	
	/**
	 * Delete campaign on Adwords Customer Account.
	 *
	 * @param array $data: $data of campaign.
	 * @return string: campaign's id.
	 * @throw Exception
	 */
	public function delete($data){
		if(!isset($data['campaign_id'])){
			throw new \Exception('campaign id is required.');
		}
		
		// Create campaign with DELETED status.
		$campaign = new \Campaign();
		$campaign->id = $campaignId;
		$campaign->status = 'DELETED';
		// Rename the campaign as delete it, to avoid future name conflicts.
		$campaign->name = 'Deleted ' . date('Ymd his');
		
		// Create operations.
		$operation = new \CampaignOperation();
		$operation->operand = $campaign;
		$operation->operator = 'SET';

		$operations = array($operation);

		// Make the mutate request.
		$result = $this->campaignService->mutate($operations);
		return TRUE;
	}
	
	/**
	 * Add criterias on campaign.
	 *
	 * @param string $clientCustomerId:
	 * @param string $campaignId;
	 * @param array $criterias: criteria's id array.
	 * @param string $type:LOCATION/LANGUAGE. 
	 * @return array: criterias id on campaign.
	 * @throw Exception
	 */
	private function addCriteria($campaignId, array $criterias, $type){
		if(count($criterias) == 0){
			return NULL;
		}
		
		$campaignCriterionService = $this->getService('CampaignCriterionService');
		$campaignCriteria = array();
		foreach($criterias as $criteriaId){
			$criteria = null;
			switch($type){
				case 'LOCATION': $criteria = new \Location(); break;
				case 'LANGUAGE': $criteria = new \Language(); break;
				default:
					throw new \Exception('currently unsupported criteria type #'.$type);
			}
			$criteria->id = $criteriaId;
			$campaignCriteria[] = new \CampaignCriterion($campaignId, null, $criteria);
		}

		$operations = array();
		foreach($campaignCriteria as $campaignCriterion){
			$operations[] = new \CampaignCriterionOperation($campaignCriterion, 'ADD');
		}

		$result = $campaignCriterionService->mutate($operations);

		$criterionIds = array();
		foreach($result->value as $campaignCriterion){
			$criterionIds[] = $campaignCriterion->criterion->id;
		}

		return $criterionIds;
	}
	
	/**
	 * Get criteria by id in specify type
	 *
	 * @param string $campaignId
	 * @param string $type
	 * @return array: criteria ids.
	 */
	private function getCriteria($campaignId, $type){
		// Get the service, which loads the required classes.
		$campaignCriterionService = $this->getService('CampaignCriterionService');

		// Create selector.
		$selector = new \Selector();
		$selector->fields = array('Id', 'CriteriaType');

		// Create predicates.
		$selector->predicates[] = new \Predicate('CampaignId', 'IN', array($campaignId));
		$selector->predicates[] = new \Predicate('CriteriaType', 'IN', array($type));

		// Create paging controls.
		$selector->paging = new \Paging(0, \AdWordsConstants::RECOMMENDED_PAGE_SIZE);
		
		$criterions = array();
		do {
			// Make the get request.
			$page = $campaignCriterionService->get($selector);

			// Display results.
			if (isset($page->entries)) {
				foreach ($page->entries as $campaignCriterion) {
					$criterions[] = $campaignCriterion->criterion->id;
				}
			}

			// Advance the paging index.
			$selector->paging->startIndex += \AdWordsConstants::RECOMMENDED_PAGE_SIZE;
		} while ($page->totalNumEntries > $selector->paging->startIndex);
		
		return $criterions;
	}
	
	/**
	 * Delete criteria by id in specify type
	 *
	 * @param string $campaignId
	 * @param array $criterions
	 * @param string $type
	 * @return TRUE on success, \Exception on failure.
	 */
	private function delCriteria($campaignId, $criterions, $type){
		// Get the service, which loads the required classes.
		$campaignCriterionService = $this->getService('CampaignCriterionService');
		
		$campaignCriteria = array();
		if(count($criterions) > 0){
			foreach($criterions as $criteriaId){
				$criteria = null;
				switch($type){
					case 'LOCATION': $criteria = new \Location(); break;
					case 'LANGUAGE': $criteria = new \Language(); break;
					default:
						throw new \Exception('currently unsupported criteria type #'.$type);
				}
				$criteria->id = $criteriaId;
				$campaignCriteria[] = new \CampaignCriterion($campaignId, null, $criteria);
			}
		}

		$operations = array();
		foreach($campaignCriteria as $campaignCriterion){
			$operations[] = new \CampaignCriterionOperation($campaignCriterion, 'REMOVE');
		}

		$result = $campaignCriterionService->mutate($operations);
		
		return TRUE;
	}
}
