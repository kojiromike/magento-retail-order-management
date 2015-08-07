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

class EbayEnterprise_Order_Test_Block_Order_Shipment_TrackingTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /**
     * Create a block instance
     *
     * @param string
     * @return Mage_Core_Block_Abstract
     */
    protected function _createBlock($class)
    {
        return Mage::app()->getLayout()->createBlock($class);
    }

    /**
     * Scenario: Get tracking information
     * Given an ebayenterprise_order/tracking instance in the registry
     * When getting tracking information
     * Then get ebayenterprise_order/tracking instance from the registry
     * And if we have a valid object, then, invoked the
     * ebayenterprise_order/tracking::getTrackingData() method.
     */
    public function testTrackingInfo()
    {
        /** @var array */
        $result = [];
        Mage::unregister('rom_order_shipment_tracking');
        /** @var EbayEnterprise_Order_Model_Tracking */
        $trackingModel = $this->getModelMockBuilder('ebayenterprise_order/tracking')
            ->setMethods(['getTrackingData'])
            ->disableOriginalConstructor()
            ->getMock();
        $trackingModel->expects($this->once())
            ->method('getTrackingData')
            ->will($this->returnValue($result));
        Mage::register('rom_order_shipment_tracking', $trackingModel);

        /** @var EbayEnterprise_Order_Block_Order_Shipment_Tracking */
        $trackingBlock = $this->_createBlock('ebayenterprise_order/order_shipment_tracking');

        $this->assertSame($result, $trackingBlock->getTrackingInfo());
    }
}
