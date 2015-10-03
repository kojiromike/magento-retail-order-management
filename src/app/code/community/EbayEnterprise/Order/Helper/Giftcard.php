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

use eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderDestinationIterable;
use eBayEnterprise\RetailOrderManagement\Payload\Order\IEmailAddressDestination;
use eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderCreateRequest;
use eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderItemReference;
use eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderItemReferenceIterable;
use eBayEnterprise\RetailOrderManagement\Payload\Order\IShipGroup;
use eBayEnterprise\RetailOrderManagement\Payload\Order\IShipGroupIterable;
use eBayEnterprise\RetailOrderManagement\Payload\Order\IPriceGroup;
use Psr\Log\LoggerInterface;

class EbayEnterprise_Order_Helper_Giftcard
{
    /** @var LoggerInterface */
    protected $logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $logContext;

    public function __construct(array $init = [])
    {
        list(
            $this->logger,
            $this->logContext
        ) = $this->checkTypes(
            $this->nullCoalesce($init, 'logger', Mage::helper('ebayenterprise_magelog')),
            $this->nullCoalesce($init, 'logContext', Mage::helper('ebayenterprise_magelog/context'))
        );
    }

    /**
     * Type checks for constructor args array.
     *
     * @param LoggerInterface
     * @param EbayEnterprise_MageLog_Helper_Context
     * @return array
     */
    protected function checkTypes(
        LoggerInterface $logger,
        EbayEnterprise_MageLog_Helper_Context $logContext
    ) {
        return func_get_args();
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the
     * default value.
     * @param array      $arr
     * @param string|int $field Valid array key
     * @param mixed      $default
     * @return mixed
     */
    protected function nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }

    /**
     * Removes any virtual gift card items from the order create request and
     * re-adds them as parts of ship groups with destinations using the recipient
     * email address as the destination.
     *
     * @param Mage_Sales_Model_Order
     * @param IOrderCreateRequest
     * @return self
     */
    public function fixOrderCreateVirtualGiftCards(
        Mage_Sales_Model_Order $order,
        IOrderCreateRequest $orderCreateRequest
    ) {
        $virtualGiftCards = $this->getOrderedVirtualGiftCards($order);
        if ($virtualGiftCards) {

            $this->logger->debug('Order contains {count} virtual gift cards', $this->logContext->getMetaData(__CLASS__, ['count' => count($virtualGiftCards)]));

            $shipGroups = $orderCreateRequest->getShipGroups();
            $destinations = $orderCreateRequest->getDestinations();

            // Assume virtual gift cards were added to the wrong ship group
            // with the wrong destination. Remove them from where they are to
            // re-add them in the right place.
            $removedItemPayloads = $this->removeVirtualGiftCardPayloads($shipGroups, $virtualGiftCards);

            $this->logger->debug('Removed {count} virtual gift card item payloads', $this->logContext->getMetaData(__CLASS__, ['count' => count($removedItemPayloads)]));

            // Each of the removed items needs to be added back into the OCR
            // as part of a ship group with the recipient's email address as the
            // destination of the ship group.
            foreach ($removedItemPayloads as $itemRefPair) {
                $shipGroup = $this->createShipGroupForVirtualGiftCard($itemRefPair, $shipGroups, $destinations);
                $shipGroups->offsetSet($shipGroup);
            }

            // After pulling virtual gift cards out of existing ship groups, make
            // sure the OCR isn't left with any empty ship groups.
            $this->removeEmptyShipGroups($shipGroups, $destinations);

            $orderCreateRequest->setShipGroups($shipGroups);
        } else {
            $this->logger->debug('Order does not contain any virtual gift cards', $this->logContext->getMetaData(__CLASS__));
        }

        return$this;
    }

    /**
     * Get all virtual gift card items from the collection of items in the
     * Magento order.
     *
     * @param Mage_Sales_Model_Order
     * @return Mage_Sales_Model_Order_Item[]
     */
    protected function getOrderedVirtualGiftCards(Mage_Sales_Model_Order $order)
    {
        $virtualGiftCards = [];
        foreach ($order->getItemsCollection() as $item) {
            if ($item->getProductType() === 'giftcard' && $item->getIsVirtual()) {
                $virtualGiftCards[] = $item;
            }
        }
        return $virtualGiftCards;
    }

