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

require_once 'abstract.php';

/**
 * Product Attribute removal script for DE1585/ TA20295
 * This script should only be run as a patch for systems which incorrectly have Export
 * attributes but are operating in an Import mode.
 */
class EbayEnterprise_Eb2c_Shell_Attribute_Remover extends Mage_Shell_Abstract
{
	private $_attributesToDelete = array(
		'drop_ship_supplier_name',
		'drop_ship_supplier_number',
		'drop_ship_supplier_part_number',
		'drop_shipped',
		'gift_card_tender_code',
		'hierarchy_class_description',
		'hierarchy_class_number',
		'hierarchy_dept_description',
		'hierarchy_dept_number',
		'hierarchy_subclass_description',
		'hierarchy_subclass_number',
		'hierarchy_subdept_description',
		'hierarchy_subdept_number',
		'item_type',
	);
	/**
	 * Delete product attributes named in the attributesToDelete array
	 */
	public function run()
	{
		if( !count($this->_args) ) {
			echo $this->usageHelp();
		} else if( $this->getArg('run') === 'yes' ) {
			$setup = Mage::getResourceModel('catalog/setup','core_setup');
			foreach ($this->_attributesToDelete as $attributeToDelete) {
				try {
					$setup->startSetup();
					$setup->removeAttribute('catalog_product', $attributeToDelete);
					$setup->endSetup();
				} catch (Exception $e) {
					echo "Error deleting attribute '{$attributeToDelete}': {$e->getMessage()}\n";
				}
			}
		} else {
			echo 'Refusing to run, invalid options' . "\n";
			echo $this->usageHelp();
		}
	}
	/**
	 * Return some help text
	 * @return string
	 */
    public function usageHelp()
    {
        $scriptName = basename(__FILE__);
        $msg = <<<USAGE

Usage: php -f $scriptName -- [options]
  -run yes    Really do it. This script will actually delete attributes from your Magento installation.
              Make sure you really want to delete them from your magento database.
  help        This help

USAGE;
        return $msg;
    }
}

$runner = new EbayEnterprise_Eb2c_Shell_Attribute_Remover();
$runner->run();
