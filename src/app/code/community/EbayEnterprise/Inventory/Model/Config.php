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

class EbayEnterprise_Inventory_Model_Config extends EbayEnterprise_Eb2cCore_Model_Config_Abstract
{
    const DEFAULT_UNAVAILABLE_ITEM_HANDLER_KEY = 'default';

    protected $_configPaths = [
        'api_service' => 'ebayenterprise_inventory/api/service',
        'quantity_api_operation' => 'ebayenterprise_inventory/quantity/operation',
        'quantity_cache_lifetime' => 'ebayenterprise_inventory/quantity/inventory_expiration',
        'api_details_operation' => 'ebayenterprise_inventory/details/operation',
        'estimated_delivery_template' => 'ebayenterprise_inventory/details/estimated_delivery_template',
        'api_allocation_create_operation' => 'ebayenterprise_inventory/allocation/create_operation',
        'api_allocation_delete_operation' => 'ebayenterprise_inventory/allocation/delete_operation',
        'unavailable_item_handlers' => 'ebayenterprise_inventory/quantity/unavailable_item_handlers',
    ];
}
