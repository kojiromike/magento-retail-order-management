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

class EbayEnterprise_Eb2cCore_Test_Helper_ShippingTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /** @var EbayEnterprise_Eb2cCore_Helper_Shipping */
    protected $shippingHelper;

    public function setUp()
    {
        $logContext = $this->getHelperMock('ebayenterprise_magelog/context', ['getMetaData']);
        $logContext->method('getMetaData')->will($this->returnValue([]));

        $config = $this->buildCoreConfigRegistry([
            'shippingMethodMap' => [
                'carrier1_methodA' => 'SDK_METHODA',
                'carrier2_methodB' => 'SDK_METHODB'
            ],
        ]);
        $carriers = [
            'carrier1' => $this->mockCarrier('StarMail'),
            'carrier2' => $this->mockCarrier('SpaceFallOne')
        ];
        $this->shippingConfig = $this->getModelMockBuilder('shipping/config')
            ->disableOriginalConstructor()
            ->setMethods(['getActiveCarriers'])
            ->getMock();
        $this->shippingConfig->expects($this->any())
            ->method('getActiveCarriers')
            ->will($this->returnValue($carriers));
        $this->shippingHelper = $this->getHelperMockBuilder('eb2ccore/shipping')
            ->setMethods(['getShippingConfig'])
            ->setConstructorArgs([['config' => $config, 'log_context' => $logContext]])
            ->getMock();
        $this->shippingHelper->expects($this->once())
            ->method('getShippingConfig')
            ->will($this->returnValue($this->shippingConfig));
    }

    /**
     * verify the first shipping method code found is returned
     *
     */
    public function testGetUsableMethod()
    {
        $this->assertSame(
            'carrier1_methodA',
            $this->shippingHelper->getUsableMethod(Mage::getModel('sales/quote_address'))
        );
    }

    /**
     * verify if the address has a shipping method, return the address' shipping method
     *
     */
    public function testGetUsableMethodFromAddress()
    {
        $this->assertSame(
            'carrier2_methodB',
            $this->shippingHelper->getUsableMethod(
                Mage::getModel('sales/quote_address', ['shipping_method' => 'carrier2_methodB'])
            )
        );
    }

    /**
     * verify
     * - returns a string for the carrier and shipping method
     */
    public function testGetMethodTitle()
    {
        $this->assertSame(
            'SpaceFallOne - ludicrously speedy',
            $this->shippingHelper->getMethodTitle('carrier2_methodB')
        );
    }

    /**
     * verify if the shipping method has a mapping, the mapped ROM method id is returned
     */
    public function testGetMethodSdkIdMethodExists()
    {
        $this->assertSame(
            'SDK_METHODA',
            $this->shippingHelper->getMethodSdkId('carrier1_methodA')
        );
    }

    /**
     * verify if the shipping method does not have a mapping, an exception is thrown
     */
    public function testGetMethodSdkIdMethodNotExists()
    {
        $this->setExpectedException('EbayEnterprise_Eb2cCore_Exception');
        $this->shippingHelper->getMethodSdkId('no_such_shipping_method');
    }

    /**
     * verify if the shipping method does not have a mapping, an exception is thrown
     */
    public function testGetMethodSdkIdEmptyMethod()
    {
        $this->setExpectedException('EbayEnterprise_Eb2cCore_Exception');
        $this->shippingHelper->getMethodSdkId('');
    }

    /**
     * mock up a carrier mdoel
     *
     * @param string
     * @param array $methods shipping method array
     * @return Mage_Shipping_Model_Carrier_Abstract
     */
    protected function mockCarrier($title, $methods = null)
    {
        $methods = $methods ?: ['methodA' => 'warp speed', 'methodB' => 'ludicrously speedy'];
        $carrierStub = $this->getMockBuilder('Mage_Shipping_Model_Carrier_Abstract')
            ->disableOriginalConstructor()
            ->setMethods(['getAllowedMethods', 'getConfigData', 'setStore'])
            ->getMockForAbstractClass();
        $carrierStub->expects($this->once())
            ->method('getAllowedMethods')
            ->will($this->returnValue($methods));
        $carrierStub->expects($this->once())
            ->method('getConfigData')
            ->with($this->identicalTo('title'))
            ->will($this->returnValue($title));
        $carrierStub->expects($this->once())
            ->method('setStore')
            ->with($this->anything())
            ->will($this->returnSelf());
        return $carrierStub;
    }
}
