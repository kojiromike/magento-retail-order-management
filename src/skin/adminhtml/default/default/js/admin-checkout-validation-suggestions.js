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

document.observe('dom:loaded', function () {
    /**
     * Replacement for the onepage Billing and Shipping methods.
     * Need to ensure the original method is called as well as the additinal steps
     * to insert the suggestions. This means some steps are duplicated but
     * also helps to reduce the risk of the overridden method diverging too far from
     * its replacement.
     * @param function originalFn - the original method this is replacing
     * @param Ajax.Response transport - the result of the Ajax call this is a callback to
     * @return bool
     */
    var nextWithSuggestions = function nextWithSuggestions(originalFn, transport)
    {
        // call the original next function and capture the results
        var success = originalFn.call(this, transport);
        var response = {};
        // this, unfortunately, is repeated in the originalFn
        if (transport && transport.responseText) {
            response = transport.responseText.evalJSON();
        }

        if ($(this.form)) {
            // always remove any suggestions that may already exist in the form
            if ($(this.form).down('.suggestion-fields')) {
                $(this.form).down('.suggestion-fields').remove();
            }
            // if there are suggestions, add them into the page and make sure one is selected
            if (response.suggestions) {
                $(this.form).insert({'top': response.suggestions});

                var firstChoice = $(this.form).down('.suggestion-fields input[type="radio"]');
                if (firstChoice) {
                    firstChoice.checked = true;
                    window.triggerEvent(firstChoice, 'change');
                }

                var sameAsBilling = $(this.form).down('input[name="shipping[same_as_billing]"]');
                if (sameAsBilling) {
                    sameAsBilling.checked = false;
                }
            } else {
                $(this.form).down('.hide-new-address').removeClassName('hide-new-address');
            }
        }

        return success;
    }

    if (window.Billing) {
        // replace the original nextStep method with the override
        var origNext = window.Billing.prototype.nextStep;
        window.Billing.prototype.nextStep = function (transport) {
            return nextWithSuggestions.call(this, origNext, transport);
        };
    }
    if (window.billing) {
        window.billing.onSave = window.Billing.prototype.nextStep.bindAsEventListener(window.billing);
    }

    if (window.Shipping) {
        // replace the original nextStep method with the override
        var origNext = window.Shipping.prototype.nextStep;
        window.Shipping.prototype.nextStep = function (transport) {
            return nextWithSuggestions.call(this, origNext, transport);
        };
    }
    if (window.shipping) {
        window.shipping.onSave = window.Shipping.prototype.nextStep.bindAsEventListener(window.shipping);
    }

});
