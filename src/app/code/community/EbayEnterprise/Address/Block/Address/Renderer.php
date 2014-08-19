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
 * Class EbayEnterprise_Address_Block_Address_Renderer
 *
 * Renders a single address within the suggestions block.
 */
class EbayEnterprise_Address_Block_Address_Renderer extends Mage_Customer_Block_Address_Renderer_Default
{
	/**
	 * Initialize the block data
	 *
	 * @param string $format template used to format the address
	 * @return self
	 */
	public function initType($format)
	{
		$type = new Varien_Object();
		$type->setCode('address_verification')
			->setTitle('Address Verification Suggestion')
			->setDefaultFormat($format)
			->setHtmlEscape(true);
		$this->setType($type);
		return $this;
	}
}
