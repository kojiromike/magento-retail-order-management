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
    /**
     * Trigger a native browser event.
     * @param Element element
     * @param string eventName
     */
    window.triggerEvent = function triggerEvent(element, eventName)
    {
        if (document.createEvent) {
            var evt = document.createEvent('HTMLEvents');
            evt.initEvent(eventName, true, true);
            return element.dispatchEvent(evt);
        }
        if (element.fireEvent) {
            return element.fireEvent('on' + eventName);
        }
    }

    /**
     * Update the form owning the provided suggestion input element with
     * the address data for the address suggestion.
     * @param Element input - the suggestion input element
     * @return Element input - the supplied input element for chainability
     */
    var fillFormWithSuggestion = function (input) {
/*
data-address-data = {
    "street1":"1 S Rosedale St",
    "street2":null,
    "street3":null,
    "street4":null,
    "city":"Baltimore",
    "region_id":31,"
    "country_id":"US",
    "postcode":"21229-3739"
}"
 */
        var addressData = input.readAttribute('data-address-data').evalJSON();
        var fields = Element('order-shipping_address_fields');
        if (addressData && fields) {
            $('order-shipping_address_street0').value   = addressData.street1;
            $('order-shipping_address_street1').value   = addressData.street2;
            $('order-shipping_address_city').value      = addressData.city;
            $('order-shipping_address_region_id').value = addressData.region_id;
            $('order-shipping_address_city').value      = addressData.city;
            $('order-shipping_address_postcode').value  = addressData.postcode;
            window.triggerEvent($('order-shipping_address_city'), 'change');
        }
        return input;
    };

    /**
     * Event handler for change events on the address suggestion inputs.
     * Updates the parent address form with the suggested address data
     * and hides the new address form.
     * @param Event evt
     */
    var addressSelectionMade = function addressSelectionMade(evt)
    {
        fillFormWithSuggestion(evt.findElement('input'));
    }

    $(document).on('change', 'input[name="validation_option"]', addressSelectionMade);

})();
