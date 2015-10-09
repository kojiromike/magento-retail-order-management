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

class EbayEnterprise_Eb2cCore_Model_Observer
{
    /** @var  EbayEnterprise_Eb2cCore_Model_Session */
    protected $session;
    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $helper;

    public function __construct($init = [])
    {
        list($this->helper) = $this->checkTypes(
            $this->nullCoalesce($init, 'helper', Mage::helper('eb2ccore'))
        );
    }

    /**
     * enforce dependency types
     *
     * @param EbayEnterprise_Eb2cCore_Helper_Data
     * @return array
     */
    protected function checkTypes(EbayEnterprise_Eb2cCore_Helper_Data $helper)
    {
        return func_get_args();
    }

    /**
     * get the value of $arr[$key] if it is set; $default otherwise.
     *
     * @param array
     * @param mixed
     * @param mixed
     * @return mixed
     */
    protected function nullCoalesce(array $arr, $key, $default)
    {
        return isset($arr[$key]) ? $arr[$key] : $default;
    }

    /**
     * Update the eb2ccore session with the new quote.
     * @param  Varien_Event_Observer $observer Event observer object containing a quote object
     * @return self $this object
     */
    public function checkQuoteForChanges($observer)
    {
        $this->getCoreSession()->updateWithQuote($observer->getEvent()->getQuote());
        return $this;
    }

    /**
     * @return EbayEnterprise_Eb2cCore_Model_Session|Mage_Core_Model_Abstract
     */
    protected function getCoreSession()
    {
        if (!$this->session) {
            $this->session = Mage::getSingleton('eb2ccore/session');
        }
        return $this->session;
    }

    /**
     * Perform all processing necessary for the order to be placed with the
     * Exchange Platform - allocate inventory, redeem SVC. If any of the observers
     * need to indicate that an order should not be created, the observer method
     * should throw an exception.
     * Observers the 'sales_order_place_before' event.
     *
     * @see Mage_Sales_Model_Order::place
     * @param Varien_Event_Observer $observer contains the order being placed which
     *                                        will have a reference to the quote the
     *                                        order was created for
     * @throws EbayEnterprise_Eb2cInventory_Model_Allocation_Exception
     * @throws Exception
     * @return self
     */
    public function processExchangePlatformOrder(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $quote = $order->getQuote();
        Mage::dispatchEvent('ebayenterprise_giftcard_redeem', array('quote' => $quote, 'order' => $order));
        return $this;
    }
    /**
     * Roll back any Exchange Platform actions made for the order - rollback
     * allocation, void SVC redemptions, void payment auths.
     * Observes the 'sales_model_service_quote_submit_failure' event.
     * @see Mage_Sales_Model_Service_Quote::submitOrder
     * @param  Varien_Event_Observer $observer Contains the failed order as well as the quote the order was created for
     * @return self
     */
    public function rollbackExchangePlatformOrder(Varien_Event_Observer $observer)
    {
        Mage::helper('ebayenterprise_magelog')->debug(__METHOD__);
        Mage::dispatchEvent('eb2c_order_creation_failure', array(
            'quote' => $observer->getEvent()->getQuote(),
            'order' => $observer->getEvent()->getOrder()
        ));
        return $this;
    }
    /**
     * Listen to the 'checkout_onepage_controller_success_action' event
     * Clear the session
     *
     * @param Varien_Event_Observer $observer
     * @return self
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function clearSession(Varien_Event_Observer $observer)
    {
        $this->getCoreSession()->clear();
        return $this;
    }

    /**
     * respond to the order create's request for a valid ship group
     * charge type
     * @param  Varien_Event_Observer $observer
     * @return self
     */
    public function handleShipGroupChargeType(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        $shipGroup = $event->getShipGroupPayload();
        Mage::helper('eb2ccore/shipping_chargetype')->setShippingChargeType($shipGroup);
        return $this;
    }

    /**
     * Account for shipping discounts not attached to an item.
     * Combine all shipping discounts into one.
     *
     * @see self::handleSalesConvertQuoteAddressToOrderAddress
     * @see Mage_SalesRule_Model_Validator::processShippingAmount
     * @param Varien_Event_Observer
     * @return void
     */
    public function handleSalesQuoteCollectTotalsAfter(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = $event->getQuote();
        /** @var Mage_Sales_Model_Resource_Quote_Address_Collection */
        $addresses = $quote->getAddressesCollection();
        foreach ($addresses as $address) {
            $appliedRuleIds = $address->getAppliedRuleIds();
            if (is_array($appliedRuleIds)) {
                $appliedRuleIds = implode(',', $appliedRuleIds);
            }
            $data = (array) $address->getEbayEnterpriseOrderDiscountData();
            $data[$appliedRuleIds] = [
                'id' => $appliedRuleIds,
                'amount' => $address->getBaseShippingDiscountAmount(),
                'description' => $this->helper->__('Shipping Discount'),
            ];
            $address->setEbayEnterpriseOrderDiscountData($data);
        }
    }

    /**
     * Account for discounts in order create request.
     *
     * @see self::handleSalesConvertQuoteItemToOrderItem
     * @see Mage_SalesRule_Model_Validator::process
     * @see Order-Datatypes-Common-1.0.xsd:PromoDiscountSet
     * @param Varien_Event_Observer
     * @return void
     */
    public function handleSalesRuleValidatorProcess(Varien_Event_Observer $observer)
    {
        /** @var Varien_Event $event */
        $event = $observer->getEvent();
        /** @var Mage_SalesRule_Model_Rule $rule */
        $rule = $event->getRule();
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = $event->getQuote();
        /** @var Mage_Core_Model_Store $store */
        $store = $quote->getStore();
        /** @var Mage_Sales_Model_Quote_Item $item */
        $item = $event->getItem();
        /** @var Varien_Object */
        $result = $event->getResult();
        $data = (array) $item->getEbayEnterpriseOrderDiscountData();
        $ruleId = $rule->getId();
        // Use the rule id to prevent duplicates.
        $data[$ruleId] = [
            'amount' => $this->calculateDiscountAmount($item, $result, $data),
            'applied_count' => $event->getQty(),
            'code' => $this->helper->getQuoteCouponCode($quote, $rule),
            'description' => $rule->getStoreLabel($store) ?: $rule->getName(),
            'effect_type' => $rule->getSimpleAction(),
            'id' => $ruleId,
        ];
        $item->setEbayEnterpriseOrderDiscountData($data);
    }

    /**
     * When the previously applied discount amount on the item row total
     * is less than the current applied discount recalculate the current discount
     * to account for previously applied discount. Otherwise, don't recalculate
     * the current discount.
     *
     * @param  Mage_Sales_Model_Quote_Item
     * @param  Varien_Object
     * @param  array
     * @return float
     */
    protected function calculateDiscountAmount(Mage_Sales_Model_Quote_Item $item, Varien_Object $result, array $data)
    {
        /** @var float */
        $itemRowTotal = $item->getBaseRowTotal();
        /** @var float */
        $currentDiscountAmount = $result->getBaseDiscountAmount();
        /** @var float */
        $previousAppliedDiscountAmount = 0.00;
        foreach ($data as $discount) {
            $previousAppliedDiscountAmount += $discount['amount'];
        }
        /** @var float */
        $itemRowTotalWithAppliedPreviousDiscount = $itemRowTotal - $previousAppliedDiscountAmount;
        if ($itemRowTotalWithAppliedPreviousDiscount < 0) {
            $itemRowTotalWithAppliedPreviousDiscount = 0;
        }
        return $itemRowTotalWithAppliedPreviousDiscount < $currentDiscountAmount
            ? $itemRowTotalWithAppliedPreviousDiscount
            : $currentDiscountAmount;
    }
}
