onOffice = onOffice || {};
onOffice.custom_labels_inputs_converted = onOffice.custom_labels_inputs_converted || [];

// polyfill
if (window.NodeList && !NodeList.prototype.forEach) {
    NodeList.prototype.forEach = Array.prototype.forEach;
}

onOffice.custom_labels_input_converter = function () {

    var predefinedValues = onOffice_loc_settings.customlabels || {};

    // plaintext
    document.querySelectorAll('select[name=language-custom-label-language].onoffice-input').forEach(function (element) {
        element.backupLanguageSelection = {};
        var mainInput = element.parentElement.parentElement
            .querySelector('input[name^=oopluginfieldconfigformtranslatedlabels-value].onoffice-input');
        var fieldname = element.parentElement.parentElement.parentElement
            .querySelector('span.menu-item-settings-name').textContent;
        if (onOffice.custom_labels_inputs_converted.indexOf(fieldname) !== -1) {
            return;
        }
        onOffice.custom_labels_inputs_converted.push(fieldname);
        mainInput.name = 'customlabel-lang[' + fieldname + '][native]';

        (function () {
            if (predefinedValues[fieldname] !== undefined) {
                for (var lang in predefinedValues[fieldname]) {
                    var relevantOption = element.querySelector('option[value=' + lang + ']');
                    if (lang !== 'native') {
                        var clone = generateClone(mainInput, lang);
                        var label = generateLabel(relevantOption.text || '', clone);
                        var deleteButton = generateDeleteButton(element, lang);
                        var paragraph = generateParagraph(label, clone, deleteButton);
                        mainInput.parentNode.parentNode.insertBefore(paragraph, element.parentNode);
                        element.backupLanguageSelection[lang] = relevantOption;
                        element.options[relevantOption.index] = null;
                    }

                    var targetInput = element.parentElement.parentElement.querySelector(
                        'input[name="customlabel-lang[' + fieldname + '][' + lang + ']"]');
                    targetInput.value = predefinedValues[fieldname][lang];
                }
            }
        })();

        element.addEventListener('change', function (event) {
            var value = event.srcElement.value || '';

            if (value !== '') {
                var clone = generateClone(mainInput, value);
                var label = generateLabel(event.srcElement.selectedOptions[0].text, clone);
                var deleteButton = generateDeleteButton(event.srcElement, value);
                var paragraph = generateParagraph(label, clone, deleteButton);

                element.backupLanguageSelection[event.srcElement.selectedOptions[0].value] =
                    event.srcElement.selectedOptions[0];
                event.srcElement.options[event.srcElement.selectedIndex] = null;

                mainInput.parentNode.parentNode.insertBefore(paragraph, event.srcElement.parentNode);
            }
        });

        function generateClone(mainInput, language) {
            var clone = mainInput.cloneNode(true);
            clone.id = 'customlabel-lang-' + language;
            clone.name = 'customlabel-lang[' + fieldname + '][' + language + ']';
            clone.style.width = '100%';
            clone.style.marginLeft = '20px';
            clone.value = '';
            return clone;
        }

        function generateLabel(labelText, clone) {
            var label = document.createElement('label');
            label.classList = ['howto'];
            label.htmlFor = clone.id;
            label.style.minWidth = 'min-content';
            label.textContent = onOffice_loc_settings.label_custom_label.replace('%s', labelText);
            return label;
        }

        function generateDeleteButton(srcElement, language) {
            var deleteButton = document.createElement('span');
            deleteButton.id = 'deleteButtonLang-' + language;
            deleteButton.className = 'dashicons dashicons-dismiss deleteButtonLang';
            deleteButton.targetLanguage = language;
            deleteButton.style.display = 'block';
            deleteButton.style.verticalAlign = 'middle';

            deleteButton.addEventListener('click', function (deleteEvent) {
                var restoreValue = element.backupLanguageSelection[deleteEvent.srcElement.targetLanguage];
                srcElement.options.add(restoreValue);
                srcElement.selectedIndex = 0;
                deleteEvent.srcElement.parentElement.remove();
            });
            return deleteButton;
        }

        function generateParagraph(label, clone, deleteButton) {
            var paragraph = document.createElement('p');
            paragraph.classList = ['wp-clearfix'];
            paragraph.style.display = 'inline-flex';
            paragraph.style.width = '100%';
            paragraph.appendChild(label);
            paragraph.appendChild(clone);
            paragraph.appendChild(deleteButton);
            return paragraph;
        }
    });
};

onOffice.custom_labels_input_converter();

