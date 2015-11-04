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

class EbayEnterprise_Catalog_Model_Error_Confirmations implements EbayEnterprise_Catalog_Model_Error_IConfirmations
{
    /** @var array hold message string xml node */
    protected $queueMessage = [];
    /** @var array hold error string xml node */
    protected $queueError = [];
    /** @var array hold error confirmation string xml node */
    protected $queueConfirmation = [];
    /** @var SplFileInfo */
    protected $fileStream = null;
    /** @var string */
    protected $defaultLangCode = null;
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $context;
    /** @var EbayEnterprise_Catalog_Helper_Data */
    protected $helper;
    /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
    protected $coreConfig;
    /** @var EbayEnterprise_Catalog_Model_Feed_Core */
    protected $coreFeed;
    /** @var EbayEnterprise_Eb2cCore_Model_Api */
    protected $coreApi;
    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $coreHelper;

    public function __construct(array $arguments = [])
    {
        list(
            $this->logger,
            $this->context,
            $this->helper,
            $this->coreConfig,
            $this->coreApi,
            $this->coreHelper
        ) = $this->checkTypes(
            $this->nullCoalesce($arguments, 'logger', Mage::helper('ebayenterprise_magelog')),
            $this->nullCoalesce($arguments, 'context', Mage::helper('ebayenterprise_magelog/context')),
            $this->nullCoalesce($arguments, 'helper', Mage::helper('ebayenterprise_catalog')),
            $this->nullCoalesce($arguments, 'core_config', Mage::helper('eb2ccore')->getConfigModel()),
            $this->nullCoalesce($arguments, 'core_api', Mage::getModel('eb2ccore/api')),
            $this->nullCoalesce($arguments, 'core_helper', Mage::helper('eb2ccore'))
        );
        $data = ['feed_config' => $this->coreConfig->errorFeed];
        list($this->coreFeed) = $this->checkFeedType(
            $this->nullCoalesce($arguments, 'core_feed', Mage::getModel('ebayenterprise_catalog/feed_core', $data))
        );
    }

    /**
     * Type hinting for self::__construct $arguments
     *
     * @param  EbayEnterprise_MageLog_Helper_Data
     * @param  EbayEnterprise_MageLog_Helper_Context
     * @param  EbayEnterprise_Catalog_Helper_Data
     * @param  EbayEnterprise_Eb2cCore_Model_Config_Registry
     * @param  EbayEnterprise_Eb2cCore_Model_Api
     * @param  EbayEnterprise_Eb2cCore_Helper_Data
     * @return array
     */
    protected function checkTypes(
        EbayEnterprise_MageLog_Helper_Data $logger,
        EbayEnterprise_MageLog_Helper_Context $context,
        EbayEnterprise_Catalog_Helper_Data $helper,
        EbayEnterprise_Eb2cCore_Model_Config_Registry $coreConfig,
        EbayEnterprise_Eb2cCore_Model_Api $coreApi,
        EbayEnterprise_Eb2cCore_Helper_Data $coreHelper
    ) {
        return func_get_args();
    }

    /**
     * Type hinting for self::__construct $arguments
     *
     * @param  EbayEnterprise_Catalog_Model_Feed_Core
     * @return array
     */
    protected function checkFeedType(EbayEnterprise_Catalog_Model_Feed_Core $coreFeed)
    {
        return func_get_args();
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the default value.
     *
     * @param  array
     * @param  string $field Valid array key
     * @param  mixed
     * @return mixed
     */
    protected function nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }

    /**
     * check if the default language code is set to the class
     * property defaultLangCode, if not then set else return the value
     * of the class property
     * @return string
     */
    protected function getLangCode()
    {
        if (trim($this->defaultLangCode) === '') {
            $this->defaultLangCode = $this->helper->getDefaultLanguageCode();
        }
        return $this->defaultLangCode;
    }

    /**
     * abstracting instantiating SplFileInfo
     *
     * @param  string $file file with full path
     * @return SplFileInfo
     * @codeCoverageIgnore
     */
    protected function getSplFileInfo($file)
    {
        return new SplFileInfo($file);
    }

    /**
     * @see EbayEnterprise_Catalog_Model_Error_IConfirmations::loadFile()
     */
    public function loadFile($fileName)
    {
        $this->fileStream = $this->getSplFileInfo($fileName);
        return $this;
    }

    /**
     * @see EbayEnterprise_Catalog_Model_Error_IConfirmations::initFeed()
     */
    public function initFeed($feedType)
    {
        $this->append(self::XML_DIRECTIVE)
            ->append(self::XML_OPEN_ROOT_NODE)
            ->append($this->helper->generateMessageHeader($feedType));

        return $this;
    }

    /**
     * @see EbayEnterprise_Catalog_Model_Error_IConfirmations::append()
     */
    public function append($content = '')
    {
        if (is_null($this->fileStream)) {
            throw new EbayEnterprise_Catalog_Model_Error_Exception(sprintf(
                '[ %s ] Error Confirmations file stream is not instantiated',
                __CLASS__
            ));
        }
        $oldMask = umask(self::ERROR_FILE_PERMISSIONS_MASK);
        $this->fileStream->openFile('a')->fwrite("$content\n");
        umask($oldMask);
        return $this;
    }

