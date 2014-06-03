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

document.observe('dom:loaded', function() {
	/**
	 * Replacement for the onepage Billing and Shipping methods.
	 * Need to ensure the original method is called as well as the additional steps
	 * to insert the suggestions. This means some steps are duplicated but
	 * also helps to reduce the risk of the overridden method diverging too far from
	 * its replacement.
	 * @param function originalFn - the original method this is replacing
	 * @param Ajax.Response transport - the result of the Ajax call this is a callback to
	 * @return boolean
	 */
	var nextWithSuggestions = function nextWithSuggestions(originalFn, transport) {
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
	};

	if (window.Billing) {
		// replace the original nextStep method with the override, wrapping the
		// original function with added handling for address validation suggestions
		var origBillNext = window.Billing.prototype.nextStep;
		window.Billing.prototype.nextStep = function (transport) {
			return nextWithSuggestions.call(this, origBillNext, transport);
		};
	}
	/*
	 * If billing.onSave has been defined as a function, must assume it will call
	 * Billing.nextStep. This is necessary as:
	 * Wrapping the existing method with another that will certainly call
	 * Billing.nextStep may duplicate the call if the original method is already
	 * calling it.
	 * Replacing the method with a call to Billing.nextStep may remove other
	 * necessary actions taken by the already defined function.
	 * If the function has not yet been defined, define it as simply calling
	 * Billing.nextStep, properly bound as an event listener.
	 */
	if (typeof(window.billing.onSave) !== 'function') {
		window.billing.onSave = window.Billing.prototype.nextStep.bindAsEventListener(window.billing);
	}

	if (window.Shipping) {
		// replace the original nextStep method with the override, wrapping the
		// original function with added handling for address validation suggestions
		var origShipNext = window.Shipping.prototype.nextStep;
		window.Shipping.prototype.nextStep = function (transport) {
			return nextWithSuggestions.call(this, origShipNext, transport);
		};
	}
	/*
	 * Same as billing.onSave. If defined, must assume it will call
	 * Shipping.nextStep. Otherwise, define it as Shipping.nextStep bound as an
	 * event listener.
	 */
	if (typeof(window.shipping.onSave) !== 'function') {
		window.shipping.onSave = window.Shipping.prototype.nextStep.bindAsEventListener(window.shipping);
	}

});
