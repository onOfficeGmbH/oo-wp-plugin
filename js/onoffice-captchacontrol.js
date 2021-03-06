var onOffice = onOffice || {};

(function() {
	onOffice.captchaControl = function(formElement, submitButtonElement) {
		this._formElement = formElement;
		var self = this;
		submitButtonElement.onclick = function(event) {
			event.preventDefault();
			if (!self._formElement.checkValidity() && !_isMSIE()) {
				self._formElement.reportValidity();
			} else {
				window.grecaptcha.execute();
			};
		};
	};

	var _isMSIE = function() {
		var userAgent = window.navigator.userAgent;
		var iePosition = userAgent.indexOf("MSIE ");

		return iePosition > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./);
	};
})();