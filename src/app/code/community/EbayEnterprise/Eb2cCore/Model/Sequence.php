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

class EbayEnterprise_Eb2cCore_Model_Sequence
{
    /**
     * Extract the sequence number from xml feed file.
     * @param string $file, an xml file
     * @return string, the sequence number
     */
    protected function _extractSequence($file)
    {
        $sequence = '';
        $hlpr = Mage::helper('eb2ccore');
        $domDocument = $hlpr->getNewDomDocument();
        // load feed files to dom object
        $domDocument->load($file);
        $feedXpath = $hlpr->getNewDomXPath($domDocument);
        $correlationId = $feedXpath->query('//MessageHeader/MessageData/CorrelationId');
        if ($correlationId->length) {
            $sequence = (string) $correlationId->item(0)->nodeValue;
        }
        return $sequence;
    }

    /**
     * get feeds sequence.
     *
     * @param array $feeds, a collection of feed files
     * @return array, containing each feed file sequence number.
     */
    public function buildSequence($feeds)
    {
        $results = array();

        if (!empty($feeds)) {
            foreach ($feeds as $feed) {
                $results[] = array('sequence' => $this->_extractSequence($feed), 'feed' => $feed);
            }
        }
        return $results;
    }
}
