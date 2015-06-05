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

interface EbayEnterprise_Order_Model_Cancel_Send_IRequest extends EbayEnterprise_Order_Model_Abstract_ISend
{
    /**
     * Send the order cancel request payload and return a valid
     * response payload when the request was successfully sent
     * and we get back a valid response. Otherwise, return
     * null when any exception is thrown.
     *
     * @return IOrderCancelResponse | null
     */
    public function send();
}
