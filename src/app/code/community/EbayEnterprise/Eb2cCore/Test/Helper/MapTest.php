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

class EbayEnterprise_Eb2cCore_Test_Helper_MapTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * @var EbayEnterprise_Dom_Document
	 */
	protected $_doc;

	public function setUp()
	{
		parent::setUp();
		$this->_doc = Mage::helper('eb2ccore')->getNewDomDocument();
	}
	public function tearDown()
	{
		parent::tearDown();
		$this->_doc = null;
	}
	/**
	 * Test extractStringValue method for the following expectations
	 * Expectation 1: this test is expected to call the EbayEnterprise_Eb2cCore_Helper_Map::extractStringValue method with a known
	 *                DOMNodeList object the method is then expected to return a string value extract from the
	 *                DOMNodeList object
	 */
	public function testExtractStringValue()
	{
		$this->_doc->loadXML(
			'<ItemMaster>
				<Item operation_type="Add" gsi_client_id="MAGTNA" catalog_id="45">
					<ItemId>
						<ClientItemId>45-2BCEC162</ClientItemId>
					</ItemId>
				</Item>
			</ItemMaster>'
		);
		$xpath = Mage::helper('eb2ccore')->getNewDomXPath($this->_doc);
		$this->assertSame(
			'45-2BCEC162',
			Mage::helper('eb2ccore/map')->extractStringValue(
				$xpath->query('Item/ItemId/ClientItemId', $this->_doc->documentElement),
				Mage::getModel('catalog/product')
			)
		);
	}
	/**
	 * Test extractBoolValue method for the following expectations
	 * Expectation 1: this test is expected to call the EbayEnterprise_Eb2cCore_Helper_Map::extractBoolValue method with a known
	 *                DOMNodeList object. The method is then expected to return a boolean value extract from the
	 *                DOMNodeList object
	 * Expectation 2: the EbayEnterprise_Eb2cCore_Helper_Data::parseBool method is expected to be given, the extract value
	 *                from the DOMNodeList object object and return a boolean representative of the passed in string
	 * @mock EbayEnterprise_Eb2cCore_Helper_Data::parseBool
	 */
	public function testExtractBoolValue()
	{
		$this->_doc->loadXML(
			'<ItemMaster>
				<Item operation_type="Add" gsi_client_id="MAGTNA" catalog_id="45">
					<BaseAttributes>
						<IsDropShipped>true</IsDropShipped>
					</BaseAttributes>
				</Item>
			</ItemMaster>'
		);
		$xpath = Mage::helper('eb2ccore')->getNewDomXPath($this->_doc);

		$productHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('parseBool'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('parseBool')
			->will($this->returnValueMap(array(
				array('true', true)
			)));
		$this->replaceByMock('helper', 'eb2ccore', $productHelperMock);

		$this->assertSame(
			true,
			Mage::helper('eb2ccore/map')->extractBoolValue(
				$xpath->query('Item/BaseAttributes/IsDropShipped', $this->_doc->documentElement),
				Mage::getModel('catalog/product')
			)
		);
	}
	/**
	 * Test extractIntValue method for the following expectations
	 * Expectation 1: this test is expected to call the EbayEnterprise_Eb2cCore_Helper_Map::extractIntValue method with a known
	 *                DOMNodeList object the method is then expected to return a string value cast as an integer value
	 *                DOMNodeList object
	 */
	public function testExtractIntValue()
	{
		$this->_doc->loadXML(
			'<ItemMaster>
				<Item operation_type="Add" gsi_client_id="MAGTNA" catalog_id="45">
					<ExtendedAttributes>
						<Buyer>
							<BuyerId>999</BuyerId>
						</Buyer>
					</ExtendedAttributes>
				</Item>
			</ItemMaster>'
		);
		$xpath = Mage::helper('eb2ccore')->getNewDomXPath($this->_doc);
		$this->assertSame(
			999,
			Mage::helper('eb2ccore/map')->extractIntValue(
				$xpath->query('Item/ExtendedAttributes/Buyer/BuyerId', $this->_doc->documentElement),
				Mage::getModel('catalog/product')
			)
		);
	}
	/**
	 * Test extractFloatValue method for the following expectations
	 * Expectation 1: this test is expected to call the EbayEnterprise_Eb2cCore_Helper_Map::extractFloatValue method with a known
	 *                DOMNodeList object the method is then expected to return a string value cast as an float value
	 *                DOMNodeList object
	 */
	public function testExtractFloatValue()
	{
		$this->_doc->loadXML(
			'<ItemMaster>
				<Item operation_type="Add" gsi_client_id="MAGTNA" catalog_id="45">
					<ExtendedAttributes>
						<Price>3.98</Price>
					</ExtendedAttributes>
				</Item>
			</ItemMaster>'
		);
		$xpath = Mage::helper('eb2ccore')->getNewDomXPath($this->_doc);
		$this->assertSame(
			3.98,
			Mage::helper('eb2ccore/map')->extractFloatValue(
				$xpath->query('Item/ExtendedAttributes/Price', $this->_doc->documentElement),
				Mage::getModel('catalog/product')
			)
		);
	}
	/**
	 * return whatever got pass as parameter
	 */
	public function testPassThrough()
	{
		$x = 'anything';
		$this->assertSame(
			$x,
			Mage::helper('eb2ccore/map')->passThrough(
				$x, Mage::getModel('catalog/product')
			)
		);
	}
	/**
	 * test summing the float values of a list of nodes
	 */
	public function testExtractFloatSum()
	{
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML('<_><a>1.1</a><b>5.5</b><c>3.0</c></_>');
		$this->assertSame(
			9.6,
			Mage::helper('eb2ccore/map')->extractFloatSum($doc->documentElement->childNodes)
		);
	}
}
