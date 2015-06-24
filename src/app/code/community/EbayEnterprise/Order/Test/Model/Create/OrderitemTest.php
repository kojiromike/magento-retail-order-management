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

use eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderItem;

class EbayEnterprise_Order_Test_Model_Create_OrderitemTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    const PAYLOAD_CLASS =
        '\eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderItem';
    const PRICEGROUP_PAYLOAD_CLASS =
        '\eBayEnterprise\RetailOrderManagement\Payload\Order\IPriceGroup';

    protected $chargeType = EbayEnterprise_Order_Model_Create::SHIPPING_CHARGE_TYPE_FLATRATE;
    /** @var Mage_Sales_Model_Order */
    protected $itemStub;
    /** @var Mage_Sales_Model_Order_Item */
    protected $orderStub;
    /** @var IOrderItem */
    protected $payload;
    /** @var IPriceGroup */
    protected $priceGroupStub;
    /** @var Mage_Eav_Model_Resource_Entity_Attribute_Option_Collection */
    protected $optionValueCollectionStub;
    protected $shippingHelper;

    public function setUp()
    {
        parent::setUp();
        // replace the session for the logger context
        $this->_replaceSession('core/session');
        $this->itemStub = $this->getModelMock('sales/order_item', ['getId', 'getOrder']);
        $this->orderStub = $this->getModelMock('sales/order', ['load', 'save']);
        $this->addressStub = $this->getModelMock('sales/order_address', ['getId']);
        $this->addressStub->setData(['shipping_method' => 'someshipping method']);
        $this->shippingHelper = $this->getHelperMock('eb2ccore/shipping');

        $this->itemStub->expects($this->any())
            ->method('getOrder')
            ->will($this->returnValue($this->orderStub));

        $this->payload = Mage::helper('eb2ccore')
            ->getSdkApi('orders', 'create')
            ->getRequestBody()
            ->getOrderItems()
            ->getEmptyOrderItem();

        $this->itemStub->addData([
            'name' => 'itemstub',
            'sku' => 'thesku',
            'store_id' => 1,
            'product_options' => serialize([
                'info_buyRequest' => [
                    'super_attribute' => [
                        92 => '15', // fake color
                        3 => '2',   // fake size
                    ]
                ]
            ])
        ]);
        $this->optionValueCollectionStub = $this->getResourceModelMock(
            'eav/entity_attribute_option_collection',
            ['load']
        );
    }

    /**
     * provide the method to run along with the expected values
     * @return array
     */
    public function provideForSizeColorInfo()
    {
        return [
            ['getItemColorInfo', 'Black', '2'],
            ['getItemSizeInfo', null, null],
        ];
    }

    /**
     * verify
     * - the localized and default values are returned
     * - if the option does not exist, null is returned
     *   for both default and localized values
     *
     * @param  string $method
     * @param  string $localizedValue
     * @param  string $defaultValue
     * @dataProvider provideForSizeColorInfo
     */
    public function testGetItemColorAndSizeInfo($method, $localizedValue, $defaultValue)
    {
        $this->replaceByMock(
            'resource_model',
            'eav/entity_attribute_option_collection',
            $this->optionValueCollectionStub
        );
        $this->optionValueCollectionStub->addItem(
            Mage::getModel('eav/entity_attribute_option', [
                'attribute_code' => 'color',
                'option_id' => 15,
                'value' => 'Black',
                'default_value' => '2',
            ])
        );
        $handler = Mage::getModel('ebayenterprise_order/create_orderitem');
        $this->assertSame(
            [$localizedValue, $defaultValue],
            EcomDev_Utils_Reflection::invokeRestrictedMethod(
                $handler,
                $method,
                [$this->itemStub]
            )
        );
    }

    /**
     * Remainder amounts should not be added to merchandise
     * pricing payloads.
     */
    public function testBuildOrderItemsRemainder()
    {
        $this->itemStub->addData([
            'qty_ordered' => 2,
            'row_total' => 14,
            'price' => 7,
            'discount_amount' => 4,
        ]);
        $handler = $this->getModelMockBuilder('ebayenterprise_order/create_orderitem')
            ->setMethods(['loadOrderItemOptions', 'isShippingPriceGroupRequired'])
            ->setConstructorArgs([['shipping_helper' => $this->shippingHelper]])
            ->getMock();
        $handler->expects($this->any())
            ->method('loadOrderItemOptions')
            ->will($this->returnValue($this->optionValueCollectionStub));
        $handler->expects($this->any())
            ->method('isShippingPriceGroupRequired')
            ->will($this->returnValue(false));
        $handler->buildOrderItem(
            $this->payload,
            $this->itemStub,
            $this->orderStub,
            $this->addressStub,
            1,
            true
        );

        $this->assertSame('thesku', $this->payload->getItemId());
        $this->assertSame(null, $this->payload->getMerchandisePricing()->getRemainder());
    }

    /**
     * verify
     * - the localized value is not set if there is no default value
     */
    public function testBuildOrderItemsMissingOptionDefault()
    {
        $handler = $this->getModelMockBuilder('ebayenterprise_order/create_orderitem')
            ->setMethods(['loadOrderItemOptions', 'prepareMerchandisePricing'])
            ->setConstructorArgs([['shipping_helper' => $this->shippingHelper]])
            ->getMock();
        $handler->expects($this->any())
            ->method('loadOrderItemOptions')
            ->will($this->returnValue($this->optionValueCollectionStub));
        $handler->expects($this->any())
            ->method('prepareMerchandisePricing')
            ->will($this->returnSelf());
        // add fake color option value
        $this->optionValueCollectionStub->addItem(
            Mage::getModel('eav/entity_attribute_option', [
            'attribute_code' => 'color',
            'option_id' => 15,
            'value' => 'Black',
            'default_value' => null,
            ])
        );
        $handler->buildOrderItem($this->payload, $this->itemStub, $this->orderStub, $this->addressStub, 2);
        $this->assertNull($this->payload->getColor());
        $this->assertNull($this->payload->getColorId());
    }

    /**
     * verify
     * - shipping price group is included when it should be
     */
    public function testBuildOrderItemsWithShippingPriceGroup()
    {
        $handler = $this->getModelMockBuilder('ebayenterprise_order/create_orderitem')
            ->setMethods(['loadOrderItemOptions', 'prepareMerchandisePricing', 'prepareShippingPriceGroup'])
            ->setConstructorArgs([['shipping_helper' => $this->shippingHelper]])
            ->getMock();
        $handler->expects($this->any())
            ->method('loadOrderItemOptions')
            ->will($this->returnValue($this->optionValueCollectionStub));
        $handler->expects($this->any())
            ->method('prepareMerchandisePricing')
            ->will($this->returnSelf());
        $handler->expects($this->once())
            ->method('prepareShippingPriceGroup')
            ->will($this->returnSelf());
        // add fake color option value
        $this->optionValueCollectionStub->addItem(
            Mage::getModel('eav/entity_attribute_option', [
            'attribute_code' => 'color',
            'option_id' => 15,
            'value' => 'Black',
            'default_value' => null,
            ])
        );
        $handler->buildOrderItem(
            $this->payload,
            $this->itemStub,
            $this->orderStub,
            $this->addressStub,
            3,
            true
        );
    }

    /**
     * verify
     * - shipping price group is excluded when it should be
     */
    public function testBuildOrderItemsWithNoShippingPriceGroup()
    {
        $handler = $this->getModelMockBuilder('ebayenterprise_order/create_orderitem')
            ->setMethods(['loadOrderItemOptions', 'prepareMerchandisePricing', 'prepareShippingPriceGroup'])
            ->setConstructorArgs([['shipping_helper' => $this->shippingHelper]])
            ->getMock();
        $handler->expects($this->any())
            ->method('loadOrderItemOptions')
            ->will($this->returnValue($this->optionValueCollectionStub));
        $handler->expects($this->any())
            ->method('prepareMerchandisePricing')
            ->will($this->returnSelf());
        $handler->expects($this->never())
            ->method('prepareShippingPriceGroup')
            ->will($this->returnSelf());
        // add fake color option value
        $this->optionValueCollectionStub->addItem(
            Mage::getModel('eav/entity_attribute_option', [
            'attribute_code' => 'color',
            'option_id' => 15,
            'value' => 'Black',
            'default_value' => null,
            ])
        );
        $handler->buildOrderItem(
            $this->payload,
            $this->itemStub,
            $this->orderStub,
            $this->addressStub,
            1,
            false
        );
    }

    /**
     * verify
     * - shipping discounts are applied to the shipping pricegroup
     */
    public function testBuildOrderItemsWithShippingDiscounts()
    {
        $lineNumber = 1;
        $this->addressStub->setEbayEnterpriseOrderDiscountData(['1,2,3,4' => ['id' => '1,2,3,4']]);
        $handler = $this->getModelMockBuilder('ebayenterprise_order/create_orderitem')
            ->setMethods(['loadOrderItemOptions', 'prepareMerchandisePricing'])
            ->setConstructorArgs([['shipping_helper' => $this->shippingHelper]])
            ->getMock();
        $handler->expects($this->any())
            ->method('loadOrderItemOptions')
            ->will($this->returnValue($this->optionValueCollectionStub));
        $handler->expects($this->any())
            ->method('prepareMerchandisePricing')
            ->will($this->returnSelf());
        $handler->buildOrderItem(
            $this->payload,
            $this->itemStub,
            $this->orderStub,
            $this->addressStub,
            $lineNumber,
            true
        );
        $pg = $this->payload->getShippingPricing();
        $this->assertNotNull($pg);
        $this->assertCount(1, $pg->getDiscounts());
    }
    /**
     * verify
     * - discounts are applied to the merchandises pricegroup
     */
    public function testBuildOrderItemsWithItemDiscounts()
    {
        $lineNumber = 1;
        $this->itemStub->setEbayEnterpriseOrderDiscountData(['1' => ['id' => '1']]);
        $handler = $this->getModelMockBuilder('ebayenterprise_order/create_orderitem')
            ->setMethods(['loadOrderItemOptions', 'prepareShippingPriceGroup'])
            ->setConstructorArgs([['shipping_helper' => $this->shippingHelper]])
            ->getMock();
        $handler->expects($this->any())
            ->method('loadOrderItemOptions')
            ->will($this->returnValue($this->optionValueCollectionStub));
        $handler->expects($this->any())
            ->method('prepareShippingPriceGroup')
            ->will($this->returnSelf());
        $handler->buildOrderItem(
            $this->payload,
            $this->itemStub,
            $this->orderStub,
            $this->addressStub,
            $lineNumber,
            true
        );
        // merchandise price group should always exist
        $pg = $this->payload->getMerchandisePricing();
        $this->assertCount(1, $pg->getDiscounts());
    }
}
