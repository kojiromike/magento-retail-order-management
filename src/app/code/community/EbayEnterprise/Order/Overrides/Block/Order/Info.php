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

class EbayEnterprise_Order_Overrides_Block_Order_Info extends Mage_Sales_Block_Order_Info
{
	const OVERRIDDEN_TEMPLATE = 'ebayenterprise_order/order/info.phtml';
	const LOGGED_IN_CANCEL_URL_PATH = 'sales/order/romcancel';
	const GUEST_CANCEL_URL_PATH = 'sales/order/romguestcancel';
	const HELPER_CLASS = 'ebayenterprise_order';

	/** @var EbayEnterprise_Order_Helper_Data */
	protected $_orderHelper;
	/** @var EbayEnterprise_Order_Helper_Factory */
	protected $_factory;

	public function __construct(array $initParams=[])
	{
		list($this->_orderHelper, $this->_factory) = $this->_checkTypes(
			$this->_nullCoalesce($initParams, 'order_helper', Mage::helper('ebayenterprise_order')),
			$this->_nullCoalesce($initParams, 'factory', Mage::helper('ebayenterprise_order/factory'))
		);
		parent::__construct($this->_removeKnownKeys($initParams));
	}

	/**
	 * Remove the all the require and optional keys from the $initParams
	 * parameter.
	 *
	 * @param  array
	 * @return array
	 */
	protected function _removeKnownKeys(array $initParams)
	{
		foreach (['order_helper', 'factory'] as $key) {
			if (isset($initParams[$key])) {
				unset($initParams[$key]);
			}
		}
		return $initParams;
	}

	/**
	 * Type hinting for self::__construct $initParams
	 *
	 * @param  EbayEnterprise_Order_Helper_Data
	 * @param  EbayEnterprise_Order_Helper_Factory
	 * @return array
	 */
	protected function _checkTypes(
		EbayEnterprise_Order_Helper_Data $orderHelper,
		EbayEnterprise_Order_Helper_Factory $factory
	)
	{
		return [$orderHelper, $factory];
	}

	/**
	 * Return the value at field in array if it exists. Otherwise, use the default value.
	 *
	 * @param  array
	 * @param  string $field Valid array key
	 * @param  mixed
	 * @return mixed
	 */
	protected function _nullCoalesce(array $arr, $field, $default)
	{
		return isset($arr[$field]) ? $arr[$field] : $default;
	}

	protected function _construct()
	{
		// We have to have a constructor to preserve our template, because the parent constructor sets it.
		parent::_construct();
		$this->setTemplate(self::OVERRIDDEN_TEMPLATE);
	}

	/**
	 * Retrieve current order model instance
	 * @return Mage_Sales_Model_Order
	 */
	public function getOrder()
	{
		return Mage::registry('rom_order');
	}

	/**
	 * @see Mage_Core_Block_Abstract::getHelper()
	 *
	 * @param  string
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
	 * Returns URL to cancel an order.
	 * @param  string
	 * @return string
	 */
	public function getCancelUrl($orderId)
	{
		return $this->getUrl($this->_getCancelUrlPath(), ['order_id' => $orderId]);
	}

	/**
	 * Determine the cancel order URL path based the customer logging status.
	 *
	 * @return string
	 */
	protected function _getCancelUrlPath()
	{
		return $this->_factory->getCustomerSession()->isLoggedIn()
			? static::LOGGED_IN_CANCEL_URL_PATH
			: static::GUEST_CANCEL_URL_PATH;
	}

	public function addLink($name, $path, $label)
	{
		$this->_links[$name] = $this->_factory->getNewVarienObject([
			'name' => $name,
			'label' => $label,
			'url' => empty($path) ? '' : $this->getUrl($path, ['order_id' => $this->getOrder()->getRealOrderId()])
		]);
		return $this;
	}
}
