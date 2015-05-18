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

use eBayEnterprise\RetailOrderManagement\Payload\Inventory\IInventoryDetailsReply;
use eBayEnterprise\RetailOrderManagement\Payload\Inventory\IItemIterable;

class EbayEnterprise_Inventory_Helper_Details_Response
{
    /** @var EbayEnterprise_Inventory_Helper_Details_Item */
    protected $itemHelper;
    /** @var EbayEnterprise_Inventory_Helper_Details_Factory */
    protected $factory;

    public function __construct($init = [])
    {
        list($this->itemHelper, $this->factory) = $this->checkTypes(
            $this->nullCoalesce($init, 'item_helper', Mage::helper('ebayenterprise_inventory/details_item')),
            $this->nullCoalesce($init, 'factory', Mage::helper('ebayenterprise_inventory/details_factory'))
        );
    }

    /**
     * ensure dependency types
     *
     * @param EbayEnterprise_Inventory_Helper_Details_Item
     * @param EbayEnterprise_Inventory_Helper_Details_Factory
     * @return array
     */
    protected function checkTypes(
        EbayEnterprise_Inventory_Helper_Details_Item $itemHelper,
        EbayEnterprise_Inventory_Helper_Details_Factory $factory
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
    protected function nullCoalesce(array $arr, $key, $default)
    {
        return isset($arr[$key]) ? $arr[$key] : $default;
    }

    /**
     * parse and store the data from the response into the session
     *
     * @param IInventoryDetailsReply
     * @return EbayEnterprise_Inventory_Model_Details_Result
     */
    public function exportResultData(IInventoryDetailsReply $reply)
    {
        return $this->buildResultFromReply($reply);
    }

    /**
     * build a result object with the data from the reply
     *
     * @param IInventoryDetailsReply
     * @return EbayEnterprise_Inventory_Model_Details_Result
     */
    protected function buildResultFromReply(IInventoryDetailsReply $reply)
    {
        return $this->factory->createResult(
            $this->extractDataFromReply($reply->getDetailItems(), [$this->itemHelper, 'extractItemDetails']),
            $this->extractDataFromReply($reply->getUnavailableItems(), [$this->itemHelper, 'extractItemIdentification'])
        );
    }

    /**
     * extract data for each individual item from an iterable payload
     *
     * @param IItemIterable
     * @param callable
     * @return array
     */
    protected function extractDataFromReply(IItemIterable $iterable, callable $extractor)
    {
        $dataSet = [];
        foreach ($iterable as $itemPayload) {
            $dataSet[] = $extractor($itemPayload);
        }
        return $dataSet;
    }
}
