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

class EbayEnterprise_Catalog_Helper_Pim_Price
{
    /**
     * checking if product has special price if so build event number using the concatenation of special_from_date
     * and special_to_date and return inner value element containing price event number
     * @param string $attrValue
     * @param string $attribute
     * @param Mage_Catalog_Model_Product $product
     * @param EbayEnterprise_Dom_Document $doc
     * @return DOMNode|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function passPriceEventNumber(
        $attrValue,
        $attribute,
        Mage_Catalog_Model_Product $product,
        EbayEnterprise_Dom_Document $doc
    ) {
        return Mage::helper('ebayenterprise_catalog/pim')->createStringNode($this->_buildEventNumber($product), $doc);
    }

    /**
     * checking if product has special price if so create DOMNode with special price
     * otherwise create DOMNode with regular price
     * @param string $attrValue
     * @param string $attribute
     * @param Mage_Catalog_Model_Product $product
     * @param EbayEnterprise_Dom_Document $doc
     * @return DOMNode|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function passPrice(
        $attrValue,
        $attribute,
        Mage_Catalog_Model_Product $product,
        EbayEnterprise_Dom_Document $doc
    ) {
        $price = $this->_hasSpecialPrice($product)? $product->getSpecialPrice() : $product->getPrice();
        return Mage::helper('ebayenterprise_catalog/pim')->createTextNode(Mage::getModel('core/store')->roundPrice($price), $doc);
    }

    /**
     * setting the DOMNode to the product MSRP value
     * @param string $attrValue
     * @param string $attribute
     * @param Mage_Catalog_Model_Product $product
     * @param EbayEnterprise_Dom_Document $doc
     * @return DOMNode|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function passMsrp(
        $attrValue,
        $attribute,
        Mage_Catalog_Model_Product $product,
        EbayEnterprise_Dom_Document $doc
    ) {
        return Mage::helper('ebayenterprise_catalog/pim')->createTextNode(
            Mage::getModel('core/store')->roundPrice((float) $product->getMsrp()),
            $doc
        );
    }

    /**
     * checking if product has special price if so create DOMNode with special price
     * otherwise return null
     * @param string $attrValue
     * @param string $attribute
     * @param Mage_Catalog_Model_Product $product
     * @param EbayEnterprise_Dom_Document $doc
     * @return DOMNode|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function passAlternatePrice(
        $attrValue,
        $attribute,
        Mage_Catalog_Model_Product $product,
        EbayEnterprise_Dom_Document $doc
    ) {
        $altPrice = $this->_hasSpecialPrice($product)?
            Mage::getModel('core/store')->roundPrice($product->getSpecialPrice()) : null;

        return Mage::helper('ebayenterprise_catalog/pim')->createTextNode($altPrice, $doc);
    }

    /**
     * checking if product has special price if so create DOMNode with special from date
     * otherwise return null
     * @param string $attrValue
     * @param string $attribute
     * @param Mage_Catalog_Model_Product $product
     * @param EbayEnterprise_Dom_Document $doc
     * @return DOMNode|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function passPriceDateFrom(
        $attrValue,
        $attribute,
        Mage_Catalog_Model_Product $product,
        EbayEnterprise_Dom_Document $doc
    ) {
        $pim = Mage::helper('ebayenterprise_catalog/pim');
        return $pim->createTextNode(
            $this->_hasSpecialPrice($product)? $pim->createDateTime($product->getSpecialFromDate()) : null,
            $doc
        );
    }

    /**
     * checking if product has special price if so create DOMNOde with special to date
     * otherwise return null
     * @param string $attrValue
     * @param string $attribute
     * @param Mage_Catalog_Model_Product $product
     * @param EbayEnterprise_Dom_Document $doc
     * @return DOMNode|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function passPriceDateTo(
        $attrValue,
        $attribute,
        Mage_Catalog_Model_Product $product,
        EbayEnterprise_Dom_Document $doc
    ) {
        $pim = Mage::helper('ebayenterprise_catalog/pim');
        return $pim->createTextNode(
            $this->_hasSpecialPrice($product)? $pim->createDateTime($product->getSpecialToDate()) : null,
            $doc
        );
    }

    /**
     * get the vat include pricing flag from configuration
     * @param string $attrValue
     * @param string $attribute
     * @param Mage_Catalog_Model_Product $product
     * @param EbayEnterprise_Dom_Document $doc
     * @return DOMNode|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function passPriceVatInclusive(
        $attrValue,
        $attribute,
        Mage_Catalog_Model_Product $product,
        EbayEnterprise_Dom_Document $doc
    ) {
        return Mage::helper('ebayenterprise_catalog/pim')->createTextNode(
            Mage::helper('eb2ctax')->getVatInclusivePricingFlag()? 'true' : 'false',
            $doc
        );
    }

    /**
     * get timestamps in YYYYMMDD format
     * @param string $time
     * @return string
     */
    protected function _getTimeStamp($time)
    {
        $time = (trim($time) === '')? time() : strtotime($time);
        return Mage::getModel('core/date')->gmtDate('Ymd', $time);
    }

    /**
     * check if product has special price
     * @param Mage_Catalog_Model_Product $product
     * @return bool
     */
    protected function _hasSpecialPrice(Mage_Catalog_Model_Product $product)
    {
        return ((float) $product->getSpecialPrice() > 0);
    }

    /**
     * build price event number base on the given product
     * @param Mage_Catalog_Model_Product $product
     * @return string
     */
    protected function _buildEventNumber(Mage_Catalog_Model_Product $product)
    {
        return $this->_hasSpecialPrice($product)?
            $this->_getTimeStamp($product->getSpecialFromDate()) . '-' .
            $this->_getTimeStamp($product->getSpecialToDate()): null;
    }
}