    /**
     * Remove item references for virtual gift cards from the ship groups in
     * the iterable. Returns any item references removed.
     *
     * @param IShipGroupIterable
     * @param Mage_Sales_Model_Order_Item[]
     * @return array
     */
    protected function removeVirtualGiftCardPayloads(IShipGroupIterable $shipGroups, array $virtualGiftCards)
    {
        // Will be nested array of groups of item refs removed from each ship group.
        $groupedRemovedRefs = [];

        // Go through ship groups, looking for any OGCs
        foreach ($shipGroups as $shipGroup) {
            // OGCs are virtual items so they will already only be associated with virtual
            // destinations - e.g. email address destinations.
            if ($shipGroup->getDestination() instanceof IEmailAddressDestination) {

                $virtualGiftCardItemRefs = $this->getVirtualGiftCardItemReferences($shipGroup->getItemReferences(), $virtualGiftCards);

                $this->logger->debug('Found {count} virtual gift card payloads in order create request', $this->logContext->getMetaData(__CLASS__, ['count' => count($virtualGiftCardItemRefs)]));

                // Replace the item references in the ship group with an item
                // reference iterable containing only the non-virtual gift card
                // items from the ship group.
                $shipGroup->setItemReferences(
                    $this->removeItemReferences($shipGroup->getItemReferences(), $virtualGiftCardItemRefs)
                );

                // If the virtual gift card was the first item in a ship group,
                // it was the source for shipping prices for the ship group. If
                // the ship group sill has other items, the removed shipping price
                // group needs to be moved to a remaining item in the ship group.
                $this->reassignShippingPriceGroup($virtualGiftCardItemRefs, $shipGroup->getItemReferences());

                // Capture any item refs removed from the ship group, these
                // will need to be added back into the OCR in new ship groups.
                $groupedRemovedRefs[] = $virtualGiftCardItemRefs;
            }
        }

        // groupedRemovedRefs contains item refs grouped by the ship group they belonged to,
        // so each element in the array is an array of item refs. The groupings
        // don't matter, so flatten down the array before dealing with it any further.
        $itemRefs = $this->flattenArray($groupedRemovedRefs);
        return $this->createItemReferencePairs($itemRefs, $virtualGiftCards);
    }

    /**
     * Create an email address destination for the virtual gift card, using the
     * gift card recipient information for the destination data.
     *
     * @param EbayEnterprise_Order_Model_Create_Itemreferencepair
     * @param IShipGroupIterable
     * @param IOrderDestinationIterable
     * @return IShipGroup
     */
    protected function createShipGroupForVirtualGiftCard(
        EbayEnterprise_Order_Model_Create_Itemreferencepair $itemRefPair,
        IShipGroupIterable $shipGroups,
        IOrderDestinationIterable $destinations
    ) {
        $orderItem = $itemRefPair->getItem();
        $itemRefPayload = $itemRefPair->getPayload();
        $shipGroupPayload = $shipGroups->getEmptyShipGroup();
        $destination = $destinations->getEmptyEmailAddress();

        $destination->setEmailAddress($orderItem->getBuyRequest()->getGiftcardRecipientEmail());

        $shipGroupPayload->getItemReferences()->offsetSet($itemRefPayload);
        $shipGroupPayload->setDestination($destination)
            ->setChargeType(EbayEnterprise_Order_Model_Create::SHIPPING_CHARGE_TYPE_FLATRATE);

        // As each virtual gift card will be in its own ship group, it likely needs
        // its own shipping price group for the OMS, even though the "shipping" cost is 0.
        // Add a new price groups for shipping with amount of 0.00 so the item &
        // ship group will have one.
        $itemPayload = $itemRefPayload->getReferencedItem();
        $itemPayload->setShippingPricing($itemPayload->getEmptyPriceGroup()->setAmount(0.00));

        $this->logger->debug('Created ship group for virtual gift card', $this->logContext->getMetaData(__CLASS__, ['email' => $destination->getEmailAddress(), 'sku' => $orderItem->getSku(), 'line_number' => $orderItem->getLineNumber()]));

        return $shipGroupPayload;
    }

    /**
     * Remove any ship groups that no longer contain items.
     *
     * @param IShipGroupIterable
     * @return IShipGroupIterable
     */
    protected function removeEmptyShipGroups(IShipGroupIterable $shipGroups, IOrderDestinationIterable $destinations)
    {
        // Can't loop over the iterable and modify it at the same time.

        // Collect items that need to be removed from the iterable but don't
        // modify the iterable.
        $emptyShipGroups = [];
        foreach ($shipGroups as $shipGroup) {
            if ($shipGroup->getItemReferences()->count() === 0) {
                $emptyShipGroups[] = $shipGroup;
            }
        }

        // Go through the items that need to be removed and remove each from
        // the iterable.
        foreach ($emptyShipGroups as $removeShipGroup) {

            // Destinations are stored separate from the ship group. Any destinations
            // for empty ship groups are no longer necessary so remove them with
            // the rest of the ship group.
            $destinations->offsetUnset($removeShipGroup->getDestination());

            $shipGroups->offsetUnset($removeShipGroup);
        }

        return $shipGroups;
    }

