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

class EbayEnterprise_Eb2cOrder_Model_Config extends EbayEnterprise_Eb2cCore_Model_Config_Abstract
{
	protected $_configPaths = array(
		'api_cancel_dom_root_node_name'  => 'eb2corder/api/cancel_dom_root_node_name',
		'api_cancel_operation'           => 'eb2corder/api/cancel_operation',
		'api_create_dom_root_node_name'  => 'eb2corder/api/create_dom_root_node_name',
		'api_create_operation'           => 'eb2corder/api/create_operation',
		'api_detail_operation'           => 'eb2corder/api/detail_operation',
		'api_level_of_service'           => 'eb2corder/api/level_of_service',
		'api_order_history_path'         => 'eb2corder/api/order_history_path',
		'api_order_type'                 => 'eb2corder/api/order_type',
		'api_return_format'              => 'eb2corder/api/return_format',
		'api_search_operation'           => 'eb2corder/api_search_operation',
		'api_search_service'             => 'eb2corder/api_search_service',
		'api_service'                    => 'eb2corder/api/service',
		'api_ship_group_billing_id'      => 'eb2corder/api/ship_group_billing_id',
		'api_ship_group_destination_id'  => 'eb2corder/api/ship_group_destination_id',
		'api_xml_ns'                     => 'eb2ccore/api/xml_namespace',
		'detail_order_mapping'           => 'eb2corder/detail_mapping/order',
		'detail_address_mapping'         => 'eb2corder/detail_mapping/address_data',
		'detail_order_item_mapping'      => 'eb2corder/detail_mapping/order_item',
		'detail_payment_info_mapping'    => 'eb2corder/detail_mapping/payment_info_data',
		'detail_payment_method_mapping'  => 'eb2corder/detail_mapping/payment_methods',
		'detail_status_mapping'          => 'eb2corder/detail_mapping/status',
		'detail_shipment_info_mapping'   => 'eb2corder/detail_mapping/shipment_info_data',
		'eb2c_payments_enabled'          => 'eb2cpayment/enabled',
		'shipping_tax_class'             => 'eb2corder/shipping/tax_class',
		'transactional_emailer'          => 'eb2ccore/email/transactional_emailer',
		'xsd_file_cancel'                => 'eb2corder/xsd/cancel_file',
		'xsd_file_create'                => 'eb2corder/xsd/create_file',
		'xsd_file_detail'                => 'eb2corder/xsd/detail_file',
		'xsd_file_search'                => 'eb2corder/xsd/search_file',
		'event_cancel_reasons'           => 'eb2corder/order_management/cancel_reasons'
	);
}
