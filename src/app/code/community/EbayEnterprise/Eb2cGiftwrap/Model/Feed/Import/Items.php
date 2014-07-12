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

class EbayEnterprise_Eb2cGiftwrap_Model_Feed_Import_Items
	implements EbayEnterprise_Eb2cCore_Model_Feed_Import_Items_Interface
{
	/**
	 * @see EbayEnterprise_Eb2cCore_Model_Feed_Import_Items_Interface::buildCollection
	 * @param  array $skus
	 * @return Enterprise_GiftWrapping_Model_Resource_Wrapping_Collection
	 */
	public function buildCollection(array $skus=array())
	{
		return Mage::getResourceModel('eb2cgiftwrap/wrapping_collection')
			->addFieldToSelect(array('*'))
			->addFieldToFilter('eb2c_sku', array('in' => $skus))
			->load();
	}
	/**
	 * @see EbayEnterprise_Eb2cCore_Model_Feed_Import_Items_Interface::createNewItem
	 * @param string $sku
	 * @param array $additionalData optional
	 * @return Enterprise_GiftWrapping_Model_Wrapping
	 */
	public function createNewItem($sku, array $additionalData=array())
	{
		/** @var EbayEnterprise_Eb2cGiftwrap_Helper_Data $helper */
		$helper = Mage::helper('eb2cgiftwrap');
		return $helper->createNewGiftwrapping($sku, $additionalData);
	}
}
