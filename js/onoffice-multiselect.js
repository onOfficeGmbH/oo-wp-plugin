var onOffice = onOffice || {};
(function() {
	var checkboxCounter = checkboxCounter || 0;

	function multiselect(element, options, preselected, settings = {}) {
		this._element = typeof(element) === 'string' ? document.getElementById(element) : element;
		this._options = options || {};
		this._name = this._element.getAttribute('data-name');
		this._settings = settings;
		this._load(preselected);
	};


	multiselect.prototype._load = function(preselected) {
        var divPopup = document.createElement('div');
        divPopup.hidden = true;
        divPopup.className = 'onoffice-multiselect-popup';
        this._element.appendChild(divPopup);
		this.reloadWithOptions(this._options, preselected);
		this._displaySpan = document.createElement('span');
		this._element.appendChild(this._displaySpan);
		this.refreshlabel();
	};


	multiselect.prototype.show = function() {
		this._getChildDiv('onoffice-multiselect-popup').hidden = false;
		this.hideLabel();
	};


	multiselect.prototype.hide = function() {
		this._getChildDiv('onoffice-multiselect-popup').hidden = true;
		this.refreshlabel();
	};


	multiselect.prototype._getChildDiv = function(className) {
		var childnodes = [].slice.call(this._element.childNodes);
		var divs = childnodes.filter(function(element) {
			return element.nodeName.toLowerCase() === 'div' &&
				element.className === className;
		});

		if (divs.length > 0) {
			return divs[0];
		}

		throw new Error('child div not found');
	};


	multiselect.prototype.refreshlabel = function() {
		var selection = this._getSelection();
		var labels = [];

		for (var i in selection) {
			var key = selection[i];
			if (this._options[key] !== undefined) {
				labels.push(this._options[key]);
			}
		}

		this._displaySpan.textContent = ' ' + labels.join(', ');
		this.showLabel();
	};

    multiselect.prototype.reloadWithOptions = function(options, preselected) {
        this._options = options;
        var parent = this;
        var output = '';
        preselected = preselected || [];

        for (var key in this._options) {
            checkboxCounter++;
            var value = this._options[key];
            var checked = preselected.indexOf(key) >= 0 ? ' checked' : '';
            var nameSuffix = (this._settings.name_is_array || false) ? '[]' : '';
            var className = this._settings.cb_class || '';

            output += '<label for=cb' + checkboxCounter + '>' +
                '<input type="checkbox" name=' + this._name + nameSuffix + ' value="' + key + '" ' +
                checked + ' id="cb' + checkboxCounter + '" class="' + className + '">' + value + '</label>';
        }

        var divPopup = this._element.querySelector('div.onoffice-multiselect-popup');
        divPopup.innerHTML = output;

        var button = document.createElement('input');
        button.type = 'button';
        button.value = 'OK';
        button.onclick = function() {
            parent.hide();
            const event = new CustomEvent('onoffice-multiselect-change', { detail: {
                name: parent._name,
                selection: parent._getSelection()
            }});
            parent._element.dispatchEvent(event);
        };

        divPopup.appendChild(button);
    };


	multiselect.prototype.hideLabel = function() {
		this._displaySpan.hidden = true;
	};


	multiselect.prototype.showLabel = function() {
		this._displaySpan.hidden = false;
	};


	multiselect.prototype._getSelection = function() {
		var childNodes = this._getChildDiv('onoffice-multiselect-popup').childNodes;
		var elements = [].slice.call(childNodes);
		var inputs = elements.filter(function(element) {
			return element.nodeName === 'LABEL' &&
				element.childNodes[0].type === 'checkbox' &&
				element.childNodes[0].checked;
		});

		return inputs.map(function(element) {
			return element.childNodes[0].value;
		});
	};

	onOffice.multiselect = multiselect;

})();

(function () {
	var divs = document.getElementsByClassName('multiselect');
	var divsArray = [].slice.call(divs);
	for (var i in divsArray) {
		var element = divsArray[i];
		var values = {};
		var presetValues = [];

		try {
			valuesString = element.getAttribute('data-values');
			values = JSON.parse(valuesString);
			presetString = element.getAttribute('data-selected');
			if (presetString !== null) {
				presetValues = JSON.parse(presetString);
			}
		} catch (Error) {}

		var instance = new onOffice.multiselect(element, values, presetValues);
		var subElements = [].slice.call(element.children);
		var editButtonArray = subElements.filter(function(element) {
			return element.className === 'onoffice-multiselect-edit';
		});
		var button = editButtonArray.pop();
		button.onclick = (function(instance) {
			return function() {
				instance.show();
			};
		})(instance);
		element.onoffice_multiselect = instance;
	}
})();


