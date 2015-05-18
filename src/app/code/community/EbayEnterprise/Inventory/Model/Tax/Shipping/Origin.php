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

class EbayEnterprise_Inventory_Model_Tax_Shipping_Origin
{
    /** @var EbayEnterprise_Inventory_Model_Details_Service */
    protected $detailsService;
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $logContext;

    /**
     * @param array $args May contain:
     *                    - details_service => EbayEnterprise_Inventory_Model_Details_Service
     *                    - logger => EbayEnterprise_MageLog_Helper_Data
     *                    - log_context => EbayEnterprise_MageLog_Helper_Context
     */
    public function __construct(array $args = [])
    {
        list(
            $this->detailsService,
            $this->logger,
            $this->logContext
        ) = $this->checkTypes(
            $this->nullCoalesce($args, 'details_service', Mage::getModel('ebayenterprise_inventory/details_service')),
            $this->nullCoalesce($args, 'logger', Mage::helper('ebayenterprise_magelog')),
            $this->nullCoalesce($args, 'log_context', Mage::helper('ebayenterprise_magelog/context'))
        );
    }

    /**
     * Enforce type checks on constructor init params.
     *
     * @param EbayEnterprise_Inventory_Model_Details_Service
     * @param EbayEnterprise_MageLog_Helper_Data
     * @param EbayEnterprise_MageLog_Helper_Context
     * @return array
     */
    protected function checkTypes(
        EbayEnterprise_Inventory_Model_Details_Service $detailsService,
        EbayEnterprise_MageLog_Helper_Data $logger,
        EbayEnterprise_MageLog_Helper_Context $logContext
    ) {
        return func_get_args();
    }

    /**
     * Fill in default values.
     *
     * @param string
     * @param array
     * @param mixed
     * @return mixed
     */
    protected function nullCoalesce(array $arr, $key, $default)
    {
        return isset($arr[$key]) ? $arr[$key] : $default;
    }

    public function injectShippingOriginForItem(
        Mage_Sales_Model_Quote_Item_Abstract $item,
        Mage_Customer_Model_Address_Abstract $address
    ) {
        $detail = $this->detailsService->getDetailsForItem($item);
        if (!$detail) {
            // no point hanging around if there is no result for the item
            $this->logger->debug(
                'Details for item "{sku}" [{item_id}] not found.',
                $this->logContext->getMetaData(__CLASS__, ['sku' => $item->getSku(), 'item_id' => $item->getId()])
            );
            return;
        }
        $this->logger->debug(
            'found details for item "{sku}" [{item_id}]',
            $this->logContext->getMetaData(__CLASS__, ['sku' => $item->getSku(), 'item_id' => $item->getId()])
        );
        $this->copyShipFromAddressTo($address, $detail);
    }

    protected function copyShipFromAddressTo(
        Mage_Customer_Model_Address_Abstract $address,
        EbayEnterprise_Inventory_Model_Details_Item $detail
    ) {
        if ($detail->isAvailable()) {
            $meta = ['sku' => $detail->getSku(), 'item_id' => $detail->getItemId()];
            $this->logger->debug(
                'applying details for item "{sku}" [{item_id}]',
                $this->logContext->getMetaData(__CLASS__, $meta)
            );
            $address->addData($this->exportShipFromAddress($detail));
        }
    }

    protected function exportShipFromAddress($detail)
    {
        return [
            'street' => $detail->getShipFromLines(),
            'city' => $detail->getShipFromCity(),
            'region_code' => $detail->getShipFromMainDivision(),
            'country_id' => $detail->getShipFromCountryCode(),
            'postcode' => $detail->getShipFromPostalCode(),
        ];
    }
}
