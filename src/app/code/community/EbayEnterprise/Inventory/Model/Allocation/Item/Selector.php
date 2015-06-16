<?php

class EbayEnterprise_Inventory_Model_Allocation_Item_Selector
{
    /** @var array */
    protected $data = [];
    /** @var EbayEnterprise_Inventory_Item_Selection_Interface */
    protected $selectionHelper;

    public function __construct($init = [])
    {
        list(
            $this->quote,
            $this->selectionHelper
        ) = $this->checkTypes(
            $this->nullCoalesce($init, 'quote', null),
            $this->nullCoalesce(
                $init,
                'selection_helper',
                Mage::helper('ebayenterprise_inventory/item_selection')
            )
        );
        // default initialize to an empty iterator
        $this->iterator = new \ArrayIterator([]);
    }

    protected function checkTypes(
        Mage_Sales_Model_Quote $quote,
        EbayEnterprise_Inventory_Model_Item_Selection_Interface $selectionHelper
    ) {
        return func_get_args();
    }

    /**
     * Fill in default values.
     *
     * @param  array
     * @param  string
     * @param  mixed
     * @return mixed
     */
    protected function nullCoalesce(array $arr, $key, $default)
    {
        return isset($arr[$key]) ? $arr[$key] : $default;
    }

    /**
     * get an iterator for the selected items
     *
     * @return \Iterator
     * @throws EbayEnterprise_Inventory_Exception_Allocation_Collector_Exception
     *         if there is insufficient data to perform the operation
     */
    public function getSelectionIterator()
    {
        if (!$this->data) {
            $this->collectItemsForOperation($this->quote);
        }
        return new \ArrayIterator($this->data);
    }

    /**
     * collect data needed to perform an allocation operation
     *
     * @throws EbayEnterprise_Inventory_Exception_Allocation_Collector_Exception
     *         if there is insufficient data to perform the request
     */
    protected function collectItemsForOperation(Mage_Sales_Model_Quote $quote)
    {
        foreach ($this->selectAddresses($quote) as $address) {
            if (!$this->isValidPhysicalAddress($address)) {
                continue;
            }
            $items = array_map(
                $this->getAddressItemFactoryCallback($address),
                $this->selectionHelper->selectFrom($address->getAllItems())
            );
            $this->data = array_merge($this->data, $items);
        }
        if (!$this->data) {
            throw Mage::exception(
                'EbayEnterprise_Inventory_Exception_Allocation_Item_Selector',
                'No allocatable items found in the quote'
            );
        }
        return $this;
    }

    protected function getAddressItemFactoryCallback(Mage_Customer_Model_Address_Abstract $address)
    {
        return function (Mage_Sales_Model_Quote_Item_Abstract $item) use ($address) {
            return [$address, $item];
        };
    }

    /**
     * get the addresses to use for the request
     */
    protected function selectAddresses(Mage_Sales_Model_Quote $quote)
    {
        $addresses = $quote->getAllShippingAddresses();
        $addresses[] = $quote->getBillingAddress();
        return $addresses;
    }

    /**
     * Check for the item to have shipping origin data set.
     *
     * @return bool
     */
    protected function isValidPhysicalAddress(Mage_Customer_Model_Address_Abstract $address)
    {
        return $address->getStreet1()
            && $address->getCity()
            && $address->getCountryId();
    }
}
