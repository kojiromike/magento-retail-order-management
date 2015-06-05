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

(function () {
    // "globals" for the test AMQP button
    var usernameElementId = 'eb2ccore_ebayenterprise_amqp_username';
    var passwordElementId = 'eb2ccore_ebayenterprise_amqp_password';
    var hostnameElementId = 'eb2ccore_ebayenterprise_amqp_hostname';
    // will be injected via addAmqpTestButtonObserver
    var ajaxUrl;

    /**
     * Collect POST params to submit to test the message queue connection.
     * @return {Object}
     */
    function buildMessageQueueParams()
    {
        var usernameEle = $(usernameElementId);
        var usernameScopeEle = usernameEle && adminSystemConfig.getScopeElement(usernameEle);
        var passwordEle = $(passwordElementId);
        var passwordScopeEle = passwordEle && adminSystemConfig.getScopeElement(passwordEle);
        var hostEle = $(hostnameElementId);
        var hostScopeEle = hostEle && adminSystemConfig.getScopeElement(hostEle);
        var params = {
            'username_use_default': usernameScopeEle && usernameScopeEle.checked ? 1 : 0,
            'password_use_default': passwordScopeEle && passwordScopeEle.checked ? 1 : 0,
            'host_use_default': hostScopeEle && hostScopeEle.checked ? 1 : 0,
        };
        // Need to be able to differentiate between these values being empty and
        // non-existent when handling the request.
        if (usernameEle) {
            params.username = usernameEle.value;
        }
        if (passwordEle) {
            params.password = passwordEle.value;
        }
        if (hostEle) {
            params.host = hostEle.value;
        }
        return params;
    }
    /**
     * Parse the response text into JS object.
     * @param  {String} responseBody
     * @return {object}
     */
    function parseResponse(responseBody)
    {
        var response = {};
        try {
            response = responseBody.evalJSON();
        } catch (e) {
            response.success = false;
            response.message = 'Could Not Validate Settings';
        }
        return response;
    }
    /**
     * Update the element with the message and success/fail state of the test request.
     * @param  {Element} elem
     * @param  {Object} response
     */
    function updateWithResponse(elem, response)
    {
        if (response.success) {
            elem.removeClassName('fail').addClassName('success');
        } else {
            elem.removeClassName('success').addClassName('fail');
        }
        $('message_queue_response_message').update(response.message.escapeHTML());
    }
    /**
     * Callback for the test button. Makes an AJAX request to trigger a test
     * connection to the AMQP server.
     * @param {Event} evt
     * @param {Element} elem
     */
    function testAmqpConnection(evt, elem)
    {
        evt.preventDefault();
        new Ajax.Request(ajaxUrl, {
            'parameters': buildMessageQueueParams(),
            'onSuccess': function (xhrResponse) {
                updateWithResponse(elem, parseResponse(xhrResponse.responseText));
            }
        });
    }

    /**
     * Bind an event observer to the AMQP test button.
     * @param {string} elemId id of the test button
     * @param {string} url    URL to make the AJAX request to
     */
    window.addAmqpTestButtonObserver = function (elemId, url) {
        // set ajaxUrl in the outter scope for use within the callback
        ajaxUrl = url;
        $(elemId).on('click', testAmqpConnection);
    };
})();