    /**
     * @see EbayEnterprise_Catalog_Model_Error_IConfirmations::close()
     */
    public function close()
    {
        $this->append(self::XML_CLOSE_ROOT_NODE);
        if ($this->fileStream) {
            $this->fileStream = null;
        }
        $this->queueMessage = [];
        $this->queueError = [];
        $this->queueConfirmation = [];
        return $this;
    }

    /**
     * @see EbayEnterprise_Catalog_Model_Error_IConfirmations::addMessage()
     */
    public function addMessage($msgTemplate, $message)
    {
        $this->queueMessage[] = $this->helper->mapPattern(
            [
                'language_code' => $this->getLangCode(),
                'message_data' => sprintf($msgTemplate, $message)
            ],
            self::XML_MESSAGE_NODE
        );
        return $this;
    }

    /**
     * @see EbayEnterprise_Catalog_Model_Error_IConfirmations::addError()
     */
    public function addError($type, $fileName)
    {
        $this->queueError[] = sprintf(
            "%s\n%s\n%s",
            $this->helper->mapPattern($this->getErrorTemplate($type, $fileName), self::XML_ERROR_OPEN_NODE),
            implode("\n", $this->queueMessage),
            self::XML_ERROR_CLOSE_NODE
        );
        // reset message queue
        $this->queueMessage = [];
        return $this;
    }

    /**
     * @see EbayEnterprise_Catalog_Model_Error_IConfirmations::addErrorConfirmation()
     */
    public function addErrorConfirmation($sku)
    {
        $this->queueConfirmation[] = sprintf(
            "%s\n%s\n%s",
            $this->helper->mapPattern(['sku' => $sku], self::XML_ERRCONFIRMATION_OPEN_NODE),
            implode("\n", $this->queueError),
            self::XML_ERRCONFIRMATION_CLOSE_NODE
        );
        // reset error queue
        $this->queueError = [];
        return $this;
    }

    /**
     * @see EbayEnterprise_Catalog_Model_Error_IConfirmations::flush()
     */
    public function flush()
    {
        $this->append(implode("\n", $this->queueConfirmation));
        $this->queueConfirmation = [];
        return $this;
    }

    /**
     * get error template
     * @param  string
     * @param  string
     * @return array
     */
    protected function getErrorTemplate($type, $fileName)
    {
        return [
            'feed_type' => $type,
            'feed_file_name' => $fileName,
            'from' => EbayEnterprise_Catalog_Helper_Feed::DEST_ID
        ];
    }

    /**
     * @see EbayEnterprise_Catalog_Model_Error_IConfirmations::hasMessage()
     */
    public function hasMessage()
    {
        return !empty($this->queueMessage);
    }

    /**
     * @see EbayEnterprise_Catalog_Model_Error_IConfirmations::hasError()
     */
    public function hasError()
    {
        return !empty($this->queueError);
    }

    /**
     * @see EbayEnterprise_Catalog_Model_Error_IConfirmations::hasErrorConfirmation()
     */
    public function hasErrorConfirmation()
    {
        return !empty($this->queueConfirmation);
    }

    /**
     * @see EbayEnterprise_Catalog_Model_Error_IConfirmations::process()
     */
    public function process(Varien_Event_Observer $observer)
    {
        /** @var Varien_Event */
        $event = $observer->getEvent();
        /** @var array */
        $feedDetails = (array) $event->getFeedDetails();

        foreach ($feedDetails as $detail) {
            /** @var string */
            $fileName = $detail['error_file'];

            $this->loadFile($fileName);

            // If no in-progress error file exists for the feed detail, it was
            // either never created and nothing was written to it, or it was
            // already processed. Don't attempt to process it again, move on.
            if (!$this->fileStream->isFile()) {
                $this->logger->debug('Error confirmation file does not exist: {file_name}', $this->context->getMetaData(__CLASS__, ['file_name' => $fileName]));
                continue;
            }

            // If the file exists but nothing was ever written to it, was not
            // initialized and no errors written to it, no need to send the empty
            // error file so simply remove it and move on.
            if ($this->fileStream->getSize() === 0) {
                $this->removeFile($fileName);
                $this->logger->debug('Error confirmation file is empty: {file_name}', $this->context->getMetaData(__CLASS__, ['file_name' => $fileName]));
                continue;
            }

            // If the error file exists and has had data written to it, close
            // the file - append closing XML tag.
            $this->close();

            // Ensure that only valid files are sent - may have had bad data
            // written to the file or in some other way be invalid. ProductHub
            // will be unable to do anything with invalid files and may cause
            // issues for the system if it receives any.
            if ($this->isValidPayload($fileName)) {
                $this->coreFeed->mvToLocalDirectory($fileName);
                $this->logger->debug('Sending Error confirmation File: {file_name}', $this->context->getMetaData(__CLASS__, ['file_name' => $fileName]));
            } else {
                $this->removeFile($fileName);
                $this->logger->debug('Error confirmation File: {file_name} has invalid XML', $this->context->getMetaData(__CLASS__, ['file_name' => $fileName]));
            }
        }

        return $this;
    }

