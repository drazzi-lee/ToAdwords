<?php

/**
 * AdGroupAdManager.class.php
 *
 * AdGroupAdManager classes who operate Google Adwords Object by API.
 *
 * @author Li Pengfei
 * @email drazzi.lee@gmail.com
 * @version 1.0
 */
namespace ToAdwords\Adwords;

class AdGroupAdManager extends AdwordsBase{
	private $adGroupAdService;
	
	protected static $statusMap = array(
		'ACTIVE'	=> 'ENABLED',
		'PAUSE'		=> 'PAUSED',
		'DELETE'	=> 'DISABLED',
	);
	
	public function __construct($customerId){
		if(empty($customerId)){
			throw new \Exception('CustomerId is required.');
		}
		parent::__construct();
		$this->user->SetClientCustomerId($customerId);
		$this->adGroupAdService = $this->getService('AdGroupAdService');
	}
	
	/**
	 * Create AdGroupAd on Adwords Customer Account.
	 *
	 * @param array $data: $data of AdGroupAd.
	 * @return string: AdGroupAd's id.
	 * @throw Exception
	 * @todo keywords
	 */
	public function create($data){
		if(empty($data['adgroup_id'])){
			throw new \Exception('adgroup id is required.');
		}
		if(empty($data['ad_headline']) || empty($data['ad_description1'])
		|| empty($data['ad_description2']) || empty($data['ad_displayurl'])){
			throw new \Exception('Incompleted ad data.');
		}
		$operations = array();
		// Create text ad.
		$textAd = new \TextAd();
		$textAd->headline = $data['ad_headline'];
		$textAd->description1 = $data['ad_description1'];
		$textAd->description2 = $data['ad_description2'];
		$textAd->displayUrl = $data['ad_displayurl'];
		$textAd->url = $data['ad_url'];

		// Create ad group ad.
		$adGroupAd = new \AdGroupAd();
		$adGroupAd->adGroupId = $data['adgroup_id'];
		$adGroupAd->ad = $textAd;

		// Set additional settings (optional).
		$adGroupAd->status = $this->mappingStatus($data['ad_status']);

		// Create operation.
		$operation = new \AdGroupAdOperation();
		$operation->operand = $adGroupAd;
		$operation->operator = 'ADD';
		$operations[] = $operation;

		// Make the mutate request.
		$result = $this->adGroupAdService->mutate($operations);
		$adGroupAd = $result->value[0];
		return $adGroupAd->ad->id;
	}
	
	/**
	 * Update AdGroupAd status on Adwords Customer Account.
	 *
	 * @param array $data: $data of AdGroupAd.
	 * @return string: AdGroupAd's id.
	 * @throw Exception
	 * @see https://groups.google.com/forum/#!topic/adwords-api/ZGiRT2IlpsM
	 */
	public function update($data){
		if(empty($data['ad_id'])){
			throw new \Exception('ad_id is required.');
		}
		if(empty($data['adgroup_id'])){
			throw new \Exception('adgroup_id is required.');
		}
		if(empty($data['ad_status'])){
			throw new \Exception('adgroup_id is required.');
		}
		$operations = array();
		$textAd = new \TextAd();
		$textAd->id = $data['ad_id'];
		
		// update TextAd status.
		$adGroupAd = new \AdGroupAd();
		$adGroupAd->adGroupId = $data['adgroup_id'];
		$adGroupAd->ad = $textAd;
		$adGroupAd->status = $this->mappingStatus($data['ad_status']);

		// Create operation.
		$operation = new \AdGroupAdOperation();
		$operation->operand = $adGroupAd;
		$operation->operator = 'SET';
		$operations = array($operation);

		// Make the mutate request.
		$result = $this->adGroupAdService->mutate($operations);
		$adGroupAd = $result->value[0];
		return TRUE;
	}
	
	/**
	 * Delete AdGroupAd on Adwords Customer Account.
	 *
	 * @param array $data: $data of AdGroupAd.
	 * @return string: AdGroupAd's id.
	 * @throw Exception
	 * @todo keywords
	 */
	public function delete($data){
		if(empty($data['adgroup_id'])){
			throw new \Exception('adgroup id is required.');
		}
		if(empty($data['ad_id'])){
			throw new \Exception('ad_id is required.');
		}
		$operations = array();
		$textAd = new \TextAd();
		$textAd->id = $data['ad_id'];
		
		// Create ad group ad.
		$adGroupAd = new \AdGroupAd();
		$adGroupAd->adGroupId = $adGroupId;
		$adGroupAd->ad = $textAd;
		
		// Create operation.
		$operation = new \AdGroupAdOperation();
		$operation->operand = $adGroupAd;
		$operation->operator = 'REMOVE';		
		$operations = array($operation);

		// Make the mutate request.
		$result = $this->adGroupAdService->mutate($operations);
		$adGroupAd = $result->value[0];
		return TRUE;
	}
}
