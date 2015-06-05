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

class EbayEnterprise_Order_Model_Detail_Process_Response_Item extends Mage_Sales_Model_Order_Item
{
    protected function _construct()
    {
        parent::_construct();
        // an item must have a valid product id in order for the
        // reorder link to work see Mage_Sales_Model_Order::_canReorder method
        if (!$this->getProductId()) {
            $this->setProductId(Mage::getModel('catalog/product')->getIdBySku($this->getSku()));
        }
    }
}
