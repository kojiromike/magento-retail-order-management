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

/**
 * Exercise the various methods for core shell feed
 *
 */
class EbayEnterprise_Eb2cCore_Test_Model_Feed_ShellTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	const FAKE_PROCESS_FEEDS_RETURN = 42;
	private $_shellCore;

	public function setUp()
	{
		$this->_mockProductFeedModel(self::FAKE_PROCESS_FEEDS_RETURN);
		$this->_shellCore = Mage::getModel('eb2ccore/feed_shell',
			array (
				'feed_set' => array(
					'eb2cproduct/feed'
				)
			)
		);
	}

	/**
	 * Test a valid feed name configured, but pointing to model that does not implement the appropriate interface
	 *
	 * @expectedException Mage_Core_Exception
	 */
	public function testModelLacksImplementation()
	{
		$fakeShell = Mage::getModel('eb2ccore/feed_shell',
			array (
				'feed_set' => array(
					'eb2ccore/feed_shell', // Purposely NOT a valid feed model, may as well be me myself, I KNOW I'm not.
				)
			)
		);
		$fakeShell->runFeedModel('feed');
	}

	/**
	 * Test a valid feed name configured, but pointing to a non-existent model
	 *
	 * @test
	 * @expectedException Mage_Core_Exception
	 */
	public function testInvalidModelConfigured()
	{
		$fakeShell = Mage::getModel('eb2ccore/feed_shell',
			array (
				'feed_set' => array(
					'someGarbageModule43346dfbdc2c57f0889d37ce061e58c57daffe6e/some_invalid_feed',
				)
			)
		);
		$fakeShell->runFeedModel('Garbage');
	}

	/**
	 * Test that we'll find a valid model, and call its processFeeds() method,
	 * which in setup we mocked it up to return FAKE_PROCESS_FEEDS_RETURN
	 *
	 * @test
	 */
	public function testRunFeedModel()
	{
		$this->assertEquals(
			self::FAKE_PROCESS_FEEDS_RETURN,
			$this->_shellCore->runFeedModel('product')
		);
	}

	/**
	 * Non-matches are invalid. If you ask for a feed that producese no match, should return false.
	 *
	 * @test
	 */
	public function testInvalidFeedIsFalse()
	{
		$fakeShell = Mage::getModel('eb2ccore/feed_shell',
			array (
				'feed_set' => array(
					'scott_b',
					'scott_s',
					'scott_v',
				)
			)
		);
		$this->assertFalse($fakeShell->getFeedModel('michael'));
	}

	/**
	 * Multiple matches are invalid. If I pass an ambiguous feed name, I should see false returned.
	 *
	 * @test
	 */
	public function testMultipleMatchesIsFalse()
	{
		Mage::getModel('eb2ccore/feed_shell',
			array (
				'feed_set' => array(
					'michael_p',
					'michael_s',
					'michael_w',
				)
			)
		);
		$this->assertFalse($this->_shellCore->getFeedModel('michael'));
	}

	/**
	 * test listAvailable returns all the feeds we configured
	 *
	 * @test
	 * @loadFixture listAvailableFeeds
	 */
	public function testListAvailableFeeds()
	{
		$fakeShell = Mage::getModel('eb2ccore/feed_shell');

		$feedSet = array(
			'module/adam',
			'module/mike',
			'module/reggie',
			'module/scott',
		);

		$availableFeeds = $fakeShell->listAvailableFeeds();
		foreach( $feedSet as $aFeed ) {
			$this->assertContains($aFeed, $availableFeeds);
		}
	}

	/**
	 * Set up an Order Status Feed as the 'dummy', and he mocks his fs_tool
	 *
	 */
	private function _mockProductFeedModel($dummyReturnValue)
	{
		// Mock the Varien_Io_File, need a mock file system
		$mockFsTool = $this->getMock('Varien_Io_File', array(
			'cd',
			'checkAndCreateFolder',
			'ls',
			'mv',
			'pwd',
			'setAllowCreateFolders',
			'open',
		));
		$mockFsTool
			->expects($this->any())
			->method('cd')
			->will($this->returnValue(true));
		$mockFsTool
			->expects($this->any())
			->method('checkAndCreateFolder')
			->will($this->returnValue(true));
		$mockFsTool
			->expects($this->any())
			->method('mv')
			->will($this->returnValue(true));
		$mockFsTool
			->expects($this->any())
			->method('ls')
			->will($this->returnValue(array()));
		$mockFsTool
			->expects($this->any())
			->method('pwd')
			->will($this->returnValue('doesnMatter'));
		$mockFsTool
			->expects($this->any())
			->method('setAllowCreateFolders')
			->with($this->logicalOr($this->identicalTo(true), $this->identicalTo(false)))
			->will($this->returnSelf());
		$mockFsTool
			->expects($this->any())
			->method('open')
			->will($this->returnValue(true));

		// Mock order status feeds so I can fake a call to processFeeds(), this is
		// where I need a mock FS.
		$mockProductFeed = $this->getModelMock(
			'eb2cproduct/feed',
			array(
				'processFeeds',
			),
			false,
			array(
				array (
					'fs_tool' => $mockFsTool,
				)
			)
		);

		$mockProductFeed
			->expects($this->any())
			->method('processFeeds')
			->will($this->returnValue($dummyReturnValue));

		$this->replaceByMock('model', 'eb2cproduct/feed', $mockProductFeed);
	}
}
