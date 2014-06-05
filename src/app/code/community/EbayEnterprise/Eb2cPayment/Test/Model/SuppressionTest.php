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

class EbayEnterprise_Eb2cPayment_Test_Model_SuppressionTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	public function providerIsConfigSuppressed()
	{
		return array(
			// suppress the giftcard section when payments is enabled
			array('giftcard', 'general', 1, true),
			array('giftcard', 'general', 0, false),
			array('giftcard', 'someothergroup', 1, true),
			// when payments is enabled, suppress all payment groups other than those we allow
			array('payment', 'allowed_group', 1, false),
			array('payment', 'disallowed_group', 1, true),
			// when payments is off, suppress only pbridge_eb2cpayment_cc
			array('payment', 'pbridge_eb2cpayment_cc', 0, true),
			array('payment', 'allowed_group', 0, false),
			array('payment', 'disallowed_group', 0, false),
		);
	}

	/**
	 * verify true is returned for config nodes that should not be rendered.
	 * @dataProvider providerIsConfigSuppressed
	 */
	public function testIsConfigSuppressed($sectionName, $groupName, $paymentState, $expected)
	{
		$allowedGroups = array('allowed_group');
		$this->replaceCoreConfigRegistry(array(
			'isPaymentEnabled' => $paymentState
		));
		$testModel = Mage::getModel('eb2cpayment/suppression');
		$this->_reflectProperty($testModel, '_allowedPaymentMethods')->setValue($testModel, $allowedGroups);
		$result = $testModel->isConfigSuppressed($sectionName, $groupName);

		$this->assertSame(
			$expected,
			$result,
			"[$sectionName, $groupName] should " .
			$expected ? '' : 'not ' .
			"be suppressed when payments is [$paymentState]"
		);
	}
}
