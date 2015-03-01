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

class EbayEnterprise_Eb2cOrder_Helper_Event
{
	// prefix for OrderEvent event names in Magento
	const EVENT_PREFIX = 'ebayenterprise_order_event_';
	// XPath to get the type of event from a message
	const EVENT_TYPE_XPATH = 'string(/*/*/@EventType)';
	// @var EbayEnterprise_Eb2cCore_Helper_Data
	protected $_coreHelper;
	// @var EbayEnterprise_MageLog_Helper_Data
	protected $_logger;
	/** @var EbayEnterprise_MageLog_Helper_Context */
	protected $_context;

	/**
	 * Set up dependencies
	 */
	public function __construct()
	{
		$this->_coreHelper = Mage::helper('eb2ccore');
		$this->_logger = Mage::helper('ebayenterprise_magelog');
		$this->_context = Mage::helper('ebayenterprise_magelog/context');
	}
	/**
	 * Get the specific name of the order event to dispatch for a given message.
	 * @param  string $message Sting of XML order event
	 * @return string
	 */
	public function getMessageEventName($message)
	{
		return self::EVENT_PREFIX . $this->_coreHelper->underscoreWords(
			$this->_extractOrderEventNameFromMessage($this->_getDomDocumentForMessage($message))
		);
	}
	/**
	 * Get a new EbayEnterprise_Dom_Document with the given message loaded.
	 * @param  string $message
	 * @return EbayEnterprise_Dom_Document
	 */
	protected function _getDomDocumentForMessage($message)
	{
		$doc = $this->_coreHelper->getNewDomDocument();
		// Attempt to load the message. If the message fails to load, log an error
		// but still return the empty document, allowing the attempt to extract
		// an event type from the message to fail and throw an appropriate
		// exception.
		if (!$doc->loadXML($message)) {
			$logData = ['error_message' => $message];
			$logMessage = "Failed to load message into DOM: \n{error_message}";
			$this->_logger->error($logMessage, $this->_context->getMetaData(__CLASS__, $logData));
		}
		return $doc;
	}
	/**
	 * Extract the name of the order event from the order event XML.
	 * @param  string $message OrderEvent XML
	 * @return string
	 * @throws EbayEnterprise_Amqp_Exception_Invalid_Message If message could not be parsed for event name
	 */
	protected function _extractOrderEventNameFromMessage(DOMDocument $messageDoc)
	{
		$xpath = $this->_coreHelper->getNewDomXPath($messageDoc);
		$eventType = $xpath->evaluate(self::EVENT_TYPE_XPATH);
		if ($eventType) {
			return $eventType;
		}
		throw new EbayEnterprise_Amqp_Exception_Invalid_Message('Could not extract event name from message.');
	}
	/**
	 * Attempt to cancel an order.
	 * @param  Mage_Sales_Model_Order $order
	 * @param  string                 $eventName
	 * @return self
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function attemptCancelOrder(Mage_Sales_Model_Order $order, $eventName)
	{
		$logData = ['increment_id' => $order->getIncrementId()];
		try {
			$order->cancel()->save();
			$logMessage = 'Canceling order {increment_id}';
			$this->_logger->info($logMessage, $this->_context->getMetaData(__CLASS__, $logData));
		} catch (Exception $e) {
			// Catching any exception that might be thrown due to calling cancel method on the order object.
			$logMessage = 'An error occurred canceling order "{increment_id}". See the exception log for details.';
			$this->_logger->warning($logMessage, $this->_context->getMetaData(__CLASS__, $logData));
			$this->_logger->logException($e, $this->_context->getMetaData(__CLASS__, [], $e));
		}
		return $this;
	}
	/**
	 * Attempt to hold an order.
	 * @param  Mage_Sales_Model_Order $order
	 * @param  string                 $eventName
	 * @return self
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function attemptHoldOrder(Mage_Sales_Model_Order $order, $eventName)
	{
		$logData = ['increment_id' => $order->getIncrementId()];
		try {
			$order->hold()->save();
			$logMessage = 'Holding order {increment_id}';
			$this->_logger->info($logMessage, array(__CLASS__, $logData));
		} catch (Exception $e) {
			$logMessage = 'An error occurred holding order "{increment_id}". See the exception log for details.';
			$this->_logger->warning($logMessage, $this->_context->getMetaData(__CLASS__, $logData));
			$this->_logger->logException($e, $this->_context->getMetaData(__CLASS__, [], $e));
		}
		return $this;
	}
}
