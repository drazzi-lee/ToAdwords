<?php

/**
 * AdwordsManager.class.php
 *
 * Provide methods to operate Google Adwords Object by API.
 *
 * @author Li Pengfei
 * @email drazzi.lee@gmail.com
 * @version 1.0
 */
namespace ToAdwords\Util;

// import init file from google library.
require_once(TOADWORDS_ADWORDS_INITFILE);

class AdwordsManager{
	private $user;

	public function __construct(){
		$this->user = new \AdWordsUser();
		$this->user->LogAll();
	}

	public function createAccount(){
		try{
			// Get the service, which loads the required classes.
			$managedCustomerService =
				$this->user->GetService('ManagedCustomerService', ADWORDS_VERSION);

			// Create customer.
			$customer = new \ManagedCustomer();
			$customer->name = 'Account #' . uniqid();
			$customer->currencyCode = 'CNY';
			$customer->dateTimeZone = 'Asia/Shanghai';

			// Create operation.
			$operation = new \ManagedCustomerOperation();
			$operation->operator = 'ADD';
			$operation->operand = $customer;

			$operations = array($operation);

			// Make the mutate request.
			$result = $managedCustomerService->mutate($operations);

			// Display result.
			$customer = $result->value[0];
			printf("Account with customer ID '%s' was created.\n",
					$customer->customerId);
		} catch(Exception $e){
			printf("An error has occurred: %s\n", $e->getMessage());
		}

	}

	public function createCampaign($clientCustomerId, $data){
		try{
			$this->user->SetClientCustomerId($clientCustomerId);
			// Get the BudgetService, which loads the required classes.
			$budgetService = $this->user->GetService('BudgetService', ADWORDS_VERSION);

			// Create the shared budget (required).
			$budget = new \Budget();
			$budget->name = 'Interplanetary Cruise Budget #' . uniqid();
			$budget->period = 'DAILY';
			$budget->amount = new \Money(50000000);
			$budget->deliveryMethod = 'STANDARD';

			$operations = array();

			// Create operation.
			$operation = new \BudgetOperation();
			$operation->operand = $budget;
			$operation->operator = 'ADD';
			$operations[] = $operation;

			// Make the mutate request.
			$result = $budgetService->mutate($operations);
			$budget = $result->value[0];

			// Get the CampaignService, which loads the required classes.
			$campaignService = $this->user->GetService('CampaignService', ADWORDS_VERSION);

			$numCampaigns = 2;
			$operations = array();
			for ($i = 0; $i < $numCampaigns; $i++) {
				// Create campaign.
				$campaign = new \Campaign();
				$campaign->name = $data['campaign_name'];

				// Set shared budget (required).
				$campaign->budget = new \Budget();
				$campaign->budget->budgetId = $budget->budgetId;

				// Set bidding strategy (required).
				$biddingStrategyConfiguration = new \BiddingStrategyConfiguration();
				$biddingStrategyConfiguration->biddingStrategyType = 'MANUAL_CPC';

				// You can optionally provide a bidding scheme in place of the type.
				$biddingScheme = new \ManualCpcBiddingScheme();
				$biddingScheme->enhancedCpcEnabled = FALSE;
				$biddingStrategyConfiguration->biddingScheme = $biddingScheme;

				$campaign->biddingStrategyConfiguration = $biddingStrategyConfiguration;

				// Set keyword matching setting (required).
				$keywordMatchSetting = new \KeywordMatchSetting();
				$keywordMatchSetting->optIn = TRUE;
				$campaign->settings[] = $keywordMatchSetting;

				// Set network targeting (recommended).
				$networkSetting = new \NetworkSetting();
				$networkSetting->targetGoogleSearch = TRUE;
				$networkSetting->targetSearchNetwork = TRUE;
				$networkSetting->targetContentNetwork = TRUE;
				$campaign->networkSetting = $networkSetting;

				// Set additional settings (optional).
				$campaign->status = 'PAUSED';
				$campaign->startDate = date('Ymd', strtotime('+1 day'));
				$campaign->endDate = date('Ymd', strtotime('+1 month'));
				$campaign->adServingOptimizationStatus = 'ROTATE';

				// Set frequency cap (optional).
				$frequencyCap = new \FrequencyCap();
				$frequencyCap->impressions = 5;
				$frequencyCap->timeUnit = 'DAY';
				$frequencyCap->level = 'ADGROUP';
				$campaign->frequencyCap = $frequencyCap;

				// Set advanced location targeting settings (optional).
				$geoTargetTypeSetting = new \GeoTargetTypeSetting();
				$geoTargetTypeSetting->positiveGeoTargetType = 'DONT_CARE';
				$geoTargetTypeSetting->negativeGeoTargetType = 'DONT_CARE';
				$campaign->settings[] = $geoTargetTypeSetting;

				// Create operation.
				$operation = new \CampaignOperation();
				$operation->operand = $campaign;
				$operation->operator = 'ADD';
				$operations[] = $operation;
			}

			// Make the mutate request.
			$result = $campaignService->mutate($operations);

			// Display results.
			foreach ($result->value as $campaign) {
				printf("Campaign with name '%s' and ID '%s' was added.\n", $campaign->name,
						$campaign->id);
			}
		} catch(Exception $e){
			printf("An error has occurred: %s\n", $e->getMessage());
		}
	}

