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

class EbayEnterprise_GiftCard_Model_Mask
{
    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $coreHelper;

    /**
     * @param array $initParams May contain:
     *                          - 'core_helper' => EbayEnterprise_Eb2cCore_Helper_Data
     */
    public function __construct(array $initParams = [])
    {
        list($this->coreHelper) = $this->checkTypes(
            $this->nullCoalesce($initParams, 'core_helper', Mage::helper('eb2ccore'))
        );
    }

    /**
     * Type checks for self::__construct $initParams
     *
     * @param  EbayEnterprise_Eb2cCore_Helper_Data
     * @return mixed[]
     */
    protected function checkTypes(EbayEnterprise_Eb2cCore_Helper_Data $coreHelper)
    {
        return func_get_args();
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the
     * default value.
     * @param  array
     * @param  string | int $field Valid array key
     * @param  mixed
     * @return mixed
     */
    protected function nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }

    /**
     * Mask the StoredValue request XML message of any sensitive data - PAN, PIN.
     *
     * @param  string
     * @return string
     */
    public function maskXmlNodes($xml)
    {
        /** @var DOMDocument */
        $doc = $this->coreHelper->getNewDomDocument();
        $doc->loadXML($xml);
        /** @var DOMElement */
        $element = $doc->documentElement;
        /** @var DOMXPath */
        $xpath = $this->coreHelper->getNewDomXPath($doc);
        $xpath->registerNamespace('x', $element->namespaceURI);

        $this->maskPin($doc, $xpath)
            ->maskPan($doc, $xpath);

        return $doc->saveXML();
    }

    /**
     * Mask the payload PIN.
     *
     * @param  DOMDocument
     * @param  DOMXPath
     * @return self
     */
    protected function maskPin(DOMDocument $doc, DOMXPath $xpath)
    {
        /** @var array */
        $nodes = ['Pin' => 'x:Pin', 'EncryptedPin' => 'x:EncryptedPin'];
        $this->doTheMasking($doc, $xpath, $nodes, false);
        return $this;
    }

    /**
     * Mask the payload PAN.
     *
     * @param  DOMDocument
     * @param  DOMXPath
     * @return self
     */
    protected function maskPan(DOMDocument $doc, DOMXPath $xpath)
    {
        /** @var array */
        $nodes = [
            'PaymentAccountUniqueId' => 'x:PaymentAccountUniqueId|x:PaymentContext/x:PaymentAccountUniqueId',
            'EncryptedPaymentAccountUniqueId' => 'x:EncryptedPaymentAccountUniqueId|x:PaymentContext/x:EncryptedPaymentAccountUniqueId'
        ];
        $this->doTheMasking($doc, $xpath, $nodes, true);
        return $this;
    }

    /**
     * Mask node values using the passed in document object, xpath object and array of
     * nodes mapped to xpath expressions path.
     *
     * @param  DOMDocument
     * @param  DOMXPath
     * @param  array
     * @param  bool
     * @return self
     */
    protected function doTheMasking(DOMDocument $doc, DOMXPath $xpath, array $nodes, $hasAttribute=false)
    {
        foreach ($nodes as $node => $expression) {
            /** @var DOMElement */
            $newNode = $doc->createElement($node, '***');
            /** @var DOMNodeList */
            $nodeList = $xpath->query($expression);
            if ($nodeList->length) {
                /** @var DOMElement */
                $oldNode = $nodeList->item(0);
                if ($hasAttribute) {
                    $newNode->setAttribute('isToken', $oldNode->getAttribute('isToken'));
                }
                $oldNode->parentNode->replaceChild($newNode, $oldNode);
            }
        }
        return $this;
    }
}
