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

class EbayEnterprise_Order_Model_Detail_Process_Response_Shipment extends Mage_Sales_Model_Order_Shipment
{
    /** @var EbayEnterprise_Order_Model_Detail_Process_Response_Address */
    protected $_shippingAddress;
    /** @var EbayEnterprise_Order_Helper_Data */
    protected $orderHelper;
    /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
    protected $coreConfig;
    /** @var Mage_Shipping_Model_Shipping */
    protected $shipping;
    /** @var EbayEnterprise_Order_Helper_Factory */
    protected $factory;
    /** @var EbayEnterprise_Order_Helper_Detail_Item */
    protected $itemHelper;

    /**
     * @param array $initParams Must have this key:
     *                          - 'response' => IOrderDetailResponse
     */
    public function __construct(array $initParams=[])
    {
        list($this->orderHelper, $this->coreConfig, $this->shipping, $this->factory, $this->itemHelper) = $this->checkTypes(
            $this->nullCoalesce($initParams, 'order_helper', Mage::helper('ebayenterprise_order')),
            $this->nullCoalesce($initParams, 'core_config', Mage::helper('eb2ccore')->getConfigModel()),
            $this->nullCoalesce($initParams, 'shipping', Mage::getModel('shipping/shipping')),
            $this->nullCoalesce($initParams, 'factory', Mage::helper('ebayenterprise_order/factory')),
            $this->nullCoalesce($initParams, 'item_helper', Mage::helper('ebayenterprise_order/detail_item'))
        );
        parent::__construct($this->removeKnownKeys($initParams));
    }

    /**
     * Populate a new array with keys that not in the array of known keys.
     *
     * @param  array
     * @return array
     */
    protected function removeKnownKeys(array $initParams)
    {
        $newParams = [];
        $knownKeys = ['order_helper', 'core_config', 'shipping', 'factory', 'item_helper'];
        foreach ($initParams as $key => $value) {
            if (!in_array($key, $knownKeys)) {
                $newParams[$key] = $value;
            }
        }
        return $newParams;
    }

    /**
     * Type hinting for self::__construct $initParams
     *
     * @param  EbayEnterprise_Order_Helper_Data
     * @param  EbayEnterprise_Eb2cCore_Model_Config_Registry
     * @param  Mage_Shipping_Model_Shipping
     * @param  EbayEnterprise_Order_Helper_Factory
     * @param  EbayEnterprise_Order_Helper_Detail_Item
     * @return array
     */
    protected function checkTypes(
        EbayEnterprise_Order_Helper_Data $orderHelper,
        EbayEnterprise_Eb2cCore_Model_Config_Registry $coreConfig,
        Mage_Shipping_Model_Shipping $shipping,
        EbayEnterprise_Order_Helper_Factory $factory,
        EbayEnterprise_Order_Helper_Detail_Item $itemHelper
    ) {
        return func_get_args();
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the default value.
     *
     * @param  array
     * @param  string $field Valid array key
     * @param  mixed
     * @return mixed
     */
    protected function nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }

    /**
     * @see Varien_Object::_construct()
     * overriding in order to implement order shipment
     * business logic to replace Magento data with
     * OMS order detail data.
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        // @see parent::_order property.
        $this->setOrder($this->getData('order'));
        // templates and blocks expect something from the getId method, so set the id to
        // the increment id to ensure output is generated.
        $this->setId($this->getIncrementId());
        if (trim($this->getIncrementId())) {
            $items = $this->getOrder()->getItemsCollection();
            foreach ($this->getShippedItemIds() as $itemRefId) {
                $item = $items->getItemByColumnValue('ref_id', $itemRefId);
                if ($item && $item->getSku()) {
                    $data = array_merge($item->getData(), ['qty' => $item->getQtyShipped(), 'order_item_id' => $item->getId()]);
                    $this->_injectShipmentItems($data, $item);
                }
            }
            $tracks = $this->getTracks();
            if (!empty($tracks)) {
                foreach ($tracks as $track) {
                    $this->_injectShipmentTracks(['number' => $track]);
                }
            }
        }
    }

    /**
     * injecting shipment data into order shipment item collection
     * @param  array
     * @param  Mage_Sales_Model_Order_Item
     * @return self
     */
    protected function _injectShipmentItems(array $data, Mage_Sales_Model_Order_Item $item)
    {
        if (!$this->_items) {
            $this->_items = new Varien_Data_Collection();
        }
        $shipmentItem = $this->factory->getNewSalesOrderShipmentItem($data);
        $shipmentItem->setOrderItem($item)
            ->setShipment($this);
        $this->_items->addItem($shipmentItem);

        return $this;
    }

