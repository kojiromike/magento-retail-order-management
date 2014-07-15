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

class EbayEnterprise_Eb2cPayment_Test_Model_Adminhtml_CommentTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * Test getCommentText method
	 */
	public function testGetCommentText()
	{
		$paymentHelperMock = $this->getHelperMockBuilder('eb2cpayment/data')
			->setMethods(array('__'))
			->getMock();
		$paymentHelperMock->expects($this->once())
			->method('__')
			->with($this->equalTo('EbayEnterprise_Eb2cPayment_Admin_System_Config_PBridge_Comments'))
			->will($this->returnValue('click here to <a href="%s">Configure Payment Bridge</a>'));
		$this->replaceByMock('helper', 'eb2cpayment', $paymentHelperMock);

		$paymentSectionUrl = 'http://example.com/admin/system_config/edit/section/payment/key/12233/';
		$commentModelMock = $this->getModelMockBuilder('eb2cpayment/adminhtml_comment')
			->disableOriginalConstructor()
			->setMethods(array('_getUrl'))
			->getMock();
		$commentModelMock->expects($this->once())
			->method('_getUrl')
			->will($this->returnValue($paymentSectionUrl));

		$this->assertSame(
			sprintf('click here to <a href="%s">Configure Payment Bridge</a>', $paymentSectionUrl),
			$commentModelMock->getCommentText()
		);
	}

	/**
	 * Test _getUrl method
	 */
	public function testGetUrl()
	{
		$adminhtmlHelperMock = $this->getHelperMockBuilder('adminhtml/data')
			->disableOriginalConstructor()
			->setMethods(array('getUrl'))
			->getMock();
		$adminhtmlHelperMock::staticExpects($this->once())
			->method('getUrl')
			->with($this->equalTo('adminhtml/system_config/edit'), $this->equalTo(array('section' => 'payment')))
			->will($this->returnValue('http://example.com/admin/system_config/edit/section/payment/key/12233/'));
		$this->replaceByMock('helper', 'adminhtml', $adminhtmlHelperMock);

		$commentModelMock = $this->getModelMockBuilder('eb2cpayment/adminhtml_comment')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$this->assertSame(
			'http://example.com/admin/system_config/edit/section/payment/key/12233/',
			$this->_reflectMethod($commentModelMock, '_getUrl')->invoke($commentModelMock)
		);
	}
}
