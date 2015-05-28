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

class EbayEnterprise_Order_Model_Detail_Process_Response_Shipment
	extends Mage_Sales_Model_Order_Shipment
{
	/** @var EbayEnterprise_Order_Model_Detail_Process_Response_Address */
	protected $_shippingAddress;

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
		// remove the order key, we no long need it.
		$this->unsetData('order');
		if (trim($this->getIncrementId())) {
			$items = $this->getOrder()->getItemsCollection();
			foreach ($this->getShippedItemIds() as $itemRefId) {
				$item = $items->getItemByColumnValue('ref_id', $itemRefId);
				if ($item && $item->getSku()) {
					$this->_injectShipmentItems(array_merge($item->getData(), ['qty' => $item->getQtyShipped()]));
				}
			}
			$tracks = $this->getTracks();
			if (!empty($tracks)) {
				foreach ($tracks as $track) {
					$this->_injectShipmentTracks(['number' => $track]);
				}
			}
			$this->setAllItems($this->getItems());
		}
	}

	/**
	 * injecting shipment data into order shipment item collection
	 * @param array $data
	 * @return self
	 */
	protected function _injectShipmentItems(array $data)
	{
		if (!$this->_items) {
			$this->_items = new Varien_Data_Collection();
		}
		$this->_items->addItem(Mage::getModel('sales/order_shipment_item', $data));

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
		$this->_tracks->addItem(Mage::getModel('sales/order_shipment_track', $data));

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
		return $this->getShippingAddress()->getChargeType();
	}
}
