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

use eBayEnterprise\RetailOrderManagement\Payload\Inventory\IQuantityReply;
use eBayEnterprise\RetailOrderManagement\Payload\Inventory\IQuantityRequest;

class EbayEnterprise_Inventory_Helper_Quantity_Factory
{
    /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
    protected $_inventoryConfig;
    /** @var EbayEnterprise_Inventory_Helper_Quantity */
    protected $_quantityHelper;

    public function __construct()
    {
        $this->_inventoryConfig = Mage::helper('ebayenterprise_inventory')->getConfigModel();
        $this->_quantityHelper = Mage::helper('ebayenterprise_inventory/quantity');
    }

    /**
     * Create a new quantity model with the provided
     * data.
     *
     * @param string
     * @param int
     * @param int
     * @return EbayEnterprise_Inventory_Model_Quantity
     */
    public function createQuantity($sku, $itemId, $quantity)
    {
        return Mage::getModel(
            'ebayenterprise_inventory/quantity',
            ['sku' => $sku, 'item_id' => $itemId, 'quantity' => $quantity]
        );
    }

    /**
     * Create an inventory quantity request builder.
     *
     * @param IQuantityRequest
     * @param Mage_Sales_Model_Quote_Item_Abstract[]
     * @return EbayEnterprise_Inventory_Model_Quantity_Request_Builder
     */
    public function createRequestBuilder(IQuantityRequest $requestBody, array $items)
    {
        return Mage::getModel(
            'ebayenterprise_inventory/quantity_request_builder',
            [
                'request_payload' => $requestBody,
                'items' => $items,
            ]
        );
    }

    /**
     * Create an inventory quantity response parser.
     *
     * @param IQuantityReply
     * @return EbayEnterprise_Inventory_Model_Quantity_Response_Parser
     */
    public function createResponseParser(IQuantityReply $responseBody)
    {
        return Mage::getModel(
            'ebayenterprise_inventory/quantity_response_parser',
            ['quantity_response' => $responseBody]
        );
    }

    /**
     * Create a quantity results model with the provided quantity models,
     * sku quantity data from the provided items and an expiration time based
     * on the current time offset by the configured quantity cache lifetime.
     *
     * @param EbayEnterprise_Inventory_Model_Quantity[]
     * @param Mage_Sales_Model_Quote_Item_Abstract[]
     * @return EbayEnterprise_Inventory_Model_Quantity_Result
     */
    public function createQuantityResults(array $quantityResults, array $requestedItems)
    {
        return Mage::getModel(
            'ebayenterprise_inventory/quantity_results',
            [
                'quantities' => $quantityResults,
                'expiration_time' => $this->_getQuantityExpirationTime(new DateTime),
                'sku_quantity_data' => $this->_quantityHelper->calculateTotalQuantitiesBySku($requestedItems),
            ]
        );
    }

    /**
     * Get the expiration time of the quantity data based upon the configured
     * quantity cache lifetime.
     *
     * @param DateTime
     * @return DateTime
     */
    protected function _getQuantityExpirationTime(DateTime $start)
    {
        $lifetime = (int) $this->_inventoryConfig->quantityCacheLifetime;
        $isNegative = $lifetime < 0;
        $interval = new DateInterval(sprintf('PT%dM', abs($lifetime)));
        $interval->invert = $isNegative;
        return $start->add($interval);
    }
}
