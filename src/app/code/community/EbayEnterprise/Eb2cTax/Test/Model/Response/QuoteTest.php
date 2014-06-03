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
 * tests the tax response quote class.
 */
class EbayEnterprise_Eb2cTax_Test_Model_Response_QuoteTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	public static function setUpBeforeClass()
	{
		$write = Mage::getSingleton('core/resource')->getConnection('core_write');
		$write->query('truncate table eb2ctax_response_quote');
	}

	public function test()
	{
		$data = array(
			'code'           => 'The taxquote code',
			'quote_item_id'  => 1,
			'type'           => 0,
			'situs'          => 'the situs',
			'calculated_tax' => 4.38,
			'effective_rate' => 0.0625,
			'taxable_amount' => 20.95,
		);
		$q = Mage::getModel('eb2ctax/response_quote');
		$q->setData($data);
		$q->save();

		$qColl = $q->getCollection()->load();
		$this->assertSame(1, $qColl->count());

		$q = $qColl->getFirstItem();
		$q->setSitus('the new situs');
		$q->save();

		$qColl = $q->getCollection()->load();
		$this->assertSame(1, $qColl->count());
		$q = $qColl->getFirstItem();
		$this->assertSame('the new situs', $q->getSitus());
	}
}
