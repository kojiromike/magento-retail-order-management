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

use eBayEnterprise\RetailOrderManagement\Payload\Order\IShipGroup;
use eBayEnterprise\RetailOrderManagement\Payload\TaxDutyFee\IShipGroup as ITaxShipGroup;

class EbayEnterprise_Eb2cCore_Helper_Shipping_Chargetype
{
    /**
     * The Shipping Charge Type recognized by ROM for flatrate/order level shipping costs
     */
    const SHIPPING_CHARGE_TYPE_FLATRATE = 'FLATRATE';

    /**
     * set the shipping chargetype on the supplied shipgroup payload
     * @param  IShipGroup $shipGroup
     * @return self
     */
    public function setShippingChargeType(IShipGroup $shipGroup)
    {
        // Use flatrate because the way shipping costs are calculated
        // in Magento makes it impossible to determine how much each
        // item contributes to the shipping cost.
        $shipGroup->setChargeType(self::SHIPPING_CHARGE_TYPE_FLATRATE);
        return $this;
    }
}
