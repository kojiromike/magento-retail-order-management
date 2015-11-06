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

class EbayEnterprise_Eb2cInventory_Model_Feed_Item_Extractor
{
    /**
     * Extract item id
     *
     * @param DOMXPath $feed document to query
     * @param DOMNode $itemContext the context node to query within
     * @return string|null the client item id if it can be found in the document, null otherwise
     */
    protected function extractItemId(DOMXPath $feed, DOMNode $itemContext)
    {
        $clientItemIdNodes = $feed->query('ItemId/ClientItemId', $itemContext);
        if ($clientItemIdNodes->length) {
            return (string) $clientItemIdNodes->item(0)->nodeValue;
        }
        return null;
    }

    /**
     * Extract the quantity available to sell minus the already-backordered quantity
     *
     * @param DOMXPath $feed document to query
     * @param DOMNode $itemContext the context node to query within
     * @return int the net quantity available to promise
     */
    protected function extractMeasurements(DOMXPath $feed, DOMNode $itemContext)
    {
        $availableNodes = $feed->query('Measurements/AvailableQuantity', $itemContext);
        $backorderNodes = $feed->query('Measurements/BackorderQuantity', $itemContext);
        $availableQty = $availableNodes->length ? (int) $availableNodes->item(0)->nodeValue : 0;
        $backorderQty = $backorderNodes->length ? (int) $backorderNodes->item(0)->nodeValue : 0;
        return max($availableQty - $backorderQty, 0);
    }

    /**
     * Extract feed data into Varien Objects array.
     *
     * @param DOMDocument $doc the dom document with the loaded feed data
     * @return Varien_Object[]
     */
    public function extractInventoryFeed(DOMDocument $doc)
    {
        $collectionOfItems = [];
        $feed = new DOMXPath($doc);
        $inventory = $feed->query('//Inventory');
        foreach ($inventory as $item) {
            $collectionOfItems[] = new Varien_Object([
                'catalog_id'    => $item->getAttribute('catalog_id'),
                'gsi_client_id' => $item->getAttribute('gsi_client_id'),
                'item_id'       => new Varien_Object(['client_item_id' => $this->extractItemId($feed, $item)]),
                'measurements'  => new Varien_Object(['available_quantity' => $this->extractMeasurements($feed, $item)]),
            ]);
        }
        return $collectionOfItems;
    }
}
