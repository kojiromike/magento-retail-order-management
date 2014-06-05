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

class EbayEnterprise_Eb2cPayment_Overrides_Block_Adminhtml_System_Config_Form extends Mage_Adminhtml_Block_System_Config_Form
{
	/**
	 * Checking field visibility
	 *
	 * @param   Varien_Simplexml_Element $field
	 * @return  bool
	 */
	protected function _canShowField($field)
	{
		$suppression = Mage::getModel('eb2cpayment/suppression');
		$section = $field->xpath('../../groups/..');
		$sectionParent = isset($section[0]) ? $section[0]->xpath('..') : null;
		return !(
			isset($sectionParent[0]) &&
			$sectionParent[0]->getName() === 'sections' &&
			$suppression->isConfigSuppressed($section[0]->getName(), $field->getName())
		) &&
		parent::_canShowField($field);
	}
}
