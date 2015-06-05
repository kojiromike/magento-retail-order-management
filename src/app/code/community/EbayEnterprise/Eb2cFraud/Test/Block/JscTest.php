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

class EbayEnterprise_Eb2cFraud_Test_Block_JscTest extends EcomDev_PHPUnit_Test_Case
{
    /**
     * Constructor should populate the block instance with "magic" data about the
     * collector to use.
     */
    public function testConstruct()
    {
        $jscUrl = 'https://magento.domain/js';
        $fraudHelper = $this->getHelperMock('eb2cfraud/http', array('getJscUrl'));
        $fraudHelper->expects($this->once())
            ->method('getJscUrl')
            ->will($this->returnValue($jscUrl));
        $this->replaceByMock('helper', 'eb2cfraud/http', $fraudHelper);

        // provide a single collector so it should be easy to tell which is
        // randomly selected
        $collectors = array(array(
            'filename' => 'jsc_filename.js',
            'formfield' => 'jsc_formfield',
            'function' => "jsc_function",
        ));
        $block = new EbayEnterprise_Eb2cFraud_Block_Jsc(array('collectors' => $collectors));
        $this->assertSame(
            $jscUrl . DS . 'jsc_filename.js',
            $block->getCollectorUrl()
        );
        $this->assertSame(
            "jsc_function('jsc_formfield');",
            $block->getCall()
        );
        $this->assertSame(
            '<input type="hidden" name="jsc_formfield" id="jsc_formfield" />',
            $block->getField()
        );
        $this->assertSame(
            // name of this field comes from a const on the helper used to retrieve
            // the data from the request POST data
            // @see EbayEnterprise_Eb2cFraud_Helper_Data::JSC_FIELD_NAME
            '<input type="hidden" name="eb2cszyvl" id="eb2cszyvl" value="jsc_formfield" />',
            $block->getMappingField()
        );
    }
}
