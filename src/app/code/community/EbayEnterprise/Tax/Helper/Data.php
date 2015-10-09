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

class EbayEnterprise_Tax_Helper_Data extends Mage_Core_Helper_Abstract implements EbayEnterprise_Eb2cCore_Helper_Interface
{
    /** @var EbayEnterprise_Tax_Model_Collector */
    protected $taxCollector;
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $logContext;

    /**
     * @param array $args May contain key/value for:
     * - tax_collector => EbayEnterprise_Tax_Model_Collector
     * - logger => EbayEnterprise_MageLog_Helper_Data
     * - log_context => EbayEnterprise_MageLog_Helper_Context
     */
    public function __construct(array $args = [])
    {
        list(
            $this->taxCollector,
            $this->logger,
            $this->logContext
        ) = $this->checkTypes(
            $this->nullCoalesce($args, 'tax_collector', Mage::getModel('ebayenterprise_tax/collector')),
            $this->nullCoalesce($args, 'logger', Mage::helper('ebayenterprise_magelog')),
            $this->nullCoalesce($args, 'log_context', Mage::helper('ebayenterprise_magelog/context'))
        );
    }

    /**
     * Enforce type checks on constructor init params.
     *
     * @param EbayEnterprise_Tax_Helper_Data
     * @param EbayEnterprise_Tax_Model_Collector
     * @param EbayEnterprise_MageLog_Helper_Data
     * @param EbayEnterprise_MageLog_Helper_Context
     * @return array
     */
    protected function checkTypes(
        EbayEnterprise_Tax_Model_Collector $taxCollector,
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

    /**
     * @see EbayEnterprise_Eb2cCore_Helper_Interface::getConfigModel
     * @param mixed
     * @return EbayEnterprise_Eb2cCore_Model_Config_Registry
     */
    public function getConfigModel($store = null)
    {
        return Mage::getModel('eb2ccore/config_registry')
            ->setStore($store)
            ->addConfigModel(Mage::getSingleton('ebayenterprise_tax/config'));
    }

    /**
     * Get the HTS code for a product in a given country.
     *
     * @param Mage_Catalog_Model_Product
     * @param string $countryCode The two letter code for a country (US, CA, DE, etc...)
     * @return string|null The HTS Code for the product/country combination. Null if no HTS code is available.
     */
    public function getProductHtsCodeByCountry(Mage_Catalog_Model_Product $product, $countryCode)
    {
        $htsCodes = unserialize($product->getHtsCodes());
        if (is_array($htsCodes)) {
            foreach ($htsCodes as $htsCode) {
                if ($countryCode === $htsCode['destination_country']) {
                    return $htsCode['hts_code'];
                }
            }
        }

        return null;
    }

    /**
     * Return a triple of tax, duty and fee totals for one or all addresses.
     *
     * @param int|null $addressId If set, return the sum just for this address. Otherwise sum for all addresses in order.
     * @return float[] of size three
     */
    public function getTaxDutyFeeTotals($addressId = null)
    {
        $collector = $this->taxCollector;
        if (is_null($addressId)) {
            $taxRecords = $collector->getTaxRecords();
            $dutyRecords = $collector->getTaxDuties();
            $feeRecords = $collector->getTaxFees();
        } else {
            $taxRecords = $collector->getTaxRecordsByAddressId($addressId);
            $dutyRecords = $collector->getTaxDutiesByAddressId($addressId);
            $feeRecords = $collector->getTaxFeesByAddressId($addressId);
        }
        $taxes = array_sum(array_map(function ($record) { return $record->getCalculatedTax(); }, $taxRecords));
        $duties = array_sum(array_map(function ($record) { return $record->getAmount(); }, $dutyRecords));
        $fees = array_sum(array_map(function ($record) { return $record->getAmount(); }, $feeRecords));
        return [$taxes, $duties, $fees];
    }
}
