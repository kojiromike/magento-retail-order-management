<?php
/**
 *
 */
class TrueAction_Eb2c_Order_Helper_Constants extends Mage_Core_Helper_Abstract
{
	const REGION = 'na';
	const RETURN_FORMAT = 'xml';
	const SERVICE = 'orders';
	const URI_FROMAT = 'https://%s.%s.gsipartners.com/%s/stores/%s/%s/%s.%s';
	const VERSION = 'v1.10';
	const XMLNS = 'http://api.gsicommerce.com/schema/checkout/1.0';

	const CREATE_VERB = 'create';
	const CANCEL_VERB = 'cancel';

	const CREATE_DOM_ROOT_NODE_NAME = 'OrderCreateRequest';
	const CANCEL_DOM_ROOT_NODE_NAME = 'OrderCancelRequest';
	const DOM_ROOT_NS = 'http://api.gsicommerce.com/schema/checkout/1.0';

	const ORDER_TYPE = 'SALES';
	const LEVEL_OF_SERVICE = 'REGULAR';

	const SHIPGROUP_DESTINATION_ID = 'dest_1';
	const SHIPGROUP_BILLING_ID = 'billing_1';
	const ORDER_HISTORY_PATH = 'sales/order/view/order_id/';
}
