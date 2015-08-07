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

class EbayEnterprise_Order_Block_Order_Shipment_Tracking extends Mage_Core_Block_Template
{
    /**
     * Retrieve array of tracking info
     *
     * @return array
     */
    public function getTrackingInfo()
    {
        /** @var EbayEnterprise_Order_Model_Tracking | null */
        $tracking = Mage::registry('rom_order_shipment_tracking');
        return $tracking ? $tracking->getTrackingData() : [];
    }
}
