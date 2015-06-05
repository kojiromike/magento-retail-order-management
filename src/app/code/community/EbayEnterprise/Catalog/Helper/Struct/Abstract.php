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
 * Implementations can provide a strict enumeration of values type hints can enforce.
 */
abstract class EbayEnterprise_Catalog_Helper_Struct_Abstract
{
    const VALUE = 'undefined';

    /**
     * @return string The specific value for this type
     */
    public function getValue()
    {
        // Late static binding so subclasses can override VALUE without overriding getValue.
        return static::VALUE;
    }
}
