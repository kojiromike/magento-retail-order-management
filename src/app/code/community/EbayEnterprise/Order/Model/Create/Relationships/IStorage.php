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

use eBayEnterprise\RetailOrderManagement\Payload\Order\IItemRelationship;

/**
 * keeps track of relationship payloads
 */
interface EbayEnterprise_Order_Model_Create_Relationships_IStorage
{
    /**
     * Get a relationship object for $item.
     * return null if the item cannot have a relationship
     *
     * @param Mage_Sales_Model_Order_Item
     * @return IItemRelationShip|null
     */
    public function getRelationshipPayload(Mage_Sales_Model_Order_Item $item);

    /**
     * store a parent item and its relationship payload
     *
     * @param Mage_Sales_Model_Order_Item
     * @param IItemRelationship
     */
    public function addRelationship(
        Mage_Sales_Model_Order_Item $item,
        IItemRelationship $relationship
    );
}
