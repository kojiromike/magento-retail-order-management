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

use eBayEnterprise\RetailOrderManagement\Payload\Checkout\IPhysicalAddress;

/**
 * Class EbayEnterprise_Address_Helper_Data
 *
 * Methods for converting addresses represented in XML to Magento address model objects.
 */
class EbayEnterprise_Address_Helper_Data extends Mage_Core_Helper_Abstract implements EbayEnterprise_Eb2cCore_Helper_Interface
{
    /**
     * Get the address validation config model
     *
     * @see EbayEnterprise_Eb2cCore_Model_Config_Registry::addConfigModel
     * @param bool|int|Mage_Core_Model_Store|null|string $store Set the config model to use this store
     * @return EbayEnterprise_Eb2cCore_Model_Config_Registry
     */
    public function getConfigModel($store = null)
    {
        return Mage::getModel('eb2ccore/config_registry')
            ->setStore($store)
            ->addConfigModel(Mage::getSingleton('ebayenterprise_address/config'));
    }

    /**
     * Transfer data from a Magento address model to a physical address payload.
     *
     * @param Mage_Customer_Model_Address_Abstract
     * @param IPhysicalAddress
     * @return self
     */
    public function transferAddressToPhysicalAddressPayload(
        Mage_Customer_Model_Address_Abstract $address,
        IPhysicalAddress $addressPayload
    ) {
        $addressPayload
            ->setLines($address->getStreetFull())
            ->setCity($address->getCity())
            ->setMainDivision($this->getRegion($address))
            ->setCountryCode($address->getCountry())
            ->setPostalCode($address->getPostcode());
        return $this;
    }

    /**
     * If the country for the Address is US then get the 2 character ISO region code;
     * otherwise, for any other country get the fully qualified region name.
     *
     * @param  Mage_Customer_Model_Address_Abstract
     * @return string
     */
    protected function getRegion(Mage_Customer_Model_Address_Abstract $address)
    {
        return $address->getCountry() === 'US'
            ? $address->getRegionCode()
            : $address->getRegion();
    }

    /**
     * Transfer data from a physical address payload to a Magento address model.
     *
     * @param IPhysicalAddress
     * @param Mage_Customer_Model_Address_Abstract
     * @return self
     */
    public function transferPhysicalAddressPayloadToAddress(
        IPhysicalAddress $addressPayload,
        Mage_Customer_Model_Address_Abstract $address
    ) {
        /** @var string */
        $region = $addressPayload->getMainDivision();
        $address
            ->setStreet($addressPayload->getLines())
            ->setCity($addressPayload->getCity())
            ->setCountryId($addressPayload->getCountryCode())
            ->setRegionId($this->getRegionIdByCode($region, $addressPayload->getCountryCode()))
            ->setRegion($region)
            ->setPostcode($addressPayload->getPostalCode());
        return $this;
    }

    /**
     * Get the region id for a region code.
     *
     * @param string
     * @param string
     * @return string
     */
    public function getRegionIdByCode($regionCode, $countryCode)
    {
        return (int) Mage::getModel('directory/region')
            ->loadByCode($regionCode, $countryCode)
            ->getId();
    }
}
