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
class EbayEnterprise_Order_Model_Create_Relationships_Storage implements
    EbayEnterprise_Order_Model_Create_Relationships_IStorage
{
    /** @var SplObjectStorage */
    protected $relationships;

    public function __construct($init = [])
    {
        list($this->relationships) = $this->checkTypes(
            $this->nullCoalesce($init, 'storage', new SplObjectStorage)
        );
    }


    protected function checkTypes(SplObjectStorage $storage)
    {
        return func_get_args();
    }

    protected function nullCoalesce(array $arr, $key, $default)
    {
        return isset($arr[$key]) ? $arr[$key] : $default;
    }

    /**
     * @see EbayEnterprise_Order_Model_Create_Relationships_IStorage::getRelationShipPayload
     */
    public function getRelationshipPayload(
        Mage_Sales_Model_Order_Item $item
    ) {
        return $this->relationships->contains($item) ? $this->relationships[$item] : null;
    }

    /**
     * @see EbayEnterprise_Order_Model_Create_Relationships_IStorage::addRelationShip
     */
    public function addRelationship(
        Mage_Sales_Model_Order_Item $item,
        IItemRelationship $relationship
    ) {
        $this->relationships->attach($item, $relationship);
        return $this;
    }
}
