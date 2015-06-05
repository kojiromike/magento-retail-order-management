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

class EbayEnterprise_Amqp_Model_Config extends EbayEnterprise_Eb2cCore_Model_Config_Abstract
{
    protected $_configPaths = array(
        'last_test_message_timestamp' => 'ebayenterprise_amqp/general/last_test_message_timestamp',
        'number_of_messages_to_process' => 'ebayenterprise_amqp/general/number_of_messages_to_process',

        'connection_context' => 'ebayenterprise_amqp/connection/context',
        'connection_insist' => 'ebayenterprise_amqp/connection/insist',
        'connection_locale' => 'ebayenterprise_amqp/connection/locale',
        'connection_login_method' => 'ebayenterprise_amqp/connection/login_method',
        'connection_read_write_timeout' => 'ebayenterprise_amqp/connection/read_write_timeout',
        'connection_timeout' => 'ebayenterprise_amqp/connection/timeout',
        'connection_type' => 'ebayenterprise_amqp/connection/type',
        'hostname' => 'ebayenterprise_amqp/connection/hostname',
        'password' => 'ebayenterprise_amqp/connection/password',
        'port' => 'ebayenterprise_amqp/connection/port',
        'username' => 'ebayenterprise_amqp/connection/username',
        'vhost' => 'ebayenterprise_amqp/connection/vhost',

        'queue_auto_delete' => 'ebayenterprise_amqp/queue/auto_delete',
        'queue_binding_nowait' => 'ebayenterprise_amqp/queue/binding_nowait',
        'queue_durable' => 'ebayenterprise_amqp/queue/durable',
        'queue_exclusive' => 'ebayenterprise_amqp/queue/exclusive',
        'queue_names' => 'ebayenterprise_amqp/queue/queue_names',
        'queue_nowait' => 'ebayenterprise_amqp/queue/nowait',
        'queue_passive' => 'ebayenterprise_amqp/queue/passive',
    );
}
