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
use eBayEnterprise\RetailOrderManagement\Payload\PayloadFactory;

class EbayEnterprise_Order_Test_Model_CreditissuedTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    const PAYLOAD_CUSTOMER_ORDER_ID = '10000003';
    const PAYLOAD_STORE_ID = 'GTA36';
    const PAYLOAD_CURRENCY_CODE = 'USD';
    const PAYLOAD_CURRENCY_SYMBOL = '$';
    const PAYLOAD_ORDER_CREATE_TIMESTAMP = '2014-11-26T08:09:33-04:00';
    const PAYLOAD_REASON = 'Testing invalid payment reason message';
    const PAYLOAD_CODE = 'Invalid Payment';

    /** @var OrderEvents\OrderCreditIssued $_payload */
    protected $_payload;
    /** @var  EbayEnterprise_Order_Model_CreditIssued $_creditissued */
    protected $_ceditissued;

    public function setUp()
    {
        parent::setUp();
        $this->_payloadFactory = new PayloadFactory();
        $this->_payload = $this->_payloadFactory->buildPayload('\eBayEnterprise\RetailOrderManagement\Payload\OrderEvents\OrderCreditIssued');

        $this->_payload->setCustomerOrderId(static::PAYLOAD_CUSTOMER_ORDER_ID)
            ->setStoreId(static::PAYLOAD_STORE_ID)
            ->setCurrencyCode(static::PAYLOAD_CURRENCY_CODE)
            ->setCurrencySymbol(static::PAYLOAD_CURRENCY_SYMBOL);

        // suppressing the real session from starting
        $session = $this->getModelMockBuilder('core/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->replaceByMock('singleton', 'core/session', $session);

        $this->_creditissued = Mage::getModel('ebayenterprise_order/creditissued', [
            'payload' => $this->_payload
        ]);
        ;
    }

    /**
     * @param string $name
     * @param $object
     * @param string $property
     * @param $value
     */
    protected function _injectProtectedProperty($name, $object, $property, $value)
    {
        $reflection = new ReflectionClass($name);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($object, $value);

        return $object;
    }

    /**
     * @param $orderId
     * @param $entityId
     * @param $expectedReturn
     * @dataProvider dataProvider
     */
    public function testGetOrder($orderId, $entityId, $expectedReturn)
    {
        $order = $this->getModelMock('sales/order', ['getIncrementId'], false, [['entity_id' => $entityId]]);
        $this->replaceByMock('model', 'sales/order', $order);

        $credit = Mage::getModel('ebayenterprise_order/creditissued', ['payload' => $this->_payload]);
        $returned = EcomDev_Utils_Reflection::invokeRestrictedMethod($credit, '_getOrder', [$orderId]);
        if (empty($expectedReturn)) {
            $this->assertFalse($returned);
        } else {
            $this->assertInstanceOf('Mage_Sales_Model_Order', $returned);
        }
    }

    /**
     * @param string $payload
     * @param array $itemData
     * @param array $expectedReturn
     * @dataProvider dataProvider
     */
    public function testCreditmemoInitData($payload, array $itemData, $expectedReturn)
    {
        $this->_payload->deserialize(file_get_contents(__DIR__ . '/CreditissuedTest/fixtures/' . $payload));

        $collection = Mage::helper('eb2ccore')->getNewVarienDataCollection();
        foreach ($itemData as $data) {
            $collection->addItem(Mage::getModel('sales/order_item', $data));
        }

        $order = $this->getModelMock('sales/order', ['getItemsCollection']);
        $order->expects($this->any())
            ->method('getItemsCollection')
            ->willReturn($collection);
        $this->replaceByMock('model', 'sales/order', $order);

        $credit = Mage::getModel('ebayenterprise_order/creditissued', ['payload' => $this->_payload]);

        $order = $this->_injectProtectedProperty('EbayEnterprise_Order_Model_Creditissued', $credit, '_order', $order);

        $actual = EcomDev_Utils_Reflection::invokeRestrictedMethod($credit, '_creditmemoInitData');
        $this->assertSame($expectedReturn, $actual);
    }

    /**
     * @param float $subtotal
     * @param float $grandTotal
     * @param float $expectedReturn
     * @dataProvider dataProvider
     */
    public function testFixupTotals($subtotal, $grandTotal, $expectedReturn)
    {
        $this->_payload->setTotalCredit($grandTotal);
        $memo = $this->getModelMock('sales/order_creditmemo', ['getBaseSubtotal']);
        $memo->expects($this->any())
            ->method('getBaseSubtotal')
            ->willReturn($subtotal);
        $this->replaceByMock('model', 'sales/order_creditmemo', $memo);

        $credit = Mage::getModel('ebayenterprise_order/creditissued', ['payload' => $this->_payload]);
        $returned = EcomDev_Utils_Reflection::invokeRestrictedMethod($credit, '_fixupTotals', [$memo]);
        $actual = [
            'baseGrandTotal' => $returned->getBaseGrandTotal(),
            'grandTotal' => $returned->getGrandTotal(),
            'adjustmentNegative' => $returned->getBaseAdjustmentNegative(),
            'adjustmentPositive' => $returned->getBaseAdjustmentPositive(),
        ];
        $this->assertEquals($expectedReturn, $actual);
    }
}
