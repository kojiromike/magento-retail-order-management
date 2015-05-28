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

class EbayEnterprise_Order_Block_Order_Cancel extends Mage_Core_Block_Template
{
	const TEXT_MESSAGE = 'Please let us know why you would like to cancel this order.';
	const CANCEL_ORDER_BUTTON = 'Cancel Order';
	const LOGGED_IN_CANCEL_URL_PATH = 'sales/order/romcancel';
	const GUEST_CANCEL_URL_PATH = 'sales/order/romguestcancel';
	const HELPER_CLASS = 'ebayenterprise_order';

	/** @var string */
	protected $_template = 'ebayenterprise_order/order/cancel.phtml';
	/** @var EbayEnterprise_Order_Helper_Data */
	protected $_orderHelper;
	/** @var Mage_Core_Helper_Http */
	protected $_coreHttp;

	protected function _construct()
	{
		parent::_construct();
		$this->_orderHelper = Mage::helper('ebayenterprise_order');
		$this->_coreHttp = Mage::helper('core/http');
	}

	protected function _prepareLayout()
	{
		if ($headBlock = $this->getLayout()->getBlock('head')) {
			$headBlock->setTitle($this->__('Order # %s', $this->getOrder()->getRealOrderId()));
		}
	}

	/**
	 * Ge the customer session instance.
	 *
	 * @return Mage_Customer_Model_Session
	 * @codeCoverageIgnores
	 */
	protected function _getSession()
	{
		return Mage::getSingleton('customer/session');
	}

	/**
	 * Retrieve current order model instance
	 *
	 * @return Mage_Sales_Model_Order
	 * @codeCoverageIgnore
	 */
	public function getOrder()
	{
		return Mage::registry('rom_order');
	}

	/**
	 * Text message to be displayed to the customer explaining
	 * why they want to cancel the order.
	 *
	 * @return string
	 */
	public function getTextMessage()
	{
		return $this->_orderHelper->__(static::TEXT_MESSAGE);
	}

	/**
	 * Text message to be displayed to the customer explaining
	 * why they want to cancel the order.
	 *
	 * @return string
	 */
	public function getCancelOrderButton()
	{
		return $this->_orderHelper->__(static::CANCEL_ORDER_BUTTON);
	}

	/**
	 * Constructing the cancel reason select box form field
	 *
	 * @return string
	 */
	public function getCancelReasonHtmlSelect($defValue=null, $name='cancel_reason', $id='cancel_reason', $title='Reason')
	{
		return $this->getLayout()->createBlock('core/html_select')
			->setName($name)
			->setId($id)
			->setTitle($this->_orderHelper->__($title))
			->setClass('validate-select')
			->setValue($defValue)
			->setOptions($this->_orderHelper->getCancelReasonOptionArray())
			->getHtml();
	}

	/**
	 * The URI to post to after a customer submit the cancel order button.
	 *
	 * @param  string
	 * @return string
	 */
	public function getPostActionUrl()
	{
		return $this->getUrl($this->_getCancelUrlPath(), ['order_id' => $this->getOrder()->getRealOrderId()]);
	}

	/**
	 * The URI to return to in case a customer decided not to cancel the order.
	 *
	 * @param  string
	 * @return string
	 */
	public function getBackUrl()
	{
		return $this->_coreHttp->getHttpReferer();
	}

	/**
	 * @see Mage_Core_Block_Abstract::getHelper()
	 * Returns an ebayenterprise_order/data helper instance.
	 *
	 * @return EbayEnterprise_Order_Helper_Data
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getHelper($type)
	{
		return $this->_orderHelper;
	}

	/**
	 * Return the self::HELPER_CLASS class constant.
	 *
	 * @return string
	 */
	public function getHelperClass()
	{
		return static::HELPER_CLASS;
	}

	/**
	 * Determine the cancel order URL path based the customer logging status.
	 *
	 * @return string
	 */
	protected function _getCancelUrlPath()
	{
		return $this->_getSession()->isLoggedIn()
			? static::LOGGED_IN_CANCEL_URL_PATH
			: static::GUEST_CANCEL_URL_PATH;
	}
}
