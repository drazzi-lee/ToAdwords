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
		if($data['max_cpc'] >= 0.01){
			$bid = new \CpcBid();
			$bid->bid = new \Money($data['max_cpc'] * self::$moneyMultiples); //default max cpc.
			//$bid->contentBid = new \Money($data['max_cpc'] * self::$moneyMultiples); # display network bid.
			$biddingStrategyConfiguration = new \BiddingStrategyConfiguration();
			$biddingStrategyConfiguration->bids[] = $bid;
			$adGroup->biddingStrategyConfiguration = $biddingStrategyConfiguration;
		}

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
		$keywordsArray = explode(',', $data['keywords']);
		if(count($keywordsArray) > 0){
			try{
				$this->addKeywords($adGroup->id, $keywordsArray);
			} catch(\Exception $e){
				Log::write('[warning] try to add keywords on AdGroup adgroup_id#'.$adGroup->id.' error :'. $e->getMessage(), __METHOD__);
			}
		}
		return $adGroup->id;
	}
	
	/**
	 * Update AdGroup on Adwords Customer Account.
	 *
	 * @param array $data: $data of AdGroup.
	 * @return boolean: TRUE on success, FALSE on failure.
	 * @throw Exception
	 *
	 * have to delete the keyword and add a new one with modified text. 
	 * AdWords doesn't allow keyword text to be updated once it is created
	 * @see https://groups.google.com/forum/#!topic/adwords-api/orHKi0tox1Q
	 * @see https://code.google.com/p/google-api-adwords-php/source/browse/examples/AdWords/v201306/BasicOperations/UpdateAdGroup.php
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
		if(isset($data['max_cpc']) && $data['max_cpc'] >= 0.01){
			$bid = new \CpcBid();
			$bid->bid = new \Money($data['max_cpc'] * self::$moneyMultiples); //default max cpc.
			$biddingStrategyConfiguration = new \BiddingStrategyConfiguration();
			$biddingStrategyConfiguration->bids[] = $bid;
			$adGroup->biddingStrategyConfiguration = $biddingStrategyConfiguration;
		}
		// Update Keywords. remove all then add.
		if(isset($data['keywords'])){
			try{
				$keywordIds = $this->getKeywords($data['adgroup_id']);
				$this->delKeywords($data['adgroup_id'], $keywordIds);
				$keywordsArray = explode(',', $data['keywords']);
				if(count($keywordsArray) > 0){
					$this->addKeywords($adGroup->id, $keywordsArray);
				}
			} catch(\Exception $e){
				Log::write('[warning] try to update keywords on AdGroup adgroup_id#'.$data['adgroup_id'].' error :'. $e->getMessage(), __METHOD__);
			}
		}
		
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
	
	/**
	 * Get criterion Id of keywords
	 *
	 * @param string $adGroupId:
	 * @return array: criterionIds of keywords
	 * @throw \Exception;
	 * @see http://stackoverflow.com/questions/8781850/
	 */
	private function getKeywords($adGroupId){	
		$adGroupCriterionService = $this->getService('AdGroupCriterionService');
		
		// Create selector.
		$selector = new \Selector();
		$selector->fields = array('KeywordText', 'KeywordMatchType', 'Id');
		$selector->ordering[] = new \OrderBy('KeywordText', 'ASCENDING');

		// Create predicates.
		$selector->predicates[] = new \Predicate('AdGroupId', 'IN', array($adGroupId));
		$selector->predicates[] =
				new \Predicate('CriteriaType', 'IN', array('KEYWORD'));

		// Create paging controls.
		$selector->paging = new \Paging(0, \AdWordsConstants::RECOMMENDED_PAGE_SIZE);
		
		$criterionIds = array();
		do {
			$page = $adGroupCriterionService->get($selector);
			if (isset($page->entries)) {
				foreach ($page->entries as $adGroupCriterion) {
					$criterionIds[] = $adGroupCriterion->criterion->id;
				}
			}
			$selector->paging->startIndex += \AdWordsConstants::RECOMMENDED_PAGE_SIZE;
		} while ($page->totalNumEntries > $selector->paging->startIndex);
		
		return $criterionIds;
	}
	
	/**
	 * Remove keywords by id.
	 *
	 * @param string $adGroupId:
	 * @param array $criterionIds: criterionIds of keywords
	 * @return boolean: TRUE on success
	 * @throw \Exception;
	 */
	private function delKeywords($adGroupId, $criterionIds){
		$adGroupCriterionService = $this->getService('AdGroupCriterionService');

		$operations = array();
		foreach($criterionIds as $criterionId){
			$criterion = new \Criterion();
			$criterion->id = $criterionId;
			
			// Create ad group criterion.
			$adGroupCriterion = new \AdGroupCriterion();
			$adGroupCriterion->adGroupId = $adGroupId;
			$adGroupCriterion->criterion = new \Criterion($criterionId);

			// Create operation.
			$operation = new \AdGroupCriterionOperation();
			$operation->operand = $adGroupCriterion;
			$operation->operator = 'REMOVE';

			$operations[] = $operation;
		}
		$result = $adGroupCriterionService->mutate($operations);

		return TRUE;
	}
}
