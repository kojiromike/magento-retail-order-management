<?php
class EbayEnterprise_Eb2cOrder_Test_Helper_Overrides_Sales_GuestTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * Test overriding sales/guest helper, where when there's no post data the parent validator method return false
	 * @test
	 */
	public function testLoadValidOrderParentMethodReturnFalse()
	{
		$urlMock = $this->getModelMockBuilder('core/url')
			->disableOriginalConstructor()
			->setMethods(array('getUrl'))
			->getMock();
		$urlMock->expects($this->any())
			->method('getUrl')
			->will($this->returnValue('http://exmple.com'));

		$this->replaceByMock('model', 'core/url', $urlMock);

		$coreSessionMock = $this->getModelMockBuilder('core/session')
			->disableOriginalConstructor()
			->setMethods(array('addError'))
			->getMock();
		$coreSessionMock->expects($this->any())
			->method('addError')
			->will($this->returnValue(true));
		$this->replaceByMock('singleton', 'core/session', $coreSessionMock);

		$customerSessionMock = $this->getModelMockBuilder('customer/session')
			->disableOriginalConstructor()
			->setMethods(array('getCustomer', 'getId', 'isLoggedIn'))
			->getMock();
		$customerSessionMock->expects($this->any())
			->method('getCustomer')
			->will($this->returnSelf());
		$customerSessionMock->expects($this->any())
			->method('getId')
			->will($this->returnValue(0));
		$customerSessionMock->expects($this->any())
			->method('isLoggedIn')
			->will($this->returnValue(0));
		$this->replaceByMock('singleton', 'customer/session', $customerSessionMock);

		$this->assertSame(false, Mage::helper('sales/guest')->loadValidOrder());
	}
}
