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

class EbayEnterprise_Eb2cCustomerService_Test_Model_Overrides_Admin_SessionTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    public function setUp()
    {
        parent::setUp();

        // suppressing the real session from starting
        $session = $this->getModelMockBuilder('core/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->replaceByMock('singleton', 'core/session', $session);
    }
    /**
     * Scenario: Get CSR Administrative user startup page URL
     * When getting CSR Administrative user startup page URL
     * Then get the user's configured startup URL path
     * And get the full URL using the startup URL path
     */
    public function testGetStartpageUri()
    {
        $adminUrl = 'admin/some/where';
        $expectedUrl = "https://example-test.com/index.php/$adminUrl";

        $url = $this->getModelMockBuilder('adminhtml/url')
            ->disableOriginalConstructor()
            ->setMethods(['getUrl'])
            ->getMock();
        $url->expects($this->once())
            ->method('getUrl')
            ->with($this->identicalTo($adminUrl))
            ->will($this->returnValue($expectedUrl));

        $user = $this->getModelMockBuilder('admin/user')
            ->disableOriginalConstructor()
            ->setMethods(['getStartupPageUrl'])
            ->getMock();
        $user->expects($this->once())
            ->method('getStartupPageUrl')
            ->will($this->returnValue($adminUrl));

        $session = $this->getModelMockBuilder('admin/session')
            ->disableOriginalConstructor()
            ->setMethods(['getUser'])
            ->getMock();
        $session->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($user));

        EcomDev_Utils_Reflection::setRestrictedPropertyValue($session, 'url', $url);

        $this->assertSame(
            $expectedUrl,
            EcomDev_Utils_Reflection::invokeRestrictedMethod($session, '_getStartpageUri')
        );
    }
}
