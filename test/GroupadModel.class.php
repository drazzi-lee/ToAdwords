<?php

require_once('./src/ToAdwords/bootstrap.inc.php');

use ToAdwords\AdGroupAdAdapter;

class GroupadModel{
	private $adGroupAdAdapter;

	public function __construct(){
		$this->adGroupAdAdapter = new AdGroupAdAdapter();
	}
	
	/**
	 * 添加广告
	 *
	 * @param array $data: 要添加的数据，数据结构为
	 * 		$data = array(
	 * 			'idclick_adid'		=> 12345,		
	 *			'idclick_groupid'	=> 123456,
	 *			'ad_headline'		=> 'headline',
	 *			'ad_description1'	=> 'description1',
	 *			'ad_description2'	=> 'description2',
	 *			'ad_url'			=> 'http://www.izptec.com/go.php',
	 *			'ad_displayurl'		=> 'http://www.izptec.com/',
	 * 		);
	 *		注意：创建时父级依赖idclick_groupid为必需
	 * @return array $result
	 */
	public function createAd(array $data){
		return $this->adGroupAdAdapter->create($data);
	}
	
	/**
	 * 更新广告
	 *
	 * @param array $data: 要更新的数据，数据结构为
	 * 		$data = array(
	 * 			'idclick_adid'		=> 12345,
	 *			'ad_headline'		=> 'headline', //可选
	 *			'ad_description1'	=> 'description1', //可选
	 *			'ad_description2'	=> 'description2', //可选
	 *			'ad_url'			=> 'http://www.izptec.com/go.php', //可选
	 *			'ad_displayurl'		=> 'http://www.izptec.com/', //可选
	 * 		);
	 * @return array $result
	 */	
	public function updateAd(array $data){
		return $this->adGroupAdAdapter->update($data);
	}
	
	/**
	 * 删除广告
	 *
	 * @param array $data: 要删除的数据，数据结构为
	 * 		$data = array(
	 * 			'idclick_adid'		=> 12345,
	 *			'ad_headline'		=> 'headline', //可选
	 *			'ad_description1'	=> 'description1', //可选
	 *			'ad_description2'	=> 'description2', //可选
	 *			'ad_url'			=> 'http://www.izptec.com/go.php', //可选
	 *			'ad_displayurl'		=> 'http://www.izptec.com/', //可选
	 * 		);
	 * @return array $result
	 */
	public function deleteAd(array $data){
		return $this->adGroupAdAdapter->delete($data);
	}
}
