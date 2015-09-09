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

class EbayEnterprise_Swatch_Helper_Data extends Mage_Core_Helper_Abstract implements EbayEnterprise_Eb2cCore_Helper_Interface
{
    const LOCK_EXPIRED_TIME = 30;

    /** @var Mage_Core_Model_Session */
    protected $session;

    /**
     * @see EbayEnterprise_Eb2cCore_Helper_Interface::getConfigModel
     * @param  mixed
     * @return EbayEnterprise_Eb2cCore_Model_Config_Registry
     */
    public function getConfigModel($store = null)
    {
        return Mage::getModel('eb2ccore/config_registry')
            ->setStore($store)
            ->addConfigModel(Mage::getSingleton('ebayenterprise_swatch/config'));
    }

    /**
     * Stash core/session instance in a protected class property
     * if it hasn't been stashed before and return the instance.
     *
     * @return Mage_Core_Model_Session
     */
    protected function getCoreSession()
    {
        if (!$this->session) {
            $this->session = Mage::getSingleton('core/session');
        }
        return $this->session;
    }

    /**
     * Determine if a product is locked based on how long it existed in
     * session.
     *
     * @param  Mage_Catalog_Model_Product
     * @return bool
     */
    public function isLocked(Mage_Catalog_Model_Product $product)
    {
        /** @var int */
        $productId = $product->getId();
        /** @var Mage_Core_Model_Session */
        $session = $this->getCoreSession();
        /** @var array */
        $lockedProducts = (array) $session->getLockedProducts();
        /** @var DateTime | null */
        $lockedTime = isset($lockedProducts[$productId]) ? $lockedProducts[$productId] : null;
        if ($this->isExpired($lockedTime)) {
            $this->updateLock($session, $lockedProducts, $productId);
            return false;
        }
        return true;
    }

    /**
     * Return true if the elapsed time between now and the locked time
     * is greater than 30 minutes or when a null lock time value is passed in;
     * otherwise, return false.
     *
     * @param  DateTime
     * @return bool
     */
    protected function isExpired(DateTime $lockedTime=null)
    {
        return !$lockedTime || ($this->getLockedTimeInMinutes($lockedTime) > static::LOCK_EXPIRED_TIME);
    }

    /**
     * Update the lock expired date with the current date and time
     *
     * @param  Mage_Core_Model_Session
     * @param  array
     * @param  int
     * @return self
     */
    protected function updateLock(Mage_Core_Model_Session $session, array $lockedProducts, $productId)
    {
        $lockedProducts[$productId] = new DateTime();
        $session->setLockedProducts($lockedProducts);
        return $this;
    }

    /**
     * Return the elapsed time in minutes between now and the passed in locked time.
     *
     * @param  DateTime
     * @return int
     */
    protected function getLockedTimeInMinutes(DateTime $lockedTime)
    {
        /** @var DateInterval */
        $interVal = $lockedTime->diff(new DateTime());
        return (
            ($interVal->y * 365 * 24 * 60) +
            ($interVal->m * 30 * 24 * 60) +
            ($interVal->d * 24 * 60) +
            ($interVal->h * 60) +
            $interVal->i
        );
    }
}
