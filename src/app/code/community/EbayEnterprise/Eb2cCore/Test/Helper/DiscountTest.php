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

class EbayEnterprise_Eb2cCore_Test_Helper_DiscountTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /** @var eBayEnterprise\RetailOrderManagement\Payload\Order\IPriceGroup */
    protected $_payload;
    /** @var Mage_Sales_Model_Order_Item */
    protected $_item;

    public function setUp()
    {
        parent::setUp();
        $this->_replaceSession('core/session');
        $this->_item = Mage::getModel('sales/order_item');
        // get a pricegroup payload
        $this->_payload = Mage::helper('eb2ccore')
            ->getSdkApi('orders', 'create')
            ->getRequestBody()
            ->getOrderItems()
            ->getEmptyOrderItem()
            ->getEmptyPriceGroup();
    }

    /**
     * When I try to transfer discounts from a sales object to payloads
     * and there are no discounts then no errors will occur
     * and the returned IDiscountContainer will be empty.
     */
    public function testTransferNoDiscounts()
    {
        $discountHelper = Mage::helper('eb2ccore/discount');
        $discountHelper->transferDiscounts(
            $this->_item,
            $this->_payload
        );
        $this->assertEmpty($this->_payload->getDiscounts());
    }

    /**
     * When I try to transfer discounts from a sales object to payloads
     * and the discount node exists in an address
     * then the returned IDiscountContainer will have one discount.
     */
    public function testTransferDiscounts()
    {
        $this->_item->setEbayEnterpriseOrderDiscountData(['1' => ['id' => '1']]);

        $discountHelper = Mage::helper('eb2ccore/discount');
        $discountHelper->transferDiscounts(
            $this->_item,
            $this->_payload
        );
        $discounts = $this->_payload->getDiscounts();
        $this->assertCount(1, $discounts);
        $discounts->rewind();
        $discount = $discounts->current();
        $this->assertSame('1', $discount->getId());
    }
}
