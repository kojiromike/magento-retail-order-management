(function () {
	/**
	 * Trigger a native browser event.
	 * @param Element element
	 * @param string eventName
	 */
	window.triggerEvent = function triggerEvent(element, eventName) {
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
		var addressData = input.readAttribute('data-address-data').evalJSON();
		var form = input.up('form');
		if (addressData && form) {
			var arrElements = form.getElements();
			var fieldName;
			var element;
			for (var elemIndex in arrElements) {
				if (arrElements.hasOwnProperty(elemIndex)) {
					element = arrElements[elemIndex];
					// remove any 'billing:' or 'shipping:' prefixes
					// and have street lines match what they are called when split on the backend ('street1' instead of 'street_1')
					fieldName = element.id && element.id.replace(/^(?:billing|shipping):/, '').replace(/(street)_(\d)/, '$1$2');
					// apparently the 'postcode' field may be called 'zip' on the frontend...even though it's postcode *everywhere* else
					if (fieldName === 'zip') {
						fieldName = 'postcode';
					}
					// and the 'country_id' field may just be 'country'
					if (fieldName === 'country') {
						fieldName = 'country_id';
					}
					if (addressData[fieldName] && addressData[fieldName] !== element.value) {
						element.value = addressData[fieldName];
						window.triggerEvent(element, 'change');
					}
				}
			}
		}
		return input;
	};

	/**
	 * Event handler for change events on the address suggestion inputs.
	 * Updates the parent address form with the suggested address data
	 * and hides the new address form.
	 * @param Event evt
	 */
	var addressSelectionMade = function addressSelectionMade(evt) {
		fillFormWithSuggestion(evt.findElement('input'))
			.up('form')
			.down('.customer-address-form-list')[(
				evt.findElement('input').readAttribute('id') === 'suggestion-new'
					? 'removeClassName'
					: 'addClassName'
			)]('hide-new-address');
	}

	$(document).on('change', 'input[name="validation_option"]', addressSelectionMade);

})();