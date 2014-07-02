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

class EbayEnterprise_Eb2cProduct_Test_Model_ObserversTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * @loadFixture readOnlyAttributes.yaml
	 * lockReadOnlyAttributes reads the config for the attribute codes it needs to protect
	 * from admin panel edits by issuing a lockAttribute against the attribute code.
	 */
	public function testLockReadOnlyAttributes()
	{
		$product = $this->getModelMock('catalog/product', array('lockAttribute'));
		$product->expects($this->exactly(3))
			->method('lockAttribute');

		$varienEvent = $this->getMock('Varien_Event', array('getProduct'));
		$varienEvent->expects($this->once())
			->method('getProduct')
			->will($this->returnValue($product));

		$varienEventObserver = $this->getMock('Varien_Event_Observer', array('getEvent'));
		$varienEventObserver->expects($this->once())
			->method('getEvent')
			->will($this->returnValue($varienEvent));

		Mage::getModel('eb2cproduct/observers')->lockReadOnlyAttributes($varienEventObserver);
	}
	/**
	 * Test that the method EbayEnterprise_Eb2cProduct_Model_Observers::processDom will invoked
	 * and will will run EbayEnterprise_Eb2cProduct_Model_Feed_File::process with the required
	 * parameters
	 */
	public function testProcessDom()
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML(
			'<ItemMaster>
				<Item operation_type="Add" gsi_client_id="MAGTNA" catalog_id="45">
					<ItemId>
						<ClientItemId>45-2BCEC162</ClientItemId>
					</ItemId>
				</Item>
			</ItemMaster>'
		);

		$feedData = array('event_type' => 'ItemMaster');
		$coreFeed = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('getFeedConfig'))
			->getMock();
		$coreFeed->expects($this->any())
			->method('getFeedConfig')
			->will($this->returnValue($feedData));

		$observer = new Varien_Event_Observer(array('event' => new Varien_Event(array('doc' => $doc, 'file_detail' => array(
			'local_file' => 'EbayEnterprise/Product/ItemMaster/Inbound/ItemMaster_TestSubset.xml',
			'core_feed' => 'core feed mock',
			'timestamp' => '2012-07-06 10:09:05',
			'error_file' => '/EbayEnterprise/Eb2c/Feed/Product/ItemMaster/outbound/ItemMaster_20140107224605_12345_ABCD.xml',
			'core_feed' => $coreFeed
		)))));
		$cfgData = array(
			'allowable_event_type' => 'ItemMaster,ContentMaster,iShip,Pricing',
		);
		$config = $this->getModelMock('eb2cproduct/feed_import_config', array('getImportConfigData'));
		$config->expects($this->any())
			->method('getImportConfigData')
			->will($this->returnValue($cfgData));
		$this->replaceByMock('model', 'eb2cproduct/feed_import_config', $config);

		$items = $this->getModelMock('eb2cproduct/feed_import_items', array());
		$this->replaceByMock('model', 'eb2cproduct/feed_import_items', $items);

		$fileModelMock = $this->getModelMockBuilder('eb2cproduct/feed_file')
			->disableOriginalConstructor()
			->setMethods(array('process'))
			->getMock();
		$fileModelMock->expects($this->once())
			->method('process')
			->with($this->identicalTo($config), $this->identicalTo($items))
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2cproduct/feed_file', $fileModelMock);

		$observers = Mage::getModel('eb2cproduct/observers');

		$this->assertSame($observers, $observers->processDom($observer));
	}
}