    /**
     * If a shipping price group was removed from an item referenced by a ship
     * group, the shipping price group needs to be moved to a remaining item
     * in the ship group, if there is one.
     *
     * @param IOrderItemReference[]
     * @param IOrderItemReferenceIterable
     * @return self
     */
    protected function reassignShippingPriceGroup(array $removedItemRefs, IOrderItemReferenceIterable $shipGroupItemRefs)
    {
        // If there are no items left in the ship group, no item to move shipping
        // amounts to, so no need to do anything.
        if ($shipGroupItemRefs->count() === 0) {
            return $this;
        }

        $removedShippingPriceGroup = $this->removeShippingPriceGroupFromItems($removedItemRefs);
        if ($removedShippingPriceGroup) {
            $shipGroupItemRefs[0]->getReferencedItem()->setShippingPricing($removedShippingPriceGroup);
        }

        return $this;
    }

    /**
     * Find the shipping price group for a collection of item references.
     *
     * @param IOrderItemReference[]
     * @return IPriceGroup|null
     */
    protected function removeShippingPriceGroupFromItems(array $itemRefs)
    {
        $priceGroup = null;
        foreach ($itemRefs as $itemRef) {
            $item = $itemRef->getReferencedItem();
            $priceGroup = $item->getShippingPricing();
            if ($priceGroup) {
                $item->setShippingPricing(null);
                break;
            }
        }
        return $priceGroup;
    }

    /**
     * Get any item references that refer to virtual gift card items.
     *
     * @param IOrderItemReferenceIterable
     * @param array
     * @return IOrderItemReference[]
     */
    protected function getVirtualGiftCardItemReferences(IOrderItemReferenceIterable $itemRefs, array $virtualGiftCards)
    {
        return $this->mapItemReferenceToItem(
            function (IOrderItemReference $itemRef) {
                return $itemRef;
            },
            $itemRefs,
            $virtualGiftCards
        );
    }

    /**
     * For each item ref, create an item ref pair associating the item ref
     * payload to the Magento item it references.
     *
     * @param IOrderItemReference[]
     * @param Mage_Sales_Model_Order_Item[]
     * @return EbayEnterprise_Order_Model_Create_Itemreferencepair[]
     */
    protected function createItemReferencePairs(array $itemRefs, array $virtualGiftCards)
    {
        return $this->mapItemReferenceToItem(
            function (IOrderItemReference $itemRef, Mage_Sales_Model_Order_Item $item) {
                return $this->createItemReferencePair($item, $itemRef);
            },
            $itemRefs,
            $virtualGiftCards
        );
    }

    /**
     * Map an item reference to a Magento item and invoke the callback.
     *
     * @param Callable
     * @param IOrderItemReferenceIterable|IOrderItemReference[]
     * @param Mage_Sales_Model_Order_Item[]
     * @return array
     */
    protected function mapItemReferenceToItem(Callable $cb, $itemRefs, array $virtualGiftCards)
    {
        $itemIndex = $this->createItemIndex($virtualGiftCards, 'line_number');
        $res = [];
        foreach ($itemRefs as $itemRef) {
            $lineNumber = $itemRef->getReferencedItem()->getLineNumber();
            if (isset($itemIndex[$lineNumber])) {
                $res[] = call_user_func($cb, $itemRef, $itemIndex[$lineNumber]);
            }
        }
        return $res;
    }

    /**
     * Remove each of the item references in the array from the iterable.
     *
     * @param IOrderItemReferenceIterable
     * @param IOrderItemReference[]
     * @return IOrderItemReferenceIterable
     */
    protected function removeItemReferences(IOrderItemReferenceIterable $itemReferences, array $toRemove)
    {
        foreach ($toRemove as $removeRef) {
            $itemReferences->offsetUnset($removeRef);
        }
        return $itemReferences;
    }

    /**
     * Factory method for item reference pairs.
     *
     * @param Mage_Sales_Model_Order_Item
     * @param IOrderItemReference
     * @return EbayEnterprise_Order_Model_Create_Itemreferencepair
     */
    protected function createItemReferencePair(Mage_Sales_Model_Order_Item $item, IOrderItemReference $payload)
    {
        return Mage::getModel('ebayenterprise_order/create_itemreferencepair', ['item' => $item, 'payload' => $payload]);
    }

    /**
     * Create an index of the items by the value at the specified field. Field
     * values must be scalar values. Any item with a non-scalar value at the
     * field will be excluded from the index.
     *
     * @param Varien_Object[]
     * @param string
     * @return Varien_Object[]
     */
    protected function createItemIndex(array $items, $indexField)
    {
        $index = [];
        foreach ($items as $item) {
            $indexValue = $item->getData($indexField);
            if (is_scalar($indexValue)) {
                $index[$indexValue] = $item;
            }
        }
        return $index;
    }

    /**
     * Flatten a single level of array nesting, so [[1,2,3], [4,5,6]] becomes
     * [1,2,3,4,5,6]
     *
     * @param array
     * @return array
     */
    protected function flattenArray($arr = [])
    {
        return $arr ? call_user_func_array('array_merge', $arr) : [];
    }
}
