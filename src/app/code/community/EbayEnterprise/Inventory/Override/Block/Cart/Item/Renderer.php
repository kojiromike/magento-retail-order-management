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

class EbayEnterprise_Inventory_Override_Block_Cart_Item_Renderer extends Mage_Checkout_Block_Cart_Item_Renderer
{
    /** @var EbayEnterprise_Inventory_Model_Edd */
    protected $edd;

    public function __construct(array $initParams = [])
    {
        list($this->edd) = $this->checkTypes(
            $this->nullCoalesce($initParams, 'edd', Mage::getModel('ebayenterprise_inventory/edd'))
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
        $knownKeys = ['edd'];
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
     * @param  EbayEnterprise_Inventory_Model_Edd
     * @return array
     */
    protected function checkTypes(EbayEnterprise_Inventory_Model_Edd $edd)
    {
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
     * Get an estimated delivery message for a quote item.
     *
     * @return string
     */
    public function getEddMessage()
    {
        return $this->edd->getEddMessage($this->getItem());
    }
}
