document.observe('dom:loaded', function() {
	/**
	 * Replacement for the onepage Billing and Shipping methods.
	 * Need to ensure the original method is called as well as the additinal steps
	 * to insert the suggestions. This means some steps are duplicated but
	 * also helps to reduce the risk of the overridden method diverging too far from
	 * it's replacement.
	 * @param function originalFn - the original method this is replacing
	 * @param Ajax.Response transport - the result of the Ajax call this is a callback to
	 * @return boolean
	 */
	var nextWithSuggestions = function nextWithSuggestions(originalFn, transport) {
		// call the original next function and capture the results
		var success = originalFn.call(this, transport);
		
		// this, unfortunately, is repeated in the originalFn
		if (transport && transport.responseText) {
			var response = transport.responseText.evalJSON();
		}
		// when the response has suggestions, insert them into the form
		if ($(this.form) && response.suggestions) {
			// first, remove any existing suggestions
			if ($(this.form).down('.suggestion-fields')) {
				$(this.form).down('.suggestion-fields').remove();
			}
			$(this.form).insert({'top': response.suggestions});
		}

		return success;
	}

	if (window.Billing) {
		// replace the original nextStep method with the override
		var origNext = window.Billing.prototype.nextStep;
		window.Billing.prototype.nextStep = function (transport) {
			return nextWithSuggestions.call(this, origNext, transport);
		}
	}

	if (window.Shipping) {
		// replace the original nextStep method with the override
		var origNext = window.Shipping.prototype.nextStep;
		window.Shipping.prototype.nextStep = function (transport) {
			return nextWithSuggestions.call(this, origNext, transport);
		}
	}

});
