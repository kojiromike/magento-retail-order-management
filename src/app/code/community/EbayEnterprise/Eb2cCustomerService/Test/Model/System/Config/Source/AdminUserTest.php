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

class EbayEnterprise_Eb2cCustomerService_Test_Model_System_Config_Source_AdminUserTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	public function testToOptionArray()
	{
		$username = 'CSR';
		$userid = 2;

		$user = $this->getModelMockBuilder('admin/user')
			->disableOriginalConstructor()
			->setMethods(array('getUsername', 'getId'))
			->getMock();
		$userCollection = $this->getResourceModelMockBuilder('admin/user_collection')
			->disableOriginalConstructor()
			->setMethods(array('addFieldToSelect', 'getItems'))
			->getMock();
		$users = array($user);

		$userCollection->expects($this->once())
			->method('addFieldToSelect')
			->with($this->identicalTo(array('username', 'user_id')))
			->will($this->returnSelf());
		$userCollection->expects($this->once())
			->method('getItems')
			->will($this->returnValue($users));
		$user->expects($this->once())
			->method('getUsername')
			->will($this->returnValue($username));
		$user->expects($this->once())
			->method('getId')
			->will($this->returnValue($userid));

		$this->replaceByMock('resource_model', 'admin/user_collection', $userCollection);

		$this->assertSame(
			array(array('label' => $username, 'value' => $userid)),
			Mage::getModel('eb2ccsr/system_config_source_adminuser')->toOptionArray()
		);
	}
}
