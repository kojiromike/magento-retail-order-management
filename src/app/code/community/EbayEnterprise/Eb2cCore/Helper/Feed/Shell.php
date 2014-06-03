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

class EbayEnterprise_Eb2cCore_Helper_Feed_Shell extends Mage_Core_Helper_Abstract
{
	/**
	 * Returns an array of available feed models configured in core config.xml.
	 * Does *not* validate them in any way - just returns what's configured.
	 * @return array Configured feed models in the form 'module/class_name'
	 */
	public function getConfiguredFeedModels()
	{
		$config = Mage::helper('eb2ccore')->getConfigModel();
		$availableFeeds = array();
		foreach( $config->feedAvailableModels as $module => $feedClass ) {
			foreach( $feedClass as $class => $enabled ) {
				if( $enabled ) {
					$availableFeeds[] = $module . '/' . $class;
				}
			}
		}
		return $availableFeeds;
	}
}
