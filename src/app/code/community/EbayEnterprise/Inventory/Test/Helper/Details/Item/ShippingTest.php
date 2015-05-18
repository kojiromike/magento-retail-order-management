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

class EbayEnterprise_Inventory_Test_Helper_Details_Item_ShippingTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    public function setUp()
    {
        $coreHelper = $this->getHelperMock('eb2ccore/data', ['lookupShipMethod']);
        $coreHelper->expects($this->any())
            ->method('lookupShipMethod')
            ->with($this->isType('string'))
            ->will($this->returnValueMap([
                ['carrier1_methodA', 'SDK_METHODA'],
                ['carrier2_methodB', 'SDK_METHODB']
            ]));
        $carriers = ['carrier1' => $this->mockCarrier(), 'carrier2' => $this->mockCarrier()];
        $this->shippingConfig = $this->getModelMockBuilder('shipping/config')
            ->disableOriginalConstructor()
            ->setMethods(['getActiveCarriers'])
            ->getMock();
        $this->shippingConfig->expects($this->any())
            ->method('getActiveCarriers')
            ->will($this->returnValue($carriers));
        $this->shippingHelper = $this->getHelperMock('ebayenterprise_inventory/details_item_shipping', ['getShippingConfig', 'getCarrierTitle']);
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($this->shippingHelper, 'coreHelper', $coreHelper);
        $this->shippingHelper->expects($this->once())
            ->method('getShippingConfig')
            ->will($this->returnValue($this->shippingConfig));
        $this->shippingHelper->expects($this->any())
            ->method('getCarrierTitle')
            ->will($this->returnValueMap([
                ['carrier1', 'StarMail'],
                ['carrier2', 'SpaceFallOne'],
            ]));
    }

    /**
     * verify the first shipping method code found is returned
     *
     */
    public function testGetUsableMethod()
    {
        $this->assertSame('carrier1_methodA', $this->shippingHelper->getUsableMethod(Mage::getModel('sales/quote_address')));
    }

    /**
     * verify if the address has a shipping method, return the address' shipping method
     *
     */
    public function testGetUsableMethodFromAddress()
    {
        $this->assertSame('carrier2_methodB', $this->shippingHelper->getUsableMethod(Mage::getModel('sales/quote_address', ['shipping_method' => 'carrier2_methodB'])));
    }

    /**
     * verify
     * - returns a string for the carrier and shipping method
     */
    public function testGetMethodTitle()
    {
        $this->assertSame('SpaceFallOne ludicrous speed', $this->shippingHelper->getMethodTitle('carrier2_methodB'));
    }

    /**
     * mock up a carrier mdoel
     *
     * @param array $methods shipping method array
     * @return Mage_Shipping_Model_Carrier_Abstract
     */
    protected function mockCarrier($methods = null)
    {
        $methods = $methods ?: ['methodA' => 'warp speed', 'methodB' => 'ludicrous speed'];
        $carrierStub = $this->getMockBuilder('Mage_Shipping_Model_Carrier_Abstract')
            ->disableOriginalConstructor()
            ->setMethods(['getAllowedMethods'])
            ->getMockForAbstractClass();
        $carrierStub->expects($this->once())
            ->method('getAllowedMethods')
            ->will($this->returnValue($methods));
        return $carrierStub;
    }
}
