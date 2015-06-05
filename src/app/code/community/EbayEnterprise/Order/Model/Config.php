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

class EbayEnterprise_Order_Model_Config extends EbayEnterprise_Eb2cCore_Model_Config_Abstract
{
    protected $_configPaths = [
        'api_service'                    => 'ebayenterprise_order/api/service',
        'api_create_operation'           => 'ebayenterprise_order/api/create_operation',
        'api_cancel_operation'           => 'ebayenterprise_order/api/cancel_operation',
        'api_detail_operation'           => 'ebayenterprise_order/api/detail_operation',
        'api_search_operation'           => 'ebayenterprise_order/api/search_operation',
        'api_search_service'             => 'ebayenterprise_order/api/search_service',
        'level_of_service'               => 'ebayenterprise_order/create/level_of_service',
        'order_type'                     => 'ebayenterprise_order/create/order_type',
        'gender_map'                     => 'ebayenterprise_order/create/gender_map',
        'shipping_tax_class'             => 'ebayenterprise_order/create/shipping/default_tax_class',
        'cancel_reason_map'              => 'ebayenterprise_order/cancel/reason_map',
        'map_search_response'            => 'ebayenterprise_order/map/search_response',
        'map_detail_response'            => 'ebayenterprise_order/map/detail_response',
        'map_payment_methods'            => 'ebayenterprise_order/map/payment_methods',
        'transactional_emailer'          => 'eb2ccore/email/transactional_emailer',
    ];
}
