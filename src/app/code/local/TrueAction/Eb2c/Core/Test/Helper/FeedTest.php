<?php
/**
 * @category  TrueAction
 * @package   TrueAction_Eb2c
 * @copyright Copyright (c) 2013 True Action (http://www.trueaction.com)
 */
class TrueAction_Eb2c_Core_Test_Helper_FeedTest extends EcomDev_PHPUnit_Test_Case
{
	protected $_feed;

	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_feed = Mage::helper('eb2ccore/feed');
	}

	public function providerValidateHeader()
	{
		$domDocument = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$sampleFeed = dirname(__FILE__) . '/FeedTest/fixtures/sample-feed.xml';
		$domDocument->load($sampleFeed);
		return array(
			array($domDocument, 'ItemInventories', '2.3.0')
		);
	}

	/**
	 * test validateHeader method, when the header file is valid
	 *
	 * @test
	 * @dataProvider providerValidateHeader
	 * @loadFixture configData.yaml
	 */
	public function testValidateHeader($doc, $expectEventType, $expectHeaderVersion)
	{
		$this->assertSame(
			true,
			$this->_feed->validateHeader($doc, $expectEventType, $expectHeaderVersion)
		);
	}

	public function providerValidateHeaderWithInvalidNode()
	{
		$domDocument = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$sampleFeed = dirname(__FILE__) . '/FeedTest/fixtures/sample-feed-with-invalid-node.xml';
		$domDocument->load($sampleFeed);
		return array(
			array($domDocument, 'ItemInventories', '2.3.0')
		);
	}

	/**
	 * test validateHeader method, when the header feed doesn't have valid xml node
	 *
	 * @test
	 * @dataProvider providerValidateHeaderWithInvalidNode
	 * @loadFixture configData.yaml
	 */
	public function testValidateHeaderWithInvalidNode($doc, $expectEventType, $expectHeaderVersion)
	{
		$this->assertSame(
			false,
			$this->_feed->validateHeader($doc, $expectEventType, $expectHeaderVersion)
		);
	}

	public function providerValidateHeaderWithFeedInvalidData()
	{
		$domDocument = new TrueAction_Dom_Document('1.0', 'UTF-8');
		$sampleFeed = dirname(__FILE__) . '/FeedTest/fixtures/sample-feed-with-feed-invalid-data.xml';
		$domDocument->load($sampleFeed);
		return array(
			array($domDocument, 'ItemInventories', '2.3.0')
		);
	}

	/**
	 * test validateHeader method, when the header feed data doesn't match expected
	 *
	 * @test
	 * @dataProvider providerValidateHeaderWithFeedInvalidData
	 * @loadFixture configData.yaml
	 */
	public function testValidateHeaderWithFeedInvalidData($doc, $expectEventType, $expectHeaderVersion)
	{
		$this->assertSame(
			false,
			$this->_feed->validateHeader($doc, $expectEventType, $expectHeaderVersion)
		);
	}
}