	public function updateCampaign($clientCustomerId, $campaignId){
		try {
			$this->user->SetClientCustomerId($clientCustomerId);
			// Get the service, which loads the required classes.
			$campaignService = $this->user->GetService('CampaignService', ADWORDS_VERSION);

			// Create campaign using an existing ID.
			$campaign = new \Campaign();
			$campaign->id = $campaignId;
			$campaign->status = 'ACTIVE';

			// Create operation.
			$operation = new \CampaignOperation();
			$operation->operand = $campaign;
			$operation->operator = 'SET';

			$operations = array($operation);

			// Make the mutate request.
			$result = $campaignService->mutate($operations);

			// Display result.
			$campaign = $result->value[0];
			printf("Campaign with ID '%s' was paused.\n", $campaign->id);

		} catch (Exception $e) {
			printf("An error has occurred: %s\n", $e->getMessage());
		}
	}

	public function createAdGroup(){

	}

	public function createAdGroupAd(){

	}

	/**
	 * Runs the example.
	 * @param AdWordsUser $user the user to run the example with
	 */
	function AddCampaignsExample(AdWordsUser $user) {
		// Get the BudgetService, which loads the required classes.
		$budgetService = $user->GetService('BudgetService', ADWORDS_VERSION);

		// Create the shared budget (required).
		$budget = new \Budget();
		$budget->name = 'Interplanetary Cruise Budget #' . uniqid();
		$budget->period = 'DAILY';
		$budget->amount = new \Money(50000000);
		$budget->deliveryMethod = 'STANDARD';

		$operations = array();

		// Create operation.
		$operation = new \BudgetOperation();
		$operation->operand = $budget;
		$operation->operator = 'ADD';
		$operations[] = $operation;

		// Make the mutate request.
		$result = $budgetService->mutate($operations);
		$budget = $result->value[0];

		// Get the CampaignService, which loads the required classes.
		$campaignService = $user->GetService('CampaignService', ADWORDS_VERSION);

		$numCampaigns = 2;
		$operations = array();
		for ($i = 0; $i < $numCampaigns; $i++) {
			// Create campaign.
			$campaign = new \Campaign();
			$campaign->name = 'Interplanetary Cruise #' . uniqid();

			// Set shared budget (required).
			$campaign->budget = new \Budget();
			$campaign->budget->budgetId = $budget->budgetId;

			// Set bidding strategy (required).
			$biddingStrategyConfiguration = new \BiddingStrategyConfiguration();
			$biddingStrategyConfiguration->biddingStrategyType = 'MANUAL_CPC';

			// You can optionally provide a bidding scheme in place of the type.
			$biddingScheme = new \ManualCpcBiddingScheme();
			$biddingScheme->enhancedCpcEnabled = FALSE;
			$biddingStrategyConfiguration->biddingScheme = $biddingScheme;

			$campaign->biddingStrategyConfiguration = $biddingStrategyConfiguration;

			// Set keyword matching setting (required).
			$keywordMatchSetting = new \KeywordMatchSetting();
			$keywordMatchSetting->optIn = TRUE;
			$campaign->settings[] = $keywordMatchSetting;

			// Set network targeting (recommended).
			$networkSetting = new \NetworkSetting();
			$networkSetting->targetGoogleSearch = TRUE;
			$networkSetting->targetSearchNetwork = TRUE;
			$networkSetting->targetContentNetwork = TRUE;
			$campaign->networkSetting = $networkSetting;

			// Set additional settings (optional).
			$campaign->status = 'PAUSED';
			$campaign->startDate = date('Ymd', strtotime('+1 day'));
			$campaign->endDate = date('Ymd', strtotime('+1 month'));
			$campaign->adServingOptimizationStatus = 'ROTATE';

			// Set frequency cap (optional).
			$frequencyCap = new \FrequencyCap();
			$frequencyCap->impressions = 5;
			$frequencyCap->timeUnit = 'DAY';
			$frequencyCap->level = 'ADGROUP';
			$campaign->frequencyCap = $frequencyCap;

			// Set advanced location targeting settings (optional).
			$geoTargetTypeSetting = new \GeoTargetTypeSetting();
			$geoTargetTypeSetting->positiveGeoTargetType = 'DONT_CARE';
			$geoTargetTypeSetting->negativeGeoTargetType = 'DONT_CARE';
			$campaign->settings[] = $geoTargetTypeSetting;

			// Create operation.
			$operation = new \CampaignOperation();
			$operation->operand = $campaign;
			$operation->operator = 'ADD';
			$operations[] = $operation;
		}

		// Make the mutate request.
		$result = $campaignService->mutate($operations);

		// Display results.
		foreach ($result->value as $campaign) {
			printf("Campaign with name '%s' and ID '%s' was added.\n", $campaign->name,
					$campaign->id);
		}
	}

