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

use \eBayEnterprise\RetailOrderManagement\Payload\Order\IEstimatedDeliveryDate;

class EbayEnterprise_Eb2cInventory_Test_Model_Order_Create_InventoryTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /** @var EbayEnterprise_Eb2cInventory_Helper_Data */
    protected $_helperStub;
    /** @var Mage_Sales_Model_Order */
    protected $_order;
    /** @var Mage_Sales_Model_Order_Item */
    protected $_item;
    /** @var string */
    protected $_deliveryFrom = '2015-03-10 08:33:30';
    /** @var IOrderItem */
    protected $_payload;

    public function setUp()
    {
        parent::setUp();
        // replace the session for the logger context
        $this->_replaceSession('core/session');
        $api = Mage::helper('eb2ccore')->getSdkApi('orders', 'create');
        $this->_payload = $api->getRequestBody()->getOrderItems()->getEmptyOrderItem();
        $config = $this->buildCoreConfigRegistry([
            'estimatedDeliveryTemplate' => 'eddtemplate'
        ]);
        // prevent the constructor from trying to access unavailable
        // config properties
        $this->_helperStub = $this->getHelperMockBuilder('eb2cinventory/data')
            ->disableOriginalConstructor()
            ->setMethods(['getConfigModel'])
            ->getMock();
        $this->_helperStub->expects($this->any())
            ->method('getConfigModel')
            ->will($this->returnValue($config));
        $this->_item = Mage::getModel('sales/order_item', [
            'eb2c_display' => 'some display text',
            'eb2c_delivery_window_from' => $this->_deliveryFrom,
            'eb2c_delivery_window_to' => '',
            'eb2c_shipping_window_from' => null,
            'eb2c_shipping_window_to' => 'fooo',
            'eb2c_reservation_id' => 'reservationid',
        ]);
        $this->_order = Mage::getModel('sales/order');
    }

    /**
     * verify
     * - estimated delivery date information is set on the payload
     * - invalid datestrings won't be used to set data on the payload
     * - the mode and message are each hardwired to a specific value
     * - the template is read from the config
     */
    public function testInjectShippingEstimates()
    {
        $handler = Mage::getModel('eb2cinventory/order_create_inventory', ['helper' => $this->_helperStub]);
        $handler->injectShippingEstimates($this->_payload, $this->_item);
        $this->assertSame($this->_deliveryFrom, $this->_payload->getEstimatedDeliveryWindowFrom()->format('Y-m-d H:i:s'));
        $this->assertNull($this->_payload->getEstimatedDeliveryWindowTo());
        $this->assertNull($this->_payload->getEstimatedShippingWindowFrom());
        $this->assertNull($this->_payload->getEstimatedShippingWindowTo());
        $this->assertSame(IEstimatedDeliveryDate::MODE_LEGACY, $this->_payload->getEstimatedDeliveryMode());
        $this->assertSame(IEstimatedDeliveryDate::MESSAGE_TYPE_DELIVERYDATE, $this->_payload->getEstimatedDeliveryMessageType());
        $this->assertSame('eddtemplate', $this->_payload->getEstimatedDeliveryTemplate());
        $this->assertSame('reservationid', $this->_payload->getReservationId());
    }
}
