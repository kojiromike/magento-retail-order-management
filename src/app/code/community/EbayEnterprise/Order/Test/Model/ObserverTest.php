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

use eBayEnterprise\RetailOrderManagement\Payload;
use eBayEnterprise\RetailOrderManagement\Payload\OrderEvents;

class EbayEnterprise_Order_Test_Model_ObserverTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /**
     * Validate expected event configuration.
     *
     * @dataProvider dataProvider
     */
    public function testEventSetup($area, $eventName, $observerClassAlias, $observerMethod)
    {
        $this->_testEventConfig($area, $eventName, $observerClassAlias, $observerMethod);
    }

    /**
     * Test the 'process' method is called by the observer
     */
    public function testAmqpMessageCreditIssuedObserverCallsProcess()
    {
        $factory = new Payload\PayloadFactory();
        $payload = $factory->buildPayload('\eBayEnterprise\RetailOrderManagement\Payload\OrderEvents\OrderCreditIssued');

        $credit = $this->getModelMockBuilder('ebayenterprise_order/creditissued')
            ->addMethod('process')
            ->setConstructorArgs([['payload' => $payload]]);
        $credit->expects($this->once())
            ->method('process')
            ->willReturn($credit);
        $this->replaceByMock('model', 'ebayenterprise_order/creditissued', $credit);

        $eventObserver = $this->_buildEventObserver(['message' => '<OrderEvents/>']);
        $observer = Mage::getModel('ebayenterprise_order/observer');
        $observer->handleEbayEnterpriseAmqpMessageOrderCreditIssued($eventObserver);
    }
}
