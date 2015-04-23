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

class EbayEnterprise_Eb2cTax_Test_Model_Validation_OrderitemTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * @return EbayEnterprise_Eb2cCore_Helper_Data
	 */
	protected function _getHelper()
	{
		return Mage::helper('eb2ccore');
	}

	/**
	 * @param  string
	 * @return EbayEnterprise_Dom_Document
	 */
	protected function _getDoc($file)
	{
		$doc = $this->_getHelper()->getNewDomDocument();
		$doc->loadXML(file_get_contents($file));
		return $doc;
	}

	/**
	 * @return array
	 */
	public function providerValidate()
	{
		return [
			[
				$this->_getDoc(__DIR__ . '/OrderitemTest/fixtures/MatchTaxRequest.xml'),
				$this->_getDoc(__DIR__ . '/OrderitemTest/fixtures/MatchTaxResponse.xml'),
				true,
			],
			[
				$this->_getDoc(__DIR__ . '/OrderitemTest/fixtures/UnMatchTaxRequest.xml'),
				$this->_getDoc(__DIR__ . '/OrderitemTest/fixtures/UnMatchTaxResponse.xml'),
				false,
			],
		];
	}

	/**
	 * Mocking the EbayEnterprise_MageLog_Helper_Context::_getOptionalSessionData()
	 * method to prevent session from starting.
	 *
	 * @return Mock_EbayEnterprise_MageLog_Helper_Context
	 */
	protected function _getMockContext()
	{
		$context = $this->getHelperMock('ebayenterprise_magelog/context', ['_getOptionalSessionData']);
		$context->expects($this->any())
			->method('_getOptionalSessionData')
			->will($this->returnValue([]));
		return $context;
	}

	/**
	 * Test validating tax request data against tax response payload.
	 *
	 * @param EbayEnterprise_Dom_Document
	 * @param EbayEnterprise_Dom_Document
	 * @param bool
	 * @dataProvider providerValidate
	 */
	public function testValidate(EbayEnterprise_Dom_Document $requestDoc, EbayEnterprise_Dom_Document $responseDoc, $isValid)
	{
		$validation = Mage::getModel('eb2ctax/validation_orderitem', [
			'request_doc' => $requestDoc,
			'response_doc' => $responseDoc,
			'context' => $this->_getMockContext(),
		]);
		$this->assertSame($isValid, $validation->validate());
	}
}
