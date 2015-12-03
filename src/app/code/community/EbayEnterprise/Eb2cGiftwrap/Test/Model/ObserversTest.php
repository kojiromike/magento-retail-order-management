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

class EbayEnterprise_Eb2cGiftwrap_Test_Model_ObserversTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /**
     * Test that the method EbayEnterprise_Eb2cGiftwrap_Model_Observers::processDom will invoked
     * and will will run EbayEnterprise_Catalog_Model_Feed_File::process with the required
     * parameters
     */
    public function testProcessDom()
    {
        $doc = Mage::helper('eb2ccore')->getNewDomDocument();
        $doc->loadXML(
            '<ItemMaster>
                <Item operation_type="Add" gsi_client_id="MAGTNA" catalog_id="14">
                    <ItemId>
                        <ClientItemId>14-77554</ClientItemId>
                    </ItemId>
                    <BaseAttributes>
                        <ItemDescription>Gift Wrap 1</ItemDescription>
                        <ItemType>GiftWrap</ItemType>
                        <TaxCode>78</TaxCode>
                    </BaseAttributes>
                </Item>
            </ItemMaster>'
        );

        $feedData = array('event_type' => 'ItemMaster');
        $coreFeed = $this->getModelMockBuilder('ebayenterprise_catalog/feed_core')
            ->disableOriginalConstructor()
            ->setMethods(array('getFeedConfig'))
            ->getMock();
        $coreFeed->expects($this->any())
            ->method('getFeedConfig')
            ->will($this->returnValue($feedData));

        $observer = new Varien_Event_Observer(array('event' => new Varien_Event(array('doc' => $doc, 'file_detail' => array(
            'local_file' => 'EbayEnterprise/Product/ItemMaster/Inbound/ItemMaster_TestSubset.xml',
            'core_feed' => 'core feed mock',
            'timestamp' => '2012-07-06 10:09:05',
            'error_file' => '/EbayEnterprise/Eb2c/Feed/Product/ItemMaster/outbound/ItemMaster_20140107224605_12345_ABCD.xml',
            'core_feed' => $coreFeed
        )))));
        $cfgData = array(
            'allowable_event_type' => 'ItemMaster',
        );
        $config = $this->getModelMock('eb2cgiftwrap/feed_import_config', array('getImportConfigData'));
        $config->expects($this->any())
            ->method('getImportConfigData')
            ->will($this->returnValue($cfgData));
        $this->replaceByMock('model', 'eb2cgiftwrap/feed_import_config', $config);

        $items = $this->getModelMock('eb2cgiftwrap/feed_import_items', array());
        $this->replaceByMock('model', 'eb2cgiftwrap/feed_import_items', $items);

        $fileModelMock = $this->getModelMockBuilder('ebayenterprise_catalog/feed_file')
            ->disableOriginalConstructor()
            ->setMethods(array('process'))
            ->getMock();
        $fileModelMock->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($config), $this->identicalTo($items))
            ->will($this->returnSelf());
        $this->replaceByMock('model', 'ebayenterprise_catalog/feed_file', $fileModelMock);

        $observers = Mage::getModel('eb2cgiftwrap/observers');

        $this->assertSame($observers, $observers->processDom($observer));
    }

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
     * Provide address data, order data, if the address is the primary shipping
     * address, and the gift message id the address should have after the test.
     *
     * @return array
     */
    public function provideShipGroupGiftingData()
    {
        $giftMessageId = 11;
        return [
            // address data, order data, primary shipping, message id
            [['gift_message_id' => $giftMessageId], [], false, $giftMessageId], // non-primary shipping address, address has message, use address message
            [['gift_message_id' => $giftMessageId], [], true, $giftMessageId], // primary shipping address, order has no message, use address message
            [['gift_message_id' => $giftMessageId], ['gift_message_id' => $giftMessageId + 1], false, $giftMessageId], // non-primary shipping address, order has message, use address message
            [[], ['gift_message_id' => $giftMessageId], true, $giftMessageId], // primary shipping address, order has message, use order message
        ];
    }

    /**
     * @param array
     * @param array
     * @param bool
     * @param int
     * @dataProvider provideShipGroupGiftingData
     */
    public function testHandleEbayEnterpriseOrderCreateShipGroup($addressData, $orderData, $isPrimaryShippingAddress, $giftMessageId)
    {
        $address = $this->getModelMockBuilder('sales/order_address')
            ->setMethods(['isPrimaryShippingAddress'])
            ->setConstructorArgs([$addressData])
            ->getMock();
        $address->method('isPrimaryShippingAddress')->willReturn($isPrimaryShippingAddress);

        $order = Mage::getModel('sales/order', $orderData);

        $payload = $this->getMock('eBayEnterprise\RetailOrderManagement\Payload\Order\IShipGroup');

        $event = new Varien_Event(['address' => $address, 'order' => $order, 'ship_group_payload' => $payload]);
        $eventObserver = new Varien_Event_Observer(['event' => $event]);

        $orderGifting = $this->getModelMock('eb2cgiftwrap/order_create_gifting', ['injectGifting']);
        $orderGifting->expects($this->once())
            ->method('injectGifting')
            ->with($this->identicalTo($address))
            ->willReturnSelf();

        $observer = Mage::getModel('eb2cgiftwrap/observers', ['order_gifting' => $orderGifting]);
        $observer->handleEbayEnterpriseOrderCreateShipGroup($eventObserver);

        $this->assertSame($giftMessageId, $address->getGiftMessageId());
    }
}