    /**
     * injecting shipment data into order shipment tracking collection
     * @param array $data
     * @return self
     */
    protected function _injectShipmentTracks(array $data)
    {
        if (!$this->_tracks) {
            $this->_tracks = new Varien_Data_Collection();
        }
        $this->_tracks->addItem($this->factory->getNewSalesOrderShipmentTrack($data));

        return $this;
    }

    /**.
	 * Get the shipping method by the shipment address id and then stash it
	 * to the class property self::_shippingAddress
	 *
	 * @return EbayEnterprise_Order_Model_Detail_Process_Response_Address | null
	 */
    public function getShippingAddress()
    {
        if (!$this->_shippingAddress) {
            $this->_shippingAddress = $this->_order->getAddressesCollection()->getItemById($this->getAddressId());
        }
        return $this->_shippingAddress;
    }

    /**
     * Get the shipping method.
     *
     * @return string | null
     */
    public function getShippingDescription()
    {
        /** @var string */
        $carrier = $this->getCarrier();
        /** @var string */
        $mode = $this->getCarrierMode();
        /** @var array */
        $shipmap = is_array($this->coreConfig->shippingMethodMap)
            ? array_flip($this->coreConfig->shippingMethodMap): [];
        /** @var string */
        $romKey = sprintf('%s_%s', $carrier, $mode);

        return isset($shipmap[$romKey])
            ? $this->getShippingMethod($shipmap[$romKey])
            : $this->orderHelper->__('%s %s', $carrier, $mode);
    }

    /**
     * Get the shipping method using the shipping code.
     *
     * @param  string
     * @return string | null
     */
    protected function getShippingMethod($shipping)
    {
        $data = array_filter(explode('_', $shipping));
        if (count($data) > 1) {
            /** @var string */
            $shippingCode = $data[0];
            /** @var string */
            $shippingMethodCode = $data[1];
            /** @var Mage_Shipping_Model_Carrier_Abstract | false */
            $carrier = $this->shipping->getCarrierByCode($shippingCode);
            return $carrier
                ? sprintf('%s - %s', $carrier->getConfigData('title'), $this->getShippingMethodName($carrier, $shippingMethodCode)) : null;
        }
        return null;
    }

    /**
     * Get the shipping method name using the passed in shipping method code
     *
     * @param  Mage_Shipping_Model_Carrier_Abstract
     * @param  string
     * @return string | null
     */
    protected function getShippingMethodName(Mage_Shipping_Model_Carrier_Abstract $carrier, $shippingMethodCode)
    {
        /** @var array */
        $shippingMethods = $carrier->getAllowedMethods();
        return isset($shippingMethods[$shippingMethodCode])
            ? $shippingMethods[$shippingMethodCode] : null;
    }

    /**
     * Get the shipping carrier.
     *
     * @return string | null
     */
    public function getShippingCarrierTitle()
    {
        /** @var string */
        $description = trim($this->getShippingDescription());
        return strstr($description, ' - ', true) ?: strstr($description, ' ', true);
    }

    /**
     * Get all items in the shipment. Hidden items will be excluded by default
     * but can be included using the `$includeHidden` argument.
     *
     * @param bool
     * @return Mage_Sales_Model_Order_Item[]
     */
    public function getAllItems($includeHidden = false)
    {
        // parent::getAllItems will include hidden and non-hidden items by default.
        // When hidden items are to be included, the whole array can simply be
        // returned. When hidden items should not be included, they need to
        // be filtered out of the list.
        $items = parent::getAllItems();
        return $includeHidden ? $items : $this->itemHelper->filterHiddenGiftItems($items);
    }
}
