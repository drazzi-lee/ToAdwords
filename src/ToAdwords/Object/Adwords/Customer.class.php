<?php

namespace ToAdwords\Object\Adwords;
use ToAdwords\Object\Adwords\AdwordsBase;

class Customer extends AdwordsBase{
	/**
	 * @access public
	 * @var string
	 */
	public $name;

	/**
	 * @access public
	 * @var string
	 */
	public $companyName;

	/**
	* @access public
	* @var integer
	*/
	public $customerId;

	/**
	 * @access public
	 * @var boolean
	 */
	public $canManageClients;

	/**
	* @access public
	* @var string
	*/
	public $currencyCode;

	/**
	* @access public
	* @var string
	*/
	public $dateTimeZone;

	/**
	* @access public
	* @var boolean
	*/
	public $testAccount;
	
	

}