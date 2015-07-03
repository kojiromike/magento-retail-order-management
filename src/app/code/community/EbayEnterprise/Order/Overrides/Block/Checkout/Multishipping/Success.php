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

class EbayEnterprise_Order_Overrides_Block_Checkout_Multishipping_Success extends Mage_Checkout_Block_Multishipping_Success
{
    /** @var EbayEnterprise_Order_Helper_Factory */
    protected $orderFactory;

    public function __construct(array $initParams = [])
    {
        list($this->orderFactory) = $this->checkTypes(
            $this->nullCoalesce($initParams, 'order_factory', Mage::helper('ebayenterprise_order/factory'))
        );
        parent::__construct($this->removeKnownKeys($initParams));
    }

    /**
     * Populate a new array with keys that's not in the array of known keys.
     *
     * @param  array
     * @return array
     * @codeCoverageIgnore
     */
    protected function removeKnownKeys(array $initParams)
    {
        $newParams = [];
        $knownKeys = ['order_factory'];
        foreach ($initParams as $key => $value) {
            if (!in_array($key, $knownKeys)) {
                $newParams[$key] = $value;
            }
        }
        return $newParams;
    }

    /**
     * Type hinting for self::__construct $initParams
     *
     * @param  EbayEnterprise_Order_Helper_Factory
     * @return array
     */
    protected function checkTypes(EbayEnterprise_Order_Helper_Factory $orderFactory)
    {
        return func_get_args();
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the default value.
     *
     * @param  array
     * @param  string $field Valid array key
     * @param  mixed
     * @return mixed
     */
    protected function nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }

    /**
     * @see Mage_Checkout_Block_Multishipping_Success::getOrderIds()
     * Overriding this method because it is clearing out the multi-shipping
     * order ids after retrieving them making it impossible to get the increment
     * id. So, instead this override will suppress this behavior.
     *
     * Retrieve multi-shipping order ids from the core/session.
     * If ids are found as an array return the array of order ids.
     * If ids are empty string or null then simply return the boolean value false.
     *
     * @return array | false
     */
    public function getOrderIds()
    {
        $ids = $this->orderFactory->getCoreSessionModel()->getOrderIds();
        return ($ids && is_array($ids)) ? $ids : false;
    }

    /**
     * @see Mage_Checkout_Block_Multishipping_Success::getViewOrderUrl()
     * Overriding this method in order to redirect registered user to the correct
     * ROM order detail view page in Multi-shipping checkout success page.
     *
     * @param int
     * @return string
     */
    public function getViewOrderUrl($orderId)
    {
        $orderIds = $this->getOrderIds();
        $incrementId = isset($orderIds[$orderId]) ? $orderIds[$orderId] : null;
        // clearing out the multi-shipping ids from core session, see the
        // Mage_Checkout_Block_Multishipping_Success::getOrderIds() method.
        $this->orderFactory->getCoreSessionModel()->getOrderIds(true);
        return $this->getUrl('sales/order/romview', ['order_id' => $incrementId]);
    }
}
