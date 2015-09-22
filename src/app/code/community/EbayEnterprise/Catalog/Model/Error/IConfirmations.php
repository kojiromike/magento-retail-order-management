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
 * The error confirmations class was implemented in such a way to adapt to the
 * way the super product feed is currently functioning
 * How to use the error confirmation class is as follow:
 *
 * Step 1: load the error confirmation file, which can be generated using
 *   external helper methods
 *   Example: EbayEnterprise_Catalog_Model_Error_Confirmations::loadFile('ItemMaster_20140107224605_12345_ABCD.xml');
 * Step 2: initialize primary data such as the xml directive, root node
 *   and message header
 *   Example: EbayEnterprise_Catalog_Model_Error_Confirmations::initFeed('ItemMaster');
 * Step 3: this step mean that within the integration where you have an
 *   error and you need add this error to an error confirmation feed file you
 *   should do as the example below demonstrate:
 *   Example: EbayEnterprise_Catalog_Model_Error_Confirmations::addMessage(
 *     self::DOM_LOAD_ERR,
 *     'Dom exception throw while processing feed file'
 *   );
 *   EbayEnterprise_Catalog_Model_Error_Confirmations::addError(
 *     'ItemMaster',
 *     'ItemMaster_TestSubset.xml'
 *   );
 *   EbayEnterprise_Catalog_Model_Error_Confirmations::addErrorConfirmation(
 *     'SK-ABC-1334'
 *   );
 *   EbayEnterprise_Catalog_Model_Error_Confirmations::flush();
 *   Result:
 *   <ErrorConfirmation unique_id="SK-ABC-1334">
 *     <Error event_type="ItemMaster" file_name="ItemMaster_TestSubset.xml" reported_from="WMS">
 *       <Message xml:lang="en-US" code="">Error exception occurred: Dom exception throw while processing feed file</Message>
 *     </Error>
 *   </ErrorConfirmation>
 * Step 4: closing the file after successfully capturing all possible error
 *   Example: EbayEnterprise_Catalog_Model_Error_Confirmations::close();
 *
 * FYI: because of the way the super feed is setup, we have to dispatch an
 *   event and pass it all the product error_confirmation feed files that
 *   were created in this case the following step will need to happen to
 *   close all error confirmation file, send them to eb2c server and then move
 *   the files from outbound to archive
 * Step 5: dispatch 'product_feed_complete_error_confirmation' event
 *   Example: EbayEnterprise_Catalog_Model_Error_Confirmations::process();
 * Summary: for each of the error confirmation feed files that has been process,
 *   close it with the root node, send the file to eb2c, and then move it to
 *   archive folder.
 */
interface EbayEnterprise_Catalog_Model_Error_IConfirmations
{
    const XML_DIRECTIVE = '<?xml version="1.0" encoding="UTF-8"?>';
    const XML_OPEN_ROOT_NODE = '<ErrorConfirmations>';
    const XML_CLOSE_ROOT_NODE = '</ErrorConfirmations>';

    const XML_ERRCONFIRMATION_OPEN_NODE = '<ErrorConfirmation unique_id="{sku}">';
    const XML_ERRCONFIRMATION_CLOSE_NODE = '</ErrorConfirmation>';

    const XML_ERROR_OPEN_NODE = '<Error event_type="{feed_type}" file_name="{feed_file_name}" reported_from="{from}">';
    const XML_ERROR_CLOSE_NODE = '</Error>';

    const XML_MESSAGE_NODE = '<Message xml:lang="{language_code}" code="">{message_data}</Message>';

    const SKU_NOT_REMOVE = 'The feed process failed to remove this product of operation type "delete"';
    const SKU_NOT_IMPORTED = 'The feed process failed to import this product of operation type "add" or "change"';
    // mask used when creating the error file
    const ERROR_FILE_PERMISSIONS_MASK = 0027;

    /**
     * load the file into SplFileInfo object on writable mode ('w') if not exist
     * otherwise load it on append mode ('a')
     * @param  string
     * @return self
     */
    public function loadFile($fileName);

    /**
     * insert initial data to feed file such as
     * appending xml directive, open root node, and message header
     * @param  string
     * @return self
     */
    public function initFeed($feedType);

    /**
     * append contents to a file, if fileStream property null throw exception
     * else append the content to the file in the defined file stream
     * @param  string $content the content to append to the file
     * @return self
     * @throws EbayEnterprise_Catalog_Model_Error_Exception
     */
    public function append($content = '');

    /**
     * append the closing root node to the error feed file
     * then close file stream and reset all queues to empty array
     * @return self
     */
    public function close();

    /**
     * add error messages to the message queue
     * @param  string $msgTemplate, for the message constant
     * @param  string $message, can be file name or an actually exception message content
     * @return self
     */
    public function addMessage($msgTemplate, $message);

    /**
     * add error messages to the error queue data under
     * the error node and then reset error message queue to an empty array
     * @param  string
     * @param  string
     * @return self
     */
    public function addError($type, $fileName);

    /**
     * add error to the error confirmation queue and then
     * reset the error queue to an empty array
     * @param  string
     * @return self
     */
    public function addErrorConfirmation($sku);

    /**
     * append all the error confirmation items into the feed file
     * and reset the confirmation queue into an empty array
     * @return self
     */
    public function flush();

    /**
     * check if error messages queue has data
     * @return bool true when message queue is not empty other false
     */
    public function hasMessage();

    /**
     * check if error error queue has data
     * @return bool true when error queue is not empty other false
     */
    public function hasError();

    /**
     * check if error error confirmation queue has data
     * @return bool true when error confirmation queue is not empty other false
     */
    public function hasErrorConfirmation();

    /**
     * listen for 'product_feed_complete_error_confirmation' event dispatch
     * get the feed detail information and begin closing each error confirmation file with the closing xml root node
     * send them via SFTP protocol and move local file to archive folder
     * @param  Varien_Event_Observer
     * @return self
     */
    public function process(Varien_Event_Observer $observer);

    /**
     * listen for 'product_feed_process_operation_type_error_confirmation' event dispatch
     * get the feed detail and an array of SKUs that were deleted get a collection of product by the SKUs
     * if there's any product in the collection start adding error confirmation node to the error confirmation file
     * @param  Varien_Event_Observer
     * @return self
     */
    public function processByOperationType(Varien_Event_Observer $observer);
}
