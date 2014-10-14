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

class EbayEnterprise_GiftCard_Model_Adminhtml_Observer
{
	// post data fields for gift cards
	const CARD_NUMBER_PARAM = 'ebay_enterprise_giftcard_code';
	const CARD_PIN_PARAM = 'ebay_enterprise_giftcard_pin';
	const ACTION_PARAM = 'ebay_enterprise_giftcard_action';
	// action "flags" expected to be sent in the post data
	const ADD_ACTION = 'add';
	const REMOVE_ACTION = 'remove';
	/** @var array post data */
	protected $_request;
	/** @var EbayEnterprise_GiftCard_Model_IContainer */
	protected $_container;
	/** @var EbayEnterprise_MageLog_Helper_Data */
	protected $_logger;
	public function __construct()
	{
		$this->_container = Mage::getModel('ebayenterprise_giftcard/container');
		$this->_helper = Mage::helper('ebayenterprise_giftcard');
		$this->_logger = Mage::helper('ebayenterprise_magelog');
	}
	/**
	 * Process post data and set usage of GC into order creation model
	 *
	 * @param Varien_Event_Observer $observer
	 * @return self
	 */
	public function processOrderCreationData(Varien_Event_Observer $observer)
	{
		if ($this->_helper->getConfigModel()->isEnabled) {
			$this->_request = $observer->getEvent()->getRequest();
			list($cardNumber, $pin) = $this->_getCardInfoFromRequest();
			if ($cardNumber) {
				$this->_processCard($cardNumber, $pin);
			}
		}
		return $this;
	}
	/**
	 * Add or remove the gift card, depending on the requested action.
	 * @param string $cardNumber
	 * @param string $pin
	 * @return self
	 */
	protected function _processCard($cardNumber, $pin)
	{
		if ($this->_isAddRequest()) {
			$this->_addGiftCard($cardNumber, $pin);
		} elseif ($this->_isRemoveRequest()) {
			$this->_removeGiftCard($cardNumber);
		}
		return $this;
	}
	/**
	 * Is the gift card action param in the request for an add.
	 * @return boolean
	 */
	protected function _isAddRequest()
	{
		return $this->_getPostData(self::ACTION_PARAM, '') === self::ADD_ACTION;
	}
	/**
	 * Is the gift card action param in the request for a remove.
	 * @return boolean
	 */
	protected function _isRemoveRequest()
	{
		return $this->_getPostData(self::ACTION_PARAM, '') === self::REMOVE_ACTION;
	}
	/**
	 * add a giftcard.
	 * @param string $cardNumber
	 * @param string $pin
	 * @return self
	 */
	public function _addGiftCard($cardNumber, $pin)
	{
		$this->_logger->logDebug('[%s] Adding gift card %s', array(__CLASS__, $cardNumber));
		$giftcard = $this->_container->getGiftCard($cardNumber)->setPin($pin);
		$this->_helper->addGiftCardToOrder($giftcard);
		return $this;
	}
	/**
	 * remove a giftcard.
	 * @param string $cardNumber
	 * @return self
	 */
	protected function _removeGiftCard($cardNumber)
	{
		$this->_logger->logDebug('[%s] Removing gift card %s', array(__CLASS__, $cardNumber));
		$giftcard = $this->_container->getGiftCard($cardNumber);
		$this->_container->removeGiftCard($giftcard);
		return $this;
	}
	/**
	 * Extract the card number and pin from the request. If either is not present,
	 * will return an empty string for that value.
	 * @return string[] Tuple of card number and pin
	 */
	protected function _getCardInfoFromRequest()
	{
		return array($this->_getPostData(self::CARD_NUMBER_PARAM, ''), $this->_getPostData(self::CARD_PIN_PARAM, ''));
	}
	/**
	 * Get post data from the request. If not set, return the default value.
	 * @param string|int $field
	 * @param string $default
	 * @return string
	 */
	protected function _getPostData($field, $default)
	{
		return isset($this->_request[$field]) ? $this->_request[$field] : $default;
	}
}
