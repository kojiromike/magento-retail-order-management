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
class EbayEnterprise_Catalog_Helper_Xslt
{
	/**
	 * This is a callback to add additional template handling for configurable
	 * variables that XSLT 1.0 just doesn't do.
	 * @param DOMDocument $xslDoc        XSLT document
	 * @param array       $websiteFilter website filter data
	 */
	public function xslCallBack(DOMDocument $xslDoc, array $websiteFilter)
	{
		$helper = Mage::helper('ebayenterprise_catalog');
		foreach( array('Item', 'PricePerItem', 'Content') as $nodeToMatch) {
			$helper->appendXslTemplateMatchNode($xslDoc, "/*/{$nodeToMatch}[(@catalog_id and @catalog_id!='{$websiteFilter['catalog_id']}')]");
			$helper->appendXslTemplateMatchNode($xslDoc, "/*/{$nodeToMatch}[(@gsi_client_id and @gsi_client_id!='{$websiteFilter['client_id']}')]");
			$helper->appendXslTemplateMatchNode($xslDoc, "/*/{$nodeToMatch}[(@gsi_store_id and @gsi_store_id!='{$websiteFilter['store_id']}')]");
		}
		$xslDoc->loadXML($xslDoc->saveXML());
	}
}
