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

class EbayEnterprise_GiftCard_Test_Model_MaskTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /** @var EbayEnterprise_GiftCard_Model_Mask */
    protected $mask;
    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $coreHelper;

    public function setUp()
    {
        parent::setUp();
        $this->mask = Mage::getModel('ebayenterprise_giftcard/mask');
        $this->coreHelper = Mage::helper('eb2ccore');
    }

    /**
     * @return array
     */
    public function providerMaskXmlNodes()
    {
        return [
            [
                file_get_contents(__DIR__ . DS . 'MaskTest/fixtures/StoredValueBalanceRequest.xml'),
                '***',
                ['string(x:PaymentAccountUniqueId)', 'string(x:Pin)'],
                'x'
            ],
            [
                file_get_contents(__DIR__ . DS . 'MaskTest/fixtures/StoredValueBalanceReply.xml'),
                '***',
                ['string(x:PaymentAccountUniqueId)'],
                'x'
            ],
            [
                file_get_contents(__DIR__ . DS . 'MaskTest/fixtures/StoredValueRedeemRequest.xml'),
                '***',
                ['string(x:PaymentContext/x:PaymentAccountUniqueId)', 'string(x:Pin)'],
                'x'
            ],
            [
                file_get_contents(__DIR__ . DS . 'MaskTest/fixtures/StoredValueRedeemReply.xml'),
                '***',
                ['string(x:PaymentContext/x:PaymentAccountUniqueId)'],
                'x'
            ],
            [
                file_get_contents(__DIR__ . DS . 'MaskTest/fixtures/StoredValueRedeemVoidRequest.xml'),
                '***',
                ['string(x:PaymentContext/x:PaymentAccountUniqueId)', 'string(x:Pin)'],
                'x'
            ],
            [
                file_get_contents(__DIR__ . DS . 'MaskTest/fixtures/StoredValueRedeemVoidReply.xml'),
                '***',
                ['string(x:PaymentContext/x:PaymentAccountUniqueId)'],
                'x'
            ],
        ];
    }

    /**
     * @param  DOMDocument
     * @param  string
     * @return DOMXPath
     */
    protected function getXPath(DOMDocument $doc, $prefix)
    {
        /** @var DOMXPath */
        $xpath = $this->coreHelper->getNewDomXPath($doc);
        /** @var DOMElement */
        $element = $doc->documentElement;
        $xpath->registerNamespace($prefix, $element->namespaceURI);
        return $xpath;
    }

    /**
     * Scenario: Mask StoredValue* Payload XML sensitive data
     * Given an XML Payload containing sensitive data.
     * When the mask XML nodes is applied.
     * Then all sensitive data are masked in XML Payload.
     *
     * @param string
     * @param string
     * @param array
     * @param string
     * @dataProvider providerMaskXmlNodes
     */
    public function testMaskXmlNodes($xml, $mask, array $expressionList, $prefix)
    {
        /** @var DOMDocument */
        $doc = $this->coreHelper->getNewDomDocument();
        $doc->loadXML($xml);
        /** @var DOMXPath */
        $xpath = $this->getXPath($doc, $prefix);
        foreach ($expressionList as $expression) {
            // Proving that the value of the nodes are not masked prior to calling
            // the method ebayenterprise_giftcard/giftcard::maskXmlNodes() on the
            // XML Payload.
            $this->assertNotSame($mask, $xpath->evaluate($expression));
        }
        /** @var DOMDocument */
        $maskDoc = $this->coreHelper->getNewDomDocument();
        // Masking the node values in the XML Payload
        $maskDoc->loadXML($this->mask->maskXmlNodes($xml));
        /** @var DOMXPath */
        $maskXPath = $this->getXPath($maskDoc, $prefix);
        foreach ($expressionList as $expression) {
            // Proving that the value of the nodes are now masked after calling
            // the method ebayenterprise_giftcard/giftcard::maskXmlNodes() on the
            // XML Payload.
            $this->assertSame($mask, $maskXPath->evaluate($expression));
        }
    }
}
