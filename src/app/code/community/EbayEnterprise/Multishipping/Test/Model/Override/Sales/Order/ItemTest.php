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

class EbayEnterprise_Multishipping_Test_Model_Override_Sales_Order_ItemTest extends EcomDev_PHPUnit_Test_Case
{
    /** @var EbayEnterprise_Multishipping_Helper_Factory */
    protected $_multishippingFactory;
    /** @var EbayEnterprise_Multishipping_Override_Model_Sales_Order_Item */
    protected $_item;

    protected function setUp()
    {
        $this->_multishippingFactory = $this->getHelperMock('ebayenterprise_multishipping/factory', ['loadAddressForItem']);
        $this->_item = Mage::getModel('sales/order_item', ['multishipping_factory' => $this->_multishippingFactory]);
    }

    /**
     * Before an item is saved, if the item has an associated order address
     * with a valid id, the id of the order address should be set on the item.
     */
    public function testBeforeSave()
    {
        $addressId = 8;
        $address = Mage::getModel('sales/order_address', ['entity_id' => $addressId]);
        $this->_item->setOrderAddress($address);

        EcomDev_Utils_Reflection::invokeRestrictedMethod($this->_item, '_beforeSave');
        $this->assertSame($addressId, $this->_item->getOrderAddressId());
    }

    /**
     * When getting an order address for an item, if the item does not yet have
     * a loaded order address instance, load a new one and return it.
     */
    public function testGetOrderAddress()
    {
        $addressId = 3;
        $this->_item->setOrderAddressId($addressId);
        $address = Mage::getModel('sales/order_address');

        $this->_multishippingFactory->method('loadAddressForItem')
            ->with($this->identicalTo($this->_item))
            ->will($this->returnValue($address));
        $this->assertSame($address, $this->_item->getOrderAddress());
    }

    /**
     * When getting an order address for an item, if the item does not yet have
     * a loaded order address instance, load a new one and return it.
     */
    public function testGetOrderAddressMemoized()
    {
        $addressId = 3;
        $this->_item->setOrderAddressId($addressId);
        $address = Mage::getModel('sales/order_address');

        // Side-effect test: ensure that the factory, which will load new
        // address instances for the item, is only invoked one time, no matter
        // how many times getOrderAddress is invoked.
        $this->_multishippingFactory->expects($this->once())
            ->method('loadAddressForItem')
            ->with($this->identicalTo($this->_item))
            ->will($this->returnValue($address));

        // Invoke getOrderAddress multiple times, the factory should still only
        // be invoked one time.
        $this->_item->getOrderAddress();
        $this->_item->getOrderAddress();
    }
}
