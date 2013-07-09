$(document).on('change', 'input[name="validation_option"]', function (evt) {
	evt.findElement('input')
		.up('form')
		.down('.customer-address-form-list')[(
			evt.findElement('input').readAttribute('id') === 'suggestion-new'
				? 'removeClassName'
				: 'addClassName'
		)]('hide-new-address');
});
