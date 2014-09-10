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
 * On dom:loaded to ensure all one-page checkout assets have
 * loaded and been setup as needed.
 */
document.observe('dom:loaded', function() {
	/**
	 * Address validation response handling. Adds handling for address validation
	 * response messages around the given original checkout step onSave handling.
	 * @param function originalFn - the original method this is replacing
	 * @param Ajax.Response transport - the result of the Ajax call this is a callback to
	 * @return bool
	 */
	var onSaveWithSuggestions = function onSaveWithSuggestions(originalFn, transport) {
		// Call the original next function within the same context
		// and capture the results.
		var success = originalFn.call(this, transport);

		var response = {};
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
	/**
	 * Wrap the checkout step object's onSave function with address
	 * validation handling.
	 * @param  Billing|Shipping checkoutStep
	 */
	var wrapOnSave = function wrapOnSave(checkoutStep) {
		if (checkoutStep) {
			// The onSave function is set in the initialize function for billing
			// and shipping so it should always be set. In the extreme case that
			// it isn't set, default to a no-op function.
			var originalOnSave = checkoutStep.onSave || function () {};
			// Wrap the original function with address validation handling. Bind
			// the wrapped function as an event listener to preserver the expected
			// context.
			checkoutStep.onSave = originalOnSave.wrap(onSaveWithSuggestions)
				.bindAsEventListener(checkoutStep);
		}
	};

	// Wrap the window.billing and window.shipping checkout step objects'
	// onSave functions with address validation response handling.
	[window.billing, window.shipping].each(wrapOnSave);
});