	/**
	 * Runs the example.
	 * @param AdWordsUser $user the user to run the example with
	 * @param string $campaignId the id of the campaign to delete
	 */
	function DeleteCampaignExample(AdWordsUser $user, $campaignId) {
		// Get the service, which loads the required classes.
		$campaignService = $user->GetService('CampaignService', ADWORDS_VERSION);

		// Create campaign with DELETED status.
		$campaign = new \Campaign();
		$campaign->id = $campaignId;
		$campaign->status = 'DELETED';
		// Rename the campaign as you delete it, to avoid future name conflicts.
		$campaign->name = 'Deleted ' . date('Ymd his');

		// Create operations.
		$operation = new \CampaignOperation();
		$operation->operand = $campaign;
		$operation->operator = 'SET';

		$operations = array($operation);

		// Make the mutate request.
		$result = $campaignService->mutate($operations);

		// Display result.
		$campaign = $result->value[0];
		printf("Campaign with ID '%s' was deleted.\n", $campaign->id);
	}

	/**
	 * Runs the example.
	 * @param AdWordsUser $user the user to run the example with
	 * @param string $adGroupId the id of the ad group to add the ads to
	 */
	function AddTextAdsExample(AdWordsUser $user, $adGroupId) {
		// Get the service, which loads the required classes.
		$adGroupAdService = $user->GetService('AdGroupAdService', ADWORDS_VERSION);

		$numAds = 5;
		$operations = array();
		for ($i = 0; $i < $numAds; $i++) {
			// Create text ad.
			$textAd = new \TextAd();
			$textAd->headline = 'Cruise #' . uniqid();
			$textAd->description1 = 'Visit the Red Planet in style.';
			$textAd->description2 = 'Low-gravity fun for everyone!';
			$textAd->displayUrl = 'www.example.com';
			$textAd->url = 'http://www.example.com';

			// Create ad group ad.
			$adGroupAd = new \AdGroupAd();
			$adGroupAd->adGroupId = $adGroupId;
			$adGroupAd->ad = $textAd;

			// Set additional settings (optional).
			$adGroupAd->status = 'PAUSED';

			// Create operation.
			$operation = new \AdGroupAdOperation();
			$operation->operand = $adGroupAd;
			$operation->operator = 'ADD';
			$operations[] = $operation;
		}

		// Make the mutate request.
		$result = $adGroupAdService->mutate($operations);

		// Display results.
		foreach ($result->value as $adGroupAd) {
			printf("Text ad with headline '%s' and ID '%s' was added.\n",
					$adGroupAd->ad->headline, $adGroupAd->ad->id);
		}
	}

