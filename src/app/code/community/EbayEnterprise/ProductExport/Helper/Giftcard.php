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

class EbayEnterprise_ProductExport_Helper_Giftcard
{
    /**
     * Check if the passed in 'catalog/product' class instance object is a gift card product type before proceeding
     * to get its 'open_amount_max' attribute value and the largest card amount of the product. Further business
     * logics attempt to use 'open_amount_max' attribute value when there is one, otherwise use the largest card
     * amount to build the 'CustomAttributes/Attribute[@name="MaxGCAmount"]' Element. When both 'open_amount_max'
     * attribute value and largest card amounts are less or equal to zero an exception is thrown.
     * @param  string                     $attrValue
     * @param  string                     $attribute
     * @param  Mage_Catalog_Model_Product $product
     * @param  DOMDocument                $doc
     * @return mixed
     * @throws EbayEnterprise_Catalog_Model_Pim_Product_Validation_Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function passMaxGCAmount($attrValue, $attribute, Mage_Catalog_Model_Product $product, DOMDocument $doc)
    {
        if ($product->getTypeId() !== Enterprise_GiftCard_Model_Catalog_Product_Type_Giftcard::TYPE_GIFTCARD) {
            return null;
        }
        $value = max($product->getOpenAmountMax(), $this->_getMaxGiftCardAmount($product));
        if ($value <= 0) {
            $msg = "%s SKU '%s' a Gift card product is missing both 'Open Amount Max Value' and 'Card Amounts' data.";
            throw Mage::exception('EbayEnterprise_Catalog_Model_Pim_Product_Validation', sprintf($msg, __FUNCTION__, $product->getSku()));
        }
        return Mage::helper('ebayenterprise_catalog/pim')->getValueAsDefault($value, $attribute, $product, $doc);
    }
    /**
     * Get the largest discrete gift card amount from a passed in 'catalog/product' class instance object.
     * @param  Mage_Catalog_Model_Product $product
     * @return float
     */
    protected function _getMaxGiftCardAmount(Mage_Catalog_Model_Product $product)
    {
        return array_reduce($product->getGiftcardAmounts(), function ($p, $i) {
                return max($p, $i['value']);
        }, 0);
    }
}
