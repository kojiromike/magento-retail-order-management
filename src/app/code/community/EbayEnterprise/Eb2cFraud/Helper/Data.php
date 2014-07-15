<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class EbayEnterprise_Eb2cFraud_Helper_Data extends Mage_Core_Helper_Abstract
{
	// Relative path where scripts are stored
	const JSC_JS_PATH = 'ebayenterprise_eb2cfraud';
	// Form field name that will contain the name of the randomly selected JSC
	// form field. Used to find the generated JSC data in the POST data
	const JSC_FIELD_NAME = 'eb2cszyvl';
	// format strings for working with Zend_Date
	const MAGE_DATETIME_FORMAT = 'Y-m-d H:i:s';
	const XML_DATETIME_FORMAT = "c";
	const TIME_FORMAT = '%h:%I:%S';
	/**
	 * Combined Eb2c_Core and Eb2c_Fraud config model
	 * @var EbayEnterprise_Eb2cCore_Model_Config
	 */
	private $_config;
	/**
	 * Url to our JavaScript
	 * @var string
	 */
	private $_jscUrl;
	/**
	 * Set up _config and _jscUrl.
	 */
	public function __construct()
	{
		$this->_config = Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getModel('eb2ccore/config'));
		$this->_jscUrl = Mage::getBaseUrl(
			Mage_Core_Model_Store::URL_TYPE_JS,
			array('_secure' => true)
		) . self::JSC_JS_PATH;
	}
	/**
	 * @see _config
	 */
	public function getConfig()
	{
		return $this->_config;
	}
	/**
	 * @see _jscUrl
	 */
	public function getJscUrl()
	{
		return $this->_jscUrl;
	}
	/**
	 * Find the generated JS data from the given request's POST data. This uses
	 * a known form field in the POST data, self::JSC_FIELD_NAME, to find the
	 * form field populated by the JS collector. As the form field populated is
	 * selected at random, this mapping is the only way to find the data
	 * populated by the collector.
	 * @param  Mage_Core_Controller_Request_Http $request
	 * @return string
	 */
	public function getJavaScriptFraudData($request)
	{
		return $request->getPost($request->getPost(static::JSC_FIELD_NAME, ''), '');
	}
	/**
	 * return an array with data for the session info element
	 * @return array
	 */
	public function getSessionInfo()
	{
		$session = Mage::getSingleton('customer/session');
		$visitorLog = Mage::getModel('log/visitor')
			->load($session->getEncryptedSessionId(), 'session_id');

		$timeSpentOnSite = '';
		$start = $visitorLog->getFirstVisitAt() ? date_create_from_format(self::MAGE_DATETIME_FORMAT, $visitorLog->getFirstVisitAt()) : null;
		$end = $visitorLog->getLastVisitAt() ? date_create_from_format(self::MAGE_DATETIME_FORMAT, $visitorLog->getLastVisitAt()) : null;
		if ($start && $end && $start < $end) {
			$timeSpentOnSite = $end->diff($start)->format(self::TIME_FORMAT);
		}
		$password = '';
		$lastLogin = '';
		if ($session->isLoggedIn()) {
			$customer = $session->getCustomer();
			$password = $customer->decryptPassword($customer->getPassword());
			$lastLogin = date_create_from_format(
				self::MAGE_DATETIME_FORMAT,
				Mage::getModel('log/customer')->load($visitorLog->getId(), 'visitor_id')->getLoginAt()
			);
			$lastLogin = $lastLogin ? $lastLogin->format(self::XML_DATETIME_FORMAT) : '';
		}
		return array(
			'TimeSpentOnSite' => $timeSpentOnSite,
			'LastLogin' => $lastLogin,
			'UserPassword' => $password,
			'TimeOnFile' => '',
			'RTCTransactionResponseCode' => '',
			'RTCReasonCodes' => '',
		);
	}
}
