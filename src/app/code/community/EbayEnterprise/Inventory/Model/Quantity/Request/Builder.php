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

use eBayEnterprise\RetailOrderManagement\Payload\Inventory\IQuantityRequest;

class EbayEnterprise_Inventory_Model_Quantity_Request_Builder
{
	/** @var Mage_Sales_Model_Quote_Item[] */
	protected $_items;
	/** @var IQuantityRequest */
	protected $_quantityPayload;
	/** @var EbayEnterprise_Inventory_Helper_Quantity_Payload */
	protected $_payloadHelper;
	/** @var bool */
	protected $_isPayloadPopulated = false;

	/**
	 * @param array $args Must contain:
	 *                    - items => Mage_Sales_Model_Quote_Item_Abstract[]
	 *                    - request_payload => IQuantityRequest
	 *                    May contain:
	 *                    - payload_helper => EbayEnterprise_Inventory_Helper_Quantity_Payload
	 */
	public function __construct(array $args=[])
	{
		list(
			$this->_items,
			$this->_quantityPayload,
			$this->_payloadHelper
		) = $this->_checkTypes(
			$args['items'],
			$args['request_payload'],
			$this->_nullCoalesce($args, 'payload_helper', Mage::helper('ebayenterprise_inventory/quantity_payload'))
		);
	}

	/**
	 * Enforce type checks on constructor init params.
	 *
	 * @param Mage_Sales_Model_Quote_Item_Abstract[]
	 * @param IQuantityRequest
	 * @param EbayEnterprise_Inventory_Helper_Quantity_Payload
	 * @return array
	 */
	protected function _checkTypes(
		array $items,
		IQuantityRequest $quantityPayload,
		EbayEnterprise_Inventory_Helper_Quantity_Payload $payloadHelper
	) {
		return func_get_args();
	}

	/**
	 * Fill in default values.
	 *
	 * @param string
	 * @param array
	 * @param mixed
	 * @return mixed
	 */
	protected function _nullCoalesce(array $arr, $key, $default)
	{
		return isset($arr[$key]) ? $arr[$key] : $default;
	}

	/**
	 * Get the populated quantity request payload.
	 *
	 * @return IQuantityRequest
	 */
	public function getRequest()
	{
		if (!$this->_isPayloadPopulated) {
			$this->_populatePayload();
		}
		return $this->_quantityPayload;
	}

	/**
	 * Inject data from the quote into the quantity request
	 * payload.
	 *
	 * @return self
	 */
	protected function _populatePayload()
	{
		$itemIterable = $this->_quantityPayload->getQuantityItems();
		foreach ($this->_items as $item) {
			$itemPayload = $this->_payloadHelper->itemToRequestQuantityItem(
				$item,
				$itemIterable->getEmptyQuantityItem()
			);
			$itemIterable[$itemPayload] = $itemPayload;
		}
		$this->_quantityPayload->setQuantityItems($itemIterable);
		$this->_isPayloadPopulated = true;
		return $this;
	}
}
