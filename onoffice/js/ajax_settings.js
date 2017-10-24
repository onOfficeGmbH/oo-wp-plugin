var onOffice = onOffice || {};
onOffice.settings = onOffice_loc_settings;

onOffice.ajaxSaver = function(outerDiv) {
	if (typeof $ === 'undefined') {
		$ = jQuery;
	}
	this._outerDiv = $('#' + outerDiv);
};

(function() {
	this.register = function() {
		var proto = this;
		this._outerDiv.find('#send_ajax').on('click', function() {
			proto.save();
		});
	};

	this.save = function() {
		var data = {};
		var values = this._getValues();
		data.action = onOffice.settings.action;
		data.nonce = onOffice.settings.nonce;
		data.values = JSON.stringify(values);
		data.record_id = onOffice.settings.record_id;

		jQuery.post(onOffice.settings.ajax_url, data, function(response) {
			var jsonResponse;
			try {
				jsonResponse = JSON.parse(response);
			} catch (e) {
				jsonResponse = false;
			}

			if (jsonResponse === true) {
				$('#onoffice-notice-wrapper').append('<div class="notice notice-success is-dismissible"><p>' +
					onOffice.settings.view_save_success_message + '</p></div>');
			} else {
				$('#onoffice-notice-wrapper').append('<div class="notice notice-error is-dismissible"><p>' +
					onOffice.settings.view_save_fail_message + '</p></div>');
			}
			$(document).trigger('wp-updates-notice-added');
		});
	};

	this._getValues = function() {
		var values = {};
		var proto = this;
		this._outerDiv.find('.onoffice-input').each(function(i, elem) {
			var inputNameFull = $(elem).attr('name');
			var inputName = inputNameFull;
			var elementValue = proto._getValueOfElement(elem);

			if (elementValue === null) {
				return;
			}

			var inputContainsArray = inputNameFull.match(/\[\]$/);

			if (inputContainsArray) { // array
				inputName = inputNameFull.replace(/\[\]$/, '');
				if (values[inputName] === undefined) {
					values[inputName] = [];
				}
				values[inputName].push(elementValue);
			} else {
				values[inputName] = elementValue;
			}
		});

		return values;
	};

	this._getValueOfElement = function(element) {
		var value = null;
		switch ($(element).attr('type')) {
			case 'radio':
				if ($(element).attr('selected')) {
					value = $(element).val();
				}
				break;
			case 'checkbox':
				if ($(element).attr('checked')) {
					value = $(element).val();
				}
				break;
			default:
				value = $(element).val();
		}
		return value;
	};
}).call(onOffice.ajaxSaver.prototype);

