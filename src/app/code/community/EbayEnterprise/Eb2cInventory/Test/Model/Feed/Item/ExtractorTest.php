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

class EbayEnterprise_Eb2cInventory_Test_Model_Feed_Item_ExtractorTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * testing extractInventoryFeed method
	 */
	public function testExtractInventoryFeed()
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML('<ItemInventories><Inventory operation_type="Change" gsi_client_id="66-000906034352" catalog_id="66" measurement="Level">
		<ItemId>
			<ClientItemId>000906034352</ClientItemId>
		</ItemId>
		<Measurements>
			<AvailableQuantity>99</AvailableQuantity>
			<BackorderQuantity>0</BackorderQuantity>
			<PendingQuantity>0</PendingQuantity>
			<DemandQuantity>1</DemandQuantity>
			<OnHandQuantity>100</OnHandQuantity>
		</Measurements>
	</Inventory></ItemInventories>');

		$this->assertCount(1, Mage::getModel('eb2cinventory/feed_item_extractor')->extractInventoryFeed($doc));
	}
}
