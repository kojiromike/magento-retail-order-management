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

class EbayEnterprise_Eb2cProduct_Test_Model_Resource_SetupTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * @loadExpectation
	 * @dataProvider dataProvider
	 */
	public function testApplyToAllSets($expectation, $validEntityTypes, $existingAttributeId)
	{
		$e = $this->expected($expectation);
		$attributesData = $e->getAttributeData();
		$attributeData = $attributesData['tax_code'];
		$attrInfo = $this->getModelMock('eb2cproduct/attributes', array(
			'getTargetEntityTypeIds',
			'getAttributesData'
		));
		$attrInfo->expects($this->once())
			->method('getTargetEntityTypeIds')
			->will($this->returnValue($validEntityTypes));
		$attrInfo->expects($this->once())
			->method('getAttributesData')
			->will($this->returnValue($attributesData));

		$setup = $this->getMockBuilder('EbayEnterprise_Eb2cProduct_Model_Resource_Eav_Entity_Setup')
			->disableOriginalConstructor()
			->setMethods(array('addAttribute', 'getAttribute', '_logWarn'))
			->getMock();
		$setup->expects($this->once())
			->method('addAttribute')
			->with(
				$this->identicalTo($e->getEntityId()),
				$this->identicalTo($e->getAttributeCode()),
				$this->identicalTo($attributeData)
			)
			->will($this->returnSelf());
		$setup->expects($this->once())
			->method('getAttribute')
			->with(
				$this->identicalTo($e->getEntityId()),
				$this->identicalTo($e->getAttributeCode()),
				$this->identicalTo('attribute_id')
			)
			->will($this->returnValue($existingAttributeId));

		$logCalls = $e->getLogWarnCalled() ? $this->once() : $this->never();
		$setup->expects($logCalls)
			->method('_logWarn');
		$setup->applyToAllSets($attrInfo);
	}
}
