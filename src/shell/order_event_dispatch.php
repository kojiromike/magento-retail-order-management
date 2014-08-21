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

require_once 'abstract.php';

/**
 * Utility shell script to run order status events.
 */
class EbayEnterprise_Rom_Shell_Order_Event_Dispatch extends Mage_Shell_Abstract
{
	const BACK_ORDER_PAYLOAD = 'payload/OrderBackordered.xml';
	const REJECTED_PAYLOAD = 'payload/OrderRejected.xml';
	const CANCEL_PAYLOAD = 'payload/OrderCancel.xml';
	const SHIPMENT_PAYLOAD = 'payload/OrderShipments.xml';
	const EVENT_PREFIX = 'ebayenterprise_order_event_';
	/**
	 * Running order status event Shell Script
	 * @see usageHelp
	 * @return int UNIX exit status
	 */
	public function run()
	{
		$incrementId = trim($this->getArg('increment_id'));
		$eventSuffix = trim($this->getArg('event_name'));
		if ($eventSuffix === '' || $incrementId === '') {
			echo $this->usageHelp();
		} else {
			$eventName = static::EVENT_PREFIX . $eventSuffix;
			Mage::dispatchEvent($eventName, array('message' => $this->_getMessage($eventSuffix, $incrementId)));
		}
		return 0;
	}
	/**
	 * Return an array of event suffix mapped to payload template files.
	 * @return array
	 */
	protected function _getEventPayloads()
	{
		return array(
			'back_order' => __DIR__ . DIRECTORY_SEPARATOR . static::BACK_ORDER_PAYLOAD,
			'rejected' => __DIR__ . DIRECTORY_SEPARATOR . static::REJECTED_PAYLOAD,
			'cancel' => __DIR__ . DIRECTORY_SEPARATOR . static::CANCEL_PAYLOAD,
			'shipment_confirmation' => __DIR__ . DIRECTORY_SEPARATOR . static::SHIPMENT_PAYLOAD,
		);
	}
	/**
	 * Get by load message by event prefix and replace increment id place holder
	 * with the passed in increment id.
	 * @param string $eventSuffix
	 * @param string $incrementId
	 * @return string The XML string payload content
	 */
	protected function _getMessage($eventSuffix, $incrementId)
	{
		$payloads = $this->_getEventPayloads();
		$xml = file_get_contents($payloads[$eventSuffix]);
		return str_replace(array('{INCREMENT_ID}', '{SHIPMENT_ID}', '{TRACKING_NUMBER}'), array($incrementId, uniqid('shpmt_'), uniqid('trk_')), $xml);
	}
	/**
	 * @return string how to use this script
	 */
	public function usageHelp()
	{
		$scriptName = basename(__FILE__);
		return <<<USAGE

Usage: php -f $scriptName
	--event_name		Any of following events:
				- back_order
				- rejected
				- cancel
				- shipment_confirmation
	--increment_id		The increment id to generate the event payload for
	help			This help

USAGE;
	}
}
$event = new EbayEnterprise_Rom_Shell_Order_Event_Dispatch();
exit($event->run());
