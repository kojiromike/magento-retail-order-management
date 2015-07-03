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

class EbayEnterprise_Order_Overrides_Block_Checkout_Onepage_Success extends Mage_Checkout_Block_Onepage_Success
{
    /**
     * @see Mage_Checkout_Block_Onepage_Success::getViewOrderUrl()
     * Overriding this method in order to redirect registered user to the correct
     * ROM order detail view page in OnePage checkout success page.
     *
     * @return string
     */
    public function getViewOrderUrl()
    {
        return $this->getUrl('sales/order/romview', ['order_id' => $this->getOrderId()]);
    }
}
