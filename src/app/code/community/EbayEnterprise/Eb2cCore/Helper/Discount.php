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

use eBayEnterprise\RetailOrderManagement\Payload\Order\IDiscount;
use eBayEnterprise\RetailOrderManagement\Payload\Order\IDiscountContainer;
use eBayEnterprise\RetailOrderManagement\Payload\Order\IDiscountIterable;
use eBayEnterprise\RetailOrderManagement\Payload\TaxDutyFee\IDiscount as ITaxDiscount;
use eBayEnterprise\RetailOrderManagement\Payload\TaxDutyFee\IDiscountContainer as ITaxDiscountContainer;
use eBayEnterprise\RetailOrderManagement\Payload\TaxDutyFee\IDiscountIterable as ITaxDiscountIterable;

/**
 * Helps transfer discount data from Mage_Sales_Model_Order to
 * API order and tax documents.
 */
class EbayEnterprise_Eb2cCore_Helper_Discount
{
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $_logger;

    public function __construct()
    {
        $this->_logger = Mage::helper('ebayenterprise_magelog');
    }

    /**
     * Get a value from an array if it exists or get a default.
     *
     * @codeCoverageIgnore
     * @param array
     * @param string
     * @param mixed
     * @return mixed
     */
    protected function _nullCoalesce(array $array, $key, $default)
    {
        return isset($array[$key]) ? $array[$key] : $default;
    }

    /**
     * Get discount data collected for a sales object -
     * Mage_Sales_Model_Quote_Address or Mage_Sales_Model_Quote_Address.
     *
     * @return array
     */
    public function getDiscountsData(Varien_Object $salesObject)
    {
        return (array) $salesObject->getEbayEnterpriseOrderDiscountData();
    }

    /**
     * Transfer discount data from Mage_Sales_Model_Order_Addresses
     * to IDiscountContainer.
     *
     * @see EbayEnterprise_Order_Model_Observer::handleSalesQuoteCollectTotalsAfter
     * @param  Varien_Object      item or address object
     * @param  IDiscountContainer
     * @return IDiscountContainer
     */
    public function transferDiscounts(Varien_Object $salesObject, IDiscountContainer $discountContainer)
    {
        /** @var IDiscountIterable $discounts */
        $discounts = $discountContainer->getDiscounts();
        $data = $this->getDiscountsData($salesObject);
        foreach ($data as $loneDiscountData) {
            $discount = $this->_fillOutDiscount($discounts->getEmptyDiscount(), $loneDiscountData);
            $discounts[$discount] = $discount;
        }
        return $discountContainer->setDiscounts($discounts);
    }

    /**
     * Fill out the data in an IDiscount
     *
     * @param IDiscount
     * @param array $discountData
     * @return IDiscount
     */
    protected function _fillOutDiscount(IDiscount $discountPayload, array $discountData)
    {
        return $discountPayload
            ->setAmount($this->_nullCoalesce($discountData, 'amount', 0.00))
            ->setAppliedCount($this->_nullCoalesce($discountData, 'applied_count', null))
            ->setCode($this->_nullCoalesce($discountData, 'code', null))
            ->setDescription($this->_nullCoalesce($discountData, 'description', null))
            ->setEffectType($this->_nullCoalesce($discountData, 'effect_type', null))
            ->setId($this->_nullCoalesce($discountData, 'id', null));
    }

    /**
     * Transfer discount data from Mage_Sales_Model_Qoute_Addresses
     * or Mage_Sales_Model_Quote_Items to TaxDutyFee\IDiscountContainer.
     *
     * @param Varien_Object
     * @param ITaxDiscountContainer
     * @return ITaxDiscountContainer
     */
    public function transferTaxDiscounts(Varien_Object $salesObject, ITaxDiscountContainer $discountContainer)
    {
        /** @var ITaxDiscountIterable $discounts */
        $discounts = $discountContainer->getDiscounts();
        $data = $this->getDiscountsData($salesObject);
        foreach ($data as $loneDiscountData) {
            $discount = $this->_fillOutTaxDiscount($discounts->getEmptyDiscount(), $loneDiscountData);
            ;
            $discounts[$discount] = $discount;
        }
        return $discountContainer->setDiscounts($discounts);
    }

    /**
     * Fill out the data in an ITaxDiscount
     *
     * @param ITaxDiscount
     * @param array $discountData
     * @return ITaxDiscount
     */
    protected function _fillOutTaxDiscount(ITaxDiscount $discountPayload, array $discountData)
    {
        return $discountPayload
            ->setAmount($this->_nullCoalesce($discountData, 'amount', null))
            ->setId($this->_nullCoalesce($discountData, 'id', null));
    }
}