    /**
     * @see EbayEnterprise_Catalog_Model_Error_IConfirmations::processByOperationType()
     * @return self
     */
    public function processByOperationType(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        $detail = $event->getFeedDetail();
        $skus = $event->getSkus();
        $operationType = $event->getOperationType();
        $collection = $this->getProductCollectionBySkus($skus);

        $fileName = basename($detail['local_file']);
        $errorFile = $detail['error_file'];
        $type = $detail['core_feed']->getEventType();

        $this->loadFile($errorFile);

        return ($operationType === 'delete')?
            $this->addDeleteErrors($collection, $fileName, $type) :
            $this->addImportErrors($collection, $skus, $fileName, $type);
    }

    /**
     * given a Mage_Catalog_Model_Resource_Product_Collection object if there's any product
     * add them to the error confirmation file
     * @param  Mage_Catalog_Model_Resource_Product_Collection $collection
     * @param  string $fileName the file the sku was found on
     * @param  string $type the event type
     * @return self
     */
    protected function addDeleteErrors(
        Mage_Catalog_Model_Resource_Product_Collection $collection,
        $fileName,
        $type
    ) {
        if ($collection->count()) {
            foreach ($collection as $product) {
                $this->appendError(self::SKU_NOT_REMOVE, '', $type, $fileName, $product->getSku());
            }
        }
        return $this;
    }

    /**
     * given a Mage_Catalog_Model_Resource_Product_Collection object and a list of SKUs
     * if the SKUs in the list of sku is not in the collection then add error confirmation node
     * to feed file about sku was not imported
     * @param  Mage_Catalog_Model_Resource_Product_Collection $collection
     * @param  array list of SKUs that were suppose to be imported
     * @param  string the file the sku was found on
     * @param  string the event type
     * @return self
     */
    protected function addImportErrors(
        Mage_Catalog_Model_Resource_Product_Collection $collection,
        array $skus,
        $fileName,
        $type
    ) {
        foreach ($skus as $sku) {
            $product = $collection->getItemByColumnValue('sku', $sku);
            if (is_null($product)) {
                $this->appendError(self::SKU_NOT_IMPORTED, '', $type, $fileName, $sku);
            }
        }
        return $this;
    }

    /**
     * given message template, message, event type, file name and a sku call
     * the necessary method to add and error confirmation node with these data
     * @param  string $template the message template to use
     * @param  string $message the message to be mapped to the template
     * @param  string $type the event type
     * @param  string $fileName the file this error occurred on
     * @param  string $sku the sku this error is for
     * @return self
     */
    protected function appendError($template, $message, $type, $fileName, $sku)
    {
        return $this->addMessage($template, $message)
            ->addError($type, $fileName)
            ->addErrorConfirmation($sku)
            ->flush();
    }

    /**
     * given a list of SKUs query the Mage_Catalog_Model_Resource_Product_Collection
     * for all product in this list of SKUs
     * @param  array the list of SKUs to filter the collection for
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    protected function getProductCollectionBySkus(array $skus)
    {
        return Mage::getResourceModel('catalog/product_collection')
            ->addFieldToFilter('sku', $skus)
            ->addAttributeToSelect(['sku'])
            ->load();
    }

    /**
     * Remove an error confirmation file because it is empty.
     *
     * @param  string
     * @return self
     */
    protected function removeFile($fileName)
    {
        /** @var Varien_Io_File | null */
        $file = $this->coreFeed->getFsTool();
        if ($file instanceof Varien_Io_File) {
            $file->rm($fileName);
        }
        return $this;
    }

    /**
     * Validate Error confirmation against its XSD
     *
     * @param  string
     * @return bool
     */
    protected function isValidPayload($fileName)
    {
        /** @var array */
        $data = $this->coreConfig->errorFeed;
        try {
            $doc = $this->loadDoc($fileName);
            $this->coreApi->schemaValidate($doc, $data['xsd']);
        } catch (EbayEnterprise_Eb2cCore_Exception_InvalidXml $e) {
            return false;
        }
        return true;
    }

    /**
     * Load the XML file into a new DOMDocument.
     *
     * @param string
     * @return DOMDocument
     * @throws EbayEnterprise_Eb2cCore_Exception_InvalidXml If file files to be loaded into a DOMDocument.
     */
    protected function loadDoc($fileName)
    {
        $doc = $this->coreHelper->getNewDomDocument();
        if ($doc->load($fileName)) {
            return $doc;
        }
        // If file could not be loaded into a DOMDocument, treat the XML as invalid.
        throw Mage::exception('EbayEnterprise_Eb2cCore_Exception_InvalidXml', "Could not load $fileName in DOMDocument.");
    }
}
