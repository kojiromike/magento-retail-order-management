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

/**
 * Marker interface for objects that are guaranteed safe for storage in the
 * session - all data must be serializable. Any implementing objects are
 * expected to be lightweight and store only minimal data, preferably in the
 * form of primitive values or simple php objects.
 */
interface EbayEnterprise_Eb2cCore_Model_ISessionsafe
{
}
