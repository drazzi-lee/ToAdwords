<?php

require_once('src/ToAdwords/bootstrap.inc.php');

use ToAdwords\AdGroupAdapter;

/**
 * 广告组数据模型 GroupModel
 *
 * 此模型为虚拟类，模型实例化时并不产生实际数据连接。
 */
class GroupModel{
	private $adGroupAdapter;

	public function __construct(){
		$this->adGroupAdapter = new AdGroupAdapter();
	}
	
	/**
	 * 添加广告组
	 *
	 * @param array $data: 要添加的数据，数据结构为
	 * 		$data = array(
	 *			'idclick_groupid'	=> 123456,
	 * 			'idclick_planid'	=> 12345,
	 * 			'idclick_uid'		=> 441,			
	 *			'adgroup_name'		=> 'group_name',
	 *			'keywords'			=> array('keywords1', 'keywords2'),
	 *			'budget_amount'		=> 200.00,	
	 * 		);
	 * @return array $result
	 */
	public function createAdgroup($data){
		return $this->adGroupAdapter->create($data);	
	}
	
	/**
	 * 更新广告组
	 *
	 * @param array $data: 结构为：
	 *	  $data = array(
	 *	   		'idclick_groupid'	=> 123456,
	 *	 		'idclick_uid'		=> 441,			
	 *	   		'adgroup_name'		=> 'group_name2',
	 *	   		'keywords'			=> array('keywords3', 'keywords2', 'keywords1'),
	 *	   		'budget_amount'		=> 201.00,
	 *	   );
	 * @return array $result
	 */	
	public function updateAdgroup($data){
		return $this->adGroupAdapter->update($data);
	}
	
	/**
	 * 删除广告组
	 *
	 * @param array $data: 结构为：
	 *	  $data = array(
	 *	   		'idclick_groupid'	=> 123456,
	 *	 		'idclick_uid'		=> 441,			
	 *	   		'adgroup_name'		=> 'group_name2',
	 *	   		'keywords'			=> array('keywords3', 'keywords2', 'keywords1'),
	 *	   		'budget_amount'		=> 201.00,
	 *	   );
	 * @return array $result
	 */
	public function deleteAdgroup($data){
		return $this->adGroupAdapter->delete($data);
	}
}
