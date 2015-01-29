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
	// format strings for working with Zend_Date
	const MAGE_DATETIME_FORMAT = 'Y-m-d H:i:s';
	const XML_DATETIME_FORMAT = 'c';
	const TIME_FORMAT = '%h:%I:%S';
	// order source constants
	const BACKEND_ORDER_SOURCE = 'phone';
	const FRONTEND_ORDER_SOURCE = 'web';

	/** @var EbayEnterprise_Eb2cCore_Helper_Data */
	protected $_coreHelper;
	/** @var Mage_Log_Model_Visitor */
	protected $_visitorLog;
	/** @var Mage_Log_Model_Customer */
	protected $_customerLog;
	/** @var Mage_Core_Model_Session */
	protected $_session;
	/**
	 * inject dependencies
	 * @param array
	 */
	public function __construct(array $args=[])
	{
		list($this->_customerLog, $this->_visitorLog, $this->_coreHelper) =
			$this->_checkTypes(
				$this->_nullCoalesce('customer_log', $args, Mage::getModel('log/customer')),
				$this->_nullCoalesce('visitor_log', $args, Mage::getModel('log/visitor')),
				$this->_nullCoalesce('core_helper', $args, Mage::helper('eb2ccore'))
			);
	}

	/**
	 * ensure correct types
	 * @param  Mage_Log_Model_Customer
	 * @param  Mage_Log_Model_Visitor
	 * @param  EbayEnterprise_Eb2cCore_Helper_Data
	 * @return array
	 */
	protected function _checkTypes(
		Mage_Log_Model_Customer $customerLog,
		Mage_Log_Model_Visitor $visitorLog,
		EbayEnterprise_Eb2cCore_Helper_Data $coreHelper
	) {
		return [$customerLog, $visitorLog, $coreHelper];
	}

	/**
	 * return $ar[$key] if it exists otherwise return $default
	 * @param  string
	 * @param  array
	 * @param  mixed
	 * @return mixed
	 */
	protected function _nullCoalesce($key, array $ar, $default)
	{
		return isset($ar[$key]) ? $ar[$key] : $default;
	}

	/**
	 * return an array with data for the session info element
	 * @return array
	 */
	public function getSessionInfo()
	{
		/**
		 * @var Mage_Customer_Model_Session $session
		 * @var Mage_Log_Model_Visitor $visitorLog
		 */
		$session = $this->_getCustomerSession();
		$sessionId = $session->getEncryptedSessionId();
		$visitorLog = $this->_visitorLog->load($sessionId, 'session_id');
		return array(
			'encrypted_session_id' => hash('sha256', $sessionId),
			'last_login' => $this->_getLastLoginTime($session, $visitorLog),
			'order_source' => $this->_getOrderSource(),
			'rtc_transaction_response_code' => null,
			'rtc_reason_codes' => null,
			'time_on_file' => null,
			'time_spent_on_site' => $this->_getTimeSpentOnSite($visitorLog),
		);
	}

	/**
	 * return the last login time as a DateTime object.
	 * return null if the last login time cannot be calculated.
	 * @param  Mage_Customer_Model_Session
	 * @param  Mage_Log_Model_Visitor
	 * @return DateTime
	 */
	protected function _getLastLoginTime(Mage_Customer_Model_Session $session, Mage_Log_Model_Visitor $visitorLog=null)
	{
		if ($visitorLog && $session->isLoggedIn()) {
			$lastLogin = date_create_from_format(
				self::MAGE_DATETIME_FORMAT,
				$this->_customerLog->load($visitorLog->getId(), 'visitor_id')->getLoginAt()
			);
		}
		return isset($lastLogin) ? $lastLogin : null;
	}

	/**
	 * get the time spent on the site as a DateInterval
	 * returns null if unable to calculate the interval.
	 * @param  Mage_Log_Model_Visitor
	 * @return DateInterval
	 */
	protected function _getTimeSpentOnSite(Mage_Log_Model_Visitor $visitorLog=null)
	{
		if ($visitorLog) {
			$start = date_create_from_format(self::MAGE_DATETIME_FORMAT, $visitorLog->getFirstVisitAt()) ?: null;
			$end = date_create_from_format(self::MAGE_DATETIME_FORMAT, $visitorLog->getLastVisitAt()) ?: null;
			if ($start && $end && $start < $end) {
				$timeSpentOnSite = $end->diff($start);
			}
		}
		return isset($timeSpentOnSite) ? $timeSpentOnSite : null;
	}

	/**
	 * getting the referrer value as self::BACKEND_ORDER_SOURCE when the order is placed via ADMIN
	 * otherwise this order is being placed in the FRONTEND return this constant value self::FRONTEND_ORDER_SOURCE
	 * @return string
	 */
	protected function _getOrderSource()
	{
		$session = $this->_getCustomerSession();
		$orderSource = $session->getOrderSource() ?: self::FRONTEND_ORDER_SOURCE;
		return ($this->_coreHelper->getCurrentStore()->isAdmin()) ? self::BACKEND_ORDER_SOURCE : $orderSource;
	}

	/**
	 * get the current customer session
	 * @return Mage_Customer_Model_Session
	 */
	protected function _getCustomerSession()
	{
		if (!$this->_session) {
			$this->_session = Mage::getSingleton('customer/session');
		}
		return $this->_session;
	}
}
