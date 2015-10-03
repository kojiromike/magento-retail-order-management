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

use eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderItemReference;

/**
 * Encapsulate an IOrderItemReference payload with the Magento order item
 * being referenced.
 */
class EbayEnterprise_Order_Model_Create_Itemreferencepair
{
    /** @var Mage_Sales_Model_Order_Item */
    protected $item;
    /** @var IOrderItemReference */
    protected $payload;

    /**
     * @param array $init Must contain:
     *                    - item => Mage_Sales_Model_Order_Item
     *                    - payload => IOrderItemReference
     */
    public function __construct(array $init)
    {
        list(
            $this->item,
            $this->payload
        ) = $this->checkTypes(
            $init['item'],
            $init['payload']
        );
    }

    /**
     * @param Mage_Sales_Model_Order_Item
     * @param IOrderItemReference
     * @return array
     */
    protected function checkTypes(
        Mage_Sales_Model_Order_Item $item,
        IOrderItemReference $payload
    ) {
        return func_get_args();
    }

    /**
     * @return Mage_Sales_Model_Order_Item
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * @return IOrderItemReference
     */
    public function getPayload()
    {
        return $this->payload;
    }
}
