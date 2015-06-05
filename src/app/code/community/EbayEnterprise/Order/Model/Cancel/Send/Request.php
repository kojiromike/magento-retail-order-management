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

use \eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderCancelRequest;
use \eBayEnterprise\RetailOrderManagement\Api\Exception\NetworkError;
use \eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedHttpAction;
use \eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedOperation;

class EbayEnterprise_Order_Model_Cancel_Send_Request extends EbayEnterprise_Order_Model_Abstract_Send implements EbayEnterprise_Order_Model_Cancel_Send_IRequest
{
    const REQUEST_PAYLOAD_NAME = 'OrderCancelRequest';
}
