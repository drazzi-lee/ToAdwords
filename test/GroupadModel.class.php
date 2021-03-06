<?php

require_once('../bootstrap.inc.php');

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
	 * @return <array> $result:
	 *		$result = array(
	 *			'status'	=> 1,	//1：成功；0：失败或者部分失败
	 *									-1: 提供参数不完整或解析失败
	 *			'success'	=> 7,	//成功更新的内容计数
	 *			'failure'	=> 0	//如果status不为1，则failure有计数
	 *			'description'	=> 文字描述
	 *		);
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
	 * @return <array> $result:
	 *		$result = array(
	 *			'status'	=> 1,	//1：成功；0：失败或者部分失败
	 *									-1: 提供参数不完整或解析失败
	 *			'success'	=> 7,	//成功更新的内容计数
	 *			'failure'	=> 0	//如果status不为1，则failure有计数
	 *			'description'	=> 文字描述
	 *		);
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
	 * @return <array> $result:
	 *		$result = array(
	 *			'status'	=> 1,	//1：成功；0：失败或者部分失败
	 *									-1: 提供参数不完整或解析失败
	 *			'success'	=> 7,	//成功更新的内容计数
	 *			'failure'	=> 0	//如果status不为1，则failure有计数
	 *			'description'	=> 文字描述
	 *		);
	 */
	public function deleteAd(array $data){
		return $this->adGroupAdAdapter->delete($data);
	}

	public function runAd(array $data){
		return $this->adGroupAdAdapter->run($data);
	}
}