	/**
	 * Runs the example.
	 * @param AdWordsUser $user the user to run the example with
	 * @param string $adGroupId the id of the ad group the ad is in
	 * @param string $adId the id of the ad
	 */
	function DeleteAdExample(AdWordsUser $user, $adGroupId, $adId) {
		// Get the service, which loads the required classes.
		$adGroupAdService = $user->GetService('AdGroupAdService', ADWORDS_VERSION);

		// Create base class ad to avoid setting type specific fields.
		$ad = new \Ad();
		$ad->id = $adId;

		// Create ad group ad.
		$adGroupAd = new \AdGroupAd();
		$adGroupAd->adGroupId = $adGroupId;
		$adGroupAd->ad = $ad;

		// Create operation.
		$operation = new \AdGroupAdOperation();
		$operation->operand = $adGroupAd;
		$operation->operator = 'REMOVE';

		$operations = array($operation);

		// Make the mutate request.
		$result = $adGroupAdService->mutate($operations);

		// Display result.
		$adGroupAd = $result->value[0];
		printf("Ad with ID '%s' was deleted.\n", $adGroupAd->ad->id);
	}

	/**
	 * Runs the example.
	 * @param AdWordsUser $user the user to run the example with
	 * @param string $campaignId the ID of the campaign to add the ad group to
	 */
	function AddAdGroupsExample(AdWordsUser $user, $campaignId) {
		// Get the service, which loads the required classes.
		$adGroupService = $user->GetService('AdGroupService', ADWORDS_VERSION);

		$numAdGroups = 2;
		$operations = array();
		for ($i = 0; $i < $numAdGroups; $i++) {
			// Create ad group.
			$adGroup = new \AdGroup();
			$adGroup->campaignId = $campaignId;
			$adGroup->name = 'Earth to Mars Cruise #' . uniqid();

			// Set bids (required).
			$bid = new \CpcBid();
			$bid->bid =  new \Money(1000000);
			$bid->contentBid = new \Money(750000);
			$biddingStrategyConfiguration = new \BiddingStrategyConfiguration();
			$biddingStrategyConfiguration->bids[] = $bid;
			$adGroup->biddingStrategyConfiguration = $biddingStrategyConfiguration;

			// Set additional settings (optional).
			$adGroup->status = 'ENABLED';

			// Targetting restriction settings - these setting only affect serving
			// for the Display Network.
			$targetingSetting = new \TargetingSetting();
			// Restricting to serve ads that match your ad group placements.
			$targetingSetting->details[] =
				new \TargetingSettingDetail('PLACEMENT', TRUE);
			// Using your ad group verticals only for bidding.
			$targetingSetting->details[] =
				new \TargetingSettingDetail('VERTICAL', FALSE);
			$adGroup->settings[] = $targetingSetting;

			// Create operation.
			$operation = new \AdGroupOperation();
			$operation->operand = $adGroup;
			$operation->operator = 'ADD';
			$operations[] = $operation;
		}

		// Make the mutate request.
		$result = $adGroupService->mutate($operations);

		// Display result.
		$adGroups = $result->value;
		foreach ($adGroups as $adGroup) {
			printf("Ad group with name '%s' and ID '%s' was added.\n", $adGroup->name,
					$adGroup->id);
		}
	}

	/**
	 * Runs the example.
	 * @param AdWordsUser $user the user to run the example with
	 * @param string $campaignId the id of the parent campaign
	 */
	function GetAdGroupsExample(AdWordsUser $user, $campaignId) {
		// Get the service, which loads the required classes.
		$adGroupService = $user->GetService('AdGroupService', ADWORDS_VERSION);

		// Create selector.
		$selector = new \Selector();
		$selector->fields = array('Id', 'Name');
		$selector->ordering[] = new \OrderBy('Name', 'ASCENDING');

		// Create predicates.
		$selector->predicates[] =
			new \Predicate('CampaignId', 'IN', array($campaignId));

		// Create paging controls.
		$selector->paging = new \Paging(0, AdWordsConstants::RECOMMENDED_PAGE_SIZE);

		do {
			// Make the get request.
			$page = $adGroupService->get($selector);

			// Display results.
			if (isset($page->entries)) {
				foreach ($page->entries as $adGroup) {
					printf("Ad group with name '%s' and ID '%s' was found.\n",
							$adGroup->name, $adGroup->id);
				}
			} else {
				print "No ad groups were found.\n";
			}

			// Advance the paging index.
			$selector->paging->startIndex += \AdWordsConstants::RECOMMENDED_PAGE_SIZE;
		} while ($page->totalNumEntries > $selector->paging->startIndex);
	}

}
