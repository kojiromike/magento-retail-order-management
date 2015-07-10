<?php

use eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class EbayEnterprise_GiftCard_Test_Helper_TendertypeTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /** @var EbayEnterprise_GiftCard_Helper_Data */
    protected $helper;
    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $coreHelper;
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $logContext;
    /** @var LoggerInterface */
    protected $apiLogger;
    /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
    protected $config;
    /** @var IBidirectionalApi */
    protected $api;

    public function setUp()
    {
        $this->helper = $this->getHelperMock('ebayenterprise_giftcard/data', ['__', 'getConfigData']);
        $this->coreHelper = $this->getHelperMock('eb2ccore/data', ['getSdkApi']);
        $this->logger = $this->getHelperMock('ebayenterprise_magelog/data');
        $this->logContext = $this->getHelperMock('ebayenterprise_magelog/context');
        $this->logContext->expects($this->any())
            ->method('getMetaData')
            ->will($this->returnValue([]));
        $this->apiLogger = $this->getMock('\Psr\Log\NullLogger');
        $this->config = $this->buildCoreConfigRegistry([
            'apiService' => 'payments',
            'apiOperationTenderTypeLookup' => 'tendertype/lookup',
        ]);
        $this->constructorArgs = [
            'core_helper' => $this->coreHelper,
            'helper' => $this->helper,
            'logger' => $this->logger,
            'log_context' => $this->logContext,
            'api_logger' => $this->apiLogger,
            'config' => $this->config,
        ];
    }

    public function testLookupTenderType()
    {
        $cardNumber = '23432424';
        $currencyCode = 'USD';
        $panIsToken = false;
        $this->api = $this->getMockBuilder('\eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi')
            ->getMockForAbstractClass();
        $lookup = $this->getModelMockBuilder('ebayenterprise_giftcard/tendertype_lookup')
            ->disableOriginalConstructor()
            ->setMethods(['getTenderType'])
            ->getMock();
        $tenderTypeHelper = $this->getHelperMockBuilder('ebayenterprise_giftcard/tendertype')
            ->setMethods(['getTenderTypeLookupApi', 'createTenderTypeLookup'])
            ->setConstructorArgs([$this->constructorArgs])
            ->getMock();
        $tenderTypeHelper->expects($this->once())
            ->method('getTenderTypeLookupApi')
            ->will($this->returnValue($this->api));
        $tenderTypeHelper->expects($this->once())
            ->method('createTenderTypeLookup')
            ->with(
                $this->identicalTo($cardNumber),
                $this->identicalTo($currencyCode),
                $this->identicalTo($this->api),
                $this->identicalTo($panIsToken)
            )
            ->will($this->returnValue($lookup));
        $lookup->expects($this->once())
            ->method('getTenderType')
            ->will($this->returnValue($tenderType));
        $this->assertSame(
            $tenderType,
            $tenderTypeHelper->lookupTenderType($cardNumber, $currencyCode, $panIsToken)
        );
    }

    /**
     *
     *
     * @expectedException EbayEnterprise_GiftCard_Exception_InvalidCardNumber_Exception
     */
    public function testLookupTenderTypeException()
    {
        $cardNumber = '23432424';
        $currencyCode = 'USD';
        $panIsToken = false;
        $tenderTypeHelper = $this->getHelperMockBuilder('ebayenterprise_giftcard/tendertype')
            ->setMethods(['getTenderTypeLookupApi', 'createTenderTypeLookup'])
            ->setConstructorArgs([$this->constructorArgs])
            ->getMock();
        $this->api = $this->getMockBuilder('\eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi')
            ->getMockForAbstractClass();
        $lookup = $this->getModelMockBuilder('ebayenterprise_giftcard/tendertype_lookup')
            ->disableOriginalConstructor()
            ->setMethods(['getTenderType'])
            ->getMock();
        $tenderTypeHelper->expects($this->once())
            ->method('getTenderTypeLookupApi')
            ->will($this->returnValue($this->api));
        $tenderTypeHelper->expects($this->once())
            ->method('createTenderTypeLookup')
            ->with(
                $this->identicalTo($cardNumber),
                $this->identicalTo($currencyCode),
                $this->identicalTo($this->api),
                $this->identicalTo($panIsToken)
            )
            ->will($this->returnValue($lookup));
        $lookup->expects($this->once())
            ->method('getTenderType')
            ->will($this->throwException(
                Mage::exception(
                    'EbayEnterprise_GiftCard_Exception_TenderTypeLookupFailed',
                    'test exception'
                )
            ));
        $tenderTypeHelper->lookupTenderType($cardNumber, $currencyCode, $panIsToken);
    }

    public function testGetTenderTypeLookupApi()
    {
        $service = 'payments';
        $operation = 'tendertype/lookup';
        $tenderTypeHelper = $this->getHelperMockBuilder('ebayenterprise_giftcard/tendertype')
            ->setMethods(null)
            ->setConstructorArgs([$this->constructorArgs])
            ->getMock();
        $this->api = $this->getMockBuilder('\eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi')
            ->getMockForAbstractClass();
        $this->coreHelper->expects($this->once())
            ->method('getSdkApi')
            ->with(
                $this->identicalTo($service),
                $this->identicalTo($operation),
                $this->identicalTo([]),
                $this->identicalTo($this->apiLogger)
            )
            ->will($this->returnValue($this->api));
        $this->assertSame(
            $this->api,
            EcomDev_Utils_Reflection::invokeRestrictedMethod($tenderTypeHelper, 'getTenderTypeLookupApi')
        );
    }
}
