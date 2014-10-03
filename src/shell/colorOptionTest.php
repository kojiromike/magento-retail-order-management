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
 * @license	 http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

require_once 'abstract.php';

/**
 * A test for debugging
 */
class EbayEnterprise_Eb2c_Shell_TestExtractColorValue extends Mage_Shell_Abstract
{
	public function run()
	{
		if( !count($this->_args) ) {
			echo $this->usageHelp();
		} else if( $this->getArg('run') === 'yes' ) {
			$doc = Mage::helper('eb2ccore')->getNewDomDocument();
			$xml =<<<END_XML
<Item>
	<ExtendedAttributes>
		<ColorAttributes>
			<Color>
				<Code>47</Code>
				<Description xml:lang="en-US">Black</Description>
				<Description xml:lang="ja-JP">ブラック</Description>
			</Color>
		</ColorAttributes>
	</ExtendedAttributes>
</Item>
END_XML;
			$doc->loadXML($xml);
			$xpath    = new DOMXpath($doc);
			$nodeList = $xpath->query('ExtendedAttributes/ColorAttributes/Color');
			$optionId = Mage::helper('ebayenterprise_catalog/map_attribute')->extractColorValue($nodeList);
			echo "OptionId is $optionId\n";
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
  -run yes    Really do it. This script will actually add an option to your color attribute.
              Make sure you really want to write to your magento database.
  help        This help
USAGE;
        return $msg;
    }
}
$foo = new EbayEnterprise_Eb2c_Shell_TestExtractColorValue();
$foo->run();
