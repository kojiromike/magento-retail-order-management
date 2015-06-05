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

class EbayEnterprise_Catalog_Helper_Map_Price
{
    /**
     * Extract the product "Price".
     * @param  DOMNodeList $nodeList Price feed `Event` node
     * @return float
     */
    public function extractPrice(DOMNodeList $nodeList)
    {
        return Mage::getModel(
            'ebayenterprise_catalog/price_event',
            array('event_node' => $nodeList->item(0))
        )->getPrice();
    }
    /**
     * Extract the product "Special Price".
     * @param  DOMNodeList $nodeList Price feed `Event` node
     * @return float|null
     */
    public function extractSpecialPrice(DOMNodeList $nodeList)
    {
        return Mage::getModel(
            'ebayenterprise_catalog/price_event',
            array('event_node' => $nodeList->item(0))
        )->getSpecialPrice();
    }
    /**
     * Get the product "Special From Date".
     * @param  DOMNodeList $nodeList Price feed `Event` node
     * @return string
     */
    public function extractPriceEventFromDate(DOMNodeList $nodeList)
    {
        return Mage::getModel(
            'ebayenterprise_catalog/price_event',
            array('event_node' => $nodeList->item(0))
        )->getSpecialFromDate();
    }
    /**
     * Get the product "Sepcial To Date".
     * @param  DOMNodeList $nodeList Price feed `Event` node
     * @return string
     */
    public function extractPriceEventToDate(DOMNodeList $nodeList)
    {
        return Mage::getModel(
            'ebayenterprise_catalog/price_event',
            array('event_node' => $nodeList->item(0))
        )->getSpecialToDate();
    }
}
