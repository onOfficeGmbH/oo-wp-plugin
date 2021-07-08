<?php

/**
 *
 *    Copyright (C) 2017 onOffice GmbH
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU Affero General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU Affero General Public License for more details.
 *
 *    You should have received a copy of the GNU Affero General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace onOffice\WPlugin\Model\FormModelBuilder;

use DI\Container;
use DI\ContainerBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use onOffice\SDK\onOfficeSDK;
use onOffice\WPlugin\API\APIClientCredentialsException;
use onOffice\WPlugin\DataFormConfiguration\DataFormConfiguration;
use onOffice\WPlugin\DataFormConfiguration\DataFormConfigurationFactory;
use onOffice\WPlugin\Field\Collection\FieldLoaderGeneric;
use onOffice\WPlugin\Field\Collection\FieldsCollectionBuilder;
use onOffice\WPlugin\Field\Collection\FieldsCollectionBuilderShort;
use onOffice\WPlugin\Model\FormModel;
use onOffice\WPlugin\Model\InputModel\InputModelDBFactory;
use onOffice\WPlugin\Model\InputModel\InputModelDBFactoryConfigForm;
use onOffice\WPlugin\Model\InputModelBase;
use onOffice\WPlugin\Model\InputModelBuilder\InputModelBuilderDefaultValue;
use onOffice\WPlugin\Model\InputModelDB;
use onOffice\WPlugin\Model\InputModelLabel;
use onOffice\WPlugin\Model\InputModelOption;
use onOffice\WPlugin\Record\RecordManagerReadForm;
use onOffice\WPlugin\Translation\FormTranslation;
use onOffice\WPlugin\Translation\ModuleTranslation;
use onOffice\WPlugin\Types\FieldsCollection;
use onOffice\WPlugin\Types\FieldTypes;
use onOffice\WPlugin\WP\InstalledLanguageReader;
use function __;
use function get_locale;
use function get_option;
use const ONOFFICE_DI_CONFIG_PATH;

/**
 *
 */

class FormModelBuilderDBForm
	extends FormModelBuilderDB
{
	/** @var string */
	private $_formType = null;

	/** @var array */
	private $_formModules = [];

	/** @var Container */
	private $_pContainer;

	/**
	 * @param Container $pContainer
	 */
	public function __construct(Container $pContainer)
	{
		$pConfigForm = new InputModelDBFactoryConfigForm();
		$pInputModelDBFactory = new InputModelDBFactory($pConfigForm);
		$this->_pContainer = $pContainer;
		$this->setInputModelDBFactory($pInputModelDBFactory);
	}

	/**
	 * @param string|array $module
	 * @param string $htmlType
	 * @return InputModelDB
	 * @throws DependencyException
	 * @throws NotFoundException
	 */
	public function createSortableFieldList($module, $htmlType)
	{
		$pInputModelFieldsConfig = $this->getInputModelDBFactory()->create(
			InputModelDBFactory::INPUT_FIELD_CONFIG, null, true);

		$pInputModelFieldsConfig->setHtmlType($htmlType);
		$pFieldsCollection = $this->getFieldsCollection();
		$fieldNames = [];

		if (is_array($module)) {
			$this->_formModules = $module;
			foreach ($module as $submodule) {
				$newFields = $pFieldsCollection->getFieldsByModule($submodule ?? '');
				$fieldNames = array_merge($fieldNames, $newFields);
			}
		} else {
			$this->_formModules = [$module];
			$fieldNames = $pFieldsCollection->getFieldsByModule($module);
		}

		$fieldNamesArray = [];
		$pFieldsCollectionUsedFields = new FieldsCollection;

		foreach ($fieldNames as $pField) {
			$fieldNamesArray[$pField->getName()] = $pField->getAsRow();
			$pFieldsCollectionUsedFields->addField($pField);
		}

		$pInputModelFieldsConfig->setValuesAvailable($fieldNamesArray);
		$fields = $this->getValue(DataFormConfiguration::FIELDS) ?? [];
		$pInputModelFieldsConfig->setValue($fields);

		$pModule = $this->getInputModelModule();
		$pReferenceIsRequired = $this->getInputModelIsRequired();
		$pReferenceIsAvailableOptions = $this->getInputModelIsAvailableOptions();
		$pInputModelFieldsConfig->addReferencedInputModel($pModule);
		$pInputModelFieldsConfig->addReferencedInputModel($this->getInputModelDefaultValue($pFieldsCollectionUsedFields));
		$pInputModelFieldsConfig->addReferencedInputModel($this->getInputModelDefaultValueLanguageSwitch());
		$pInputModelFieldsConfig->addReferencedInputModel($pReferenceIsRequired);
		$pInputModelFieldsConfig->addReferencedInputModel($pReferenceIsAvailableOptions);

		return $pInputModelFieldsConfig;
	}

	/**
	 * @return FieldsCollection
	 * @throws DependencyException
	 * @throws NotFoundException
	 */
	private function getFieldsCollection(): FieldsCollection
	{
		$pContainerBuilder = new ContainerBuilder;
		$pContainerBuilder->addDefinitions(ONOFFICE_DI_CONFIG_PATH);
		$pContainer = $pContainerBuilder->build();

		$pFieldsCollectionBuilder = $pContainer->get(FieldsCollectionBuilderShort::class);
		$pFieldsCollection = new FieldsCollection();

		$pFieldsCollectionBuilder
			->addFieldsAddressEstate($pFieldsCollection)
			->addFieldsSearchCriteria($pFieldsCollection)
			->addFieldsFormBackend($pFieldsCollection);
		return $pFieldsCollection;
	}

	/**
	 * @param string $category
	 * @param array $fieldNames
	 * @param string $categoryLabel
	 * @return InputModelDB
	 */
	public function createInputModelFieldsConfigByCategory($category, $fieldNames, $categoryLabel)
	{
		$pInputModelFieldsConfig = $this->getInputModelDBFactory()->create(
			InputModelDBFactory::INPUT_FIELD_CONFIG, $category, true);

		$pInputModelFieldsConfig->setHtmlType(InputModelBase::HTML_TYPE_CHECKBOX_BUTTON);
		$pInputModelFieldsConfig->setValuesAvailable($fieldNames);
		$pInputModelFieldsConfig->setId($category);
		$pInputModelFieldsConfig->setLabel($categoryLabel);
		$fields = $this->getValue(DataFormConfiguration::FIELDS, []);
		$pInputModelFieldsConfig->setValue($fields);

		return $pInputModelFieldsConfig;
	}

	/**
	 * @param string $pageSlug
	 * @param int $formId
	 * @return FormModel
	 * @throws Exception
	 */
	public function generate(string $pageSlug, $formId = null): FormModel
	{
		if ($this->_formType === null) {
			throw new Exception('formType must be set!');
		}

		$values = array();
		$values['fieldsRequired'] = array();
		$values['fieldsAvailableOptions'] = array();
		$pFactory = new DataFormConfigurationFactory($this->_formType);

		if ($formId !== null) {
			$pRecordReadManager = new RecordManagerReadForm();
			$pFactory->setIsAdminInterface(true);
			$values = $pRecordReadManager->getRowById($formId);
			$pDataFormConfiguration = $pFactory->loadByFormId($formId);
		} else {
			$pDataFormConfiguration = $pFactory->createEmpty();
		}

		$values[DataFormConfiguration::FIELDS] = array_keys($pDataFormConfiguration->getInputs());
		$values['fieldsRequired'] = $pDataFormConfiguration->getRequiredFields();
		$values['fieldsAvailableOptions'] = $pDataFormConfiguration->getAvailableOptionsFields();

		$this->setValues($values);
		$pFormModel = new FormModel();
		$pFormModel->setLabel(__('Form', 'onoffice-for-wp-websites'));
		$pFormModel->setGroupSlug('onoffice-form-settings');
		$pFormModel->setPageSlug($pageSlug);

		return $pFormModel;
	}

	/**
	 * @return InputModelDB
	 */
	public function createInputModelName()
	{
		$labelName = __('Form Name', 'onoffice-for-wp-websites');

		$pInputModelName = $this->getInputModelDBFactory()->create
			(InputModelDBFactoryConfigForm::INPUT_FORM_NAME, null);
		$pInputModelName->setPlaceholder($labelName);
		$pInputModelName->setHtmlType(InputModelOption::HTML_TYPE_TEXT);
		$pInputModelName->setValue($this->getValue($pInputModelName->getField()));

		return $pInputModelName;
	}

	/**
	 * @return InputModelLabel
	 */
	public function createInputModelFormType()
	{
		$formType = $this->getFormType();
		$pFormTranslation = new FormTranslation();
		$translation = $pFormTranslation->getPluralTranslationForForm($formType, 1);
		$pInputModeLabel = new InputModelLabel(__('Type of Form: ', 'onoffice-for-wp-websites'), $translation);

		$pInputModeLabel->setHtmlType(InputModelBase::HTML_TYPE_LABEL);

		return $pInputModeLabel;
	}

	/**
	 * @return InputModelLabel
	 */
	public function createInputModelEmbedCode()
	{
		$pConfig = new InputModelDBFactoryConfigForm();
		$config = $pConfig->getConfig();
		$name = $config[InputModelDBFactoryConfigForm::INPUT_FORM_NAME]
			[InputModelDBFactoryConfigForm::KEY_FIELD];
		$formName = $this->getValue($name);

		$code = '[oo_form form="'.$formName.'"]';
		$pInputModeLabel = new InputModelLabel(__(', Embed Code: ', 'onoffice-for-wp-websites'), $code);
		$pInputModeLabel->setHtmlType(InputModelBase::HTML_TYPE_LABEL);
		$pInputModeLabel->setValueEnclosure(InputModelLabel::VALUE_ENCLOSURE_CODE);

		return $pInputModeLabel;
	}

	/**
	 * @return InputModelDB
	 */
	public function createInputModelRecipient()
	{
		$labelRecipient = __('Recipient\'s E-Mail Address', 'onoffice-for-wp-websites');
		$selectedRecipient = $this->getValue('recipient');

		$pInputModelFormRecipient = $this->getInputModelDBFactory()->create
			(InputModelDBFactoryConfigForm::INPUT_FORM_RECIPIENT, $labelRecipient);
		$pInputModelFormRecipient->setHtmlType(InputModelOption::HTML_TYPE_TEXT);
		$pInputModelFormRecipient->setValue($selectedRecipient);
		$pInputModelFormRecipient->setPlaceholder(__('john.doe@example.com', 'onoffice-for-wp-websites'));

		return $pInputModelFormRecipient;
	}

	/**
	 * @return InputModelDB
	 * @throws Exception
	 */
	public function createInputModelCaptchaRequired(): InputModelDB
	{
		$addition = '';

		if (get_option('onoffice-settings-captcha-sitekey', '') === '') {
			$addition = __('(won\'t work until set up globally)', 'onoffice-for-wp-websites');
		}

		/* translators: %s will be replaced with the translation of
			'(won't work until set up globally)', if captcha hasn't been set up appropriately yet,
			or blank otherwise. */
		$labelRequiresCaptcha = sprintf(__('Requires Captcha %s', 'onoffice-for-wp-websites'), $addition);
		$selectedValue = $this->getValue('captcha', false);
		$pInputModelFormRequiresCaptcha = $this->generateGenericCheckbox($labelRequiresCaptcha,
			InputModelDBFactoryConfigForm::INPUT_FORM_REQUIRES_CAPTCHA, $selectedValue);

		return $pInputModelFormRequiresCaptcha;
	}

	/**
	 * @return InputModelDB
	 */
	public function createInputModelResultLimit()
	{
		$labelResultLimit = __('Result Limit', 'onoffice-for-wp-websites');
		$pInputModelFormLimitResult = $this->getInputModelDBFactory()->create
			(InputModelDBFactoryConfigForm::INPUT_FORM_LIMIT_RESULTS, $labelResultLimit);
		$field = $pInputModelFormLimitResult->getField();
		$selectedValue = $this->getValue($field);
		$pInputModelFormLimitResult->setHtmlType(InputModelOption::HTML_TYPE_TEXT);
		$pInputModelFormLimitResult->setValue($selectedValue);

		return $pInputModelFormLimitResult;
	}

    /**
     * @return InputModelDB
     */
    public function createInputModelContactType()
    {
        $labelContactType = __('Contact Type', 'onoffice-for-wp-websites');
        $pInputModelFormContactType = $this->getInputModelDBFactory()->create
        (InputModelDBFactoryConfigForm::INPUT_FORM_CONTACT_TYPE, $labelContactType);
        $field = $pInputModelFormContactType->getField();
        $pInputModelFormContactType->setHtmlType(InputModelOption::HTML_TYPE_SELECT);
        $availableContactType = array('' => 'No contact type') + $this->getDataContactType(onOfficeSDK::MODULE_ADDRESS);
        $pInputModelFormContactType->setValuesAvailable($availableContactType);
        $selectedValue = $this->getValue($field);
        $pInputModelFormContactType->setValue($selectedValue);

        return $pInputModelFormContactType;
    }

    public function getDataContactType($module)
    {
        try {
            $pFieldLoader = $this->_pContainer->get(FieldLoaderGeneric::class);
            $pFieldCollectionAddressEstate = $this->_pContainer->get(FieldsCollectionBuilder::class)
                ->buildFieldsCollection($pFieldLoader);
            $fields = $pFieldCollectionAddressEstate->getFieldsByModule($module);
            $result = [];
            if (!empty($fields['ArtDaten']->getPermittedvalues())) {
                foreach ($fields['ArtDaten']->getPermittedvalues() as $field => $type) {
                    $result[$field] = $field;
                }
            }

            return $result;
        } catch (APIClientCredentialsException $pCredentialsException) {
            return [];
        }

    }

	/**
	 * @return InputModelDB
	 */
	public function getInputModelIsRequired()
	{
		$pInputModelFactoryConfig = new InputModelDBFactoryConfigForm();
		$pInputModelFactory = new InputModelDBFactory($pInputModelFactoryConfig);
		$label = __('Required', 'onoffice-for-wp-websites');
		$type = InputModelDBFactoryConfigForm::INPUT_FORM_REQUIRED;
		/* @var $pInputModel InputModelDB */
		$pInputModel = $pInputModelFactory->create($type, $label, true);
		$pInputModel->setHtmlType(InputModelBase::HTML_TYPE_CHECKBOX);
		$pInputModel->setValueCallback(array($this, 'callbackValueInputModelIsRequired'));

		return $pInputModel;
	}

	/**
	 * @return InputModelDB
	 */
	public function getInputModelIsAvailableOptions()
	{
		$pInputModelFactoryConfig = new InputModelDBFactoryConfigForm();
		$pInputModelFactory = new InputModelDBFactory($pInputModelFactoryConfig);
		$label = __('Reduce values according to selected filter', 'onoffice-for-wp-websites');
		$type = InputModelDBFactoryConfigForm::INPUT_FORM_AVAILABLE_OPTIONS;
		/* @var $pInputModel InputModelDB */
		$pInputModel = $pInputModelFactory->create($type, $label, true);
		$pInputModel->setHtmlType(InputModelBase::HTML_TYPE_CHECKBOX);
		$pInputModel->setValueCallback(array($this, 'callbackValueInputModelIsAvailableOptions'));

		return $pInputModel;
	}

	/**
	 * @param FieldsCollection $pFieldsCollection
	 * @return InputModelDB
	 * @throws DependencyException
	 * @throws NotFoundException
	 */
	private function getInputModelDefaultValue(FieldsCollection $pFieldsCollection): InputModelDB
	{
		$pInputModelBuilder = $this->_pContainer->get(InputModelBuilderDefaultValue::class);
		return $pInputModelBuilder->createInputModelDefaultValue($pFieldsCollection, $this->getValue('defaultvalue', []));
	}


	/**
	 * @return InputModelDB
	 */
	public function getInputModelDefaultValueLanguageSwitch(): InputModelDB
	{
		$pInputModel = new InputModelDB('defaultvalue_newlang', __('Add language', 'onoffice-for-wp-websites'));
		$pInputModel->setTable('language');
		$pInputModel->setField('language');

		$pLanguageReader = new InstalledLanguageReader;
		$languages = ['' => __('Choose Language', 'onoffice-for-wp-websites')]
			+ $pLanguageReader->readAvailableLanguageNamesUsingNativeName();
		$pInputModel->setValuesAvailable(array_diff_key($languages, [get_locale() => []]));
		$pInputModel->setValueCallback(function(InputModelDB $pInputModel, string $key, string $type = null) {
			$pInputModel->setHtmlType(InputModelBase::HTML_TYPE_HIDDEN);
			$pInputModel->setLabel('');

			if (FieldTypes::isStringType($type ?? '')) {
				$pInputModel->setHtmlType(InputModelBase::HTML_TYPE_SELECT);
				$pInputModel->setLabel(__('Add language', 'onoffice-for-wp-websites'));
			}
		});

		return $pInputModel;
	}

	/**
	 * @param string $label
	 * @param string $type
	 * @param bool $checked
	 * @return InputModelDB
	 * @throws Exception
	 */
	private function generateGenericCheckbox(string $label, string $type, bool $checked):
		InputModelDB
	{
		$pInputModel = $this->getInputModelDBFactory()->create($type, $label);
		if ($pInputModel === null) {
			throw new Exception('Unknown input model type');
		}

		$pInputModel->setHtmlType(InputModelOption::HTML_TYPE_CHECKBOX);
		$pInputModel->setValue((int)$checked);
		$pInputModel->setValuesAvailable(1);
		return $pInputModel;
	}

	/**
	 * @param InputModelBase $pInputModel
	 * @param string $key Name of input
	 */
	public function callbackValueInputModelIsRequired(InputModelBase $pInputModel, $key)
	{
		$fieldsRequired = $this->getValue('fieldsRequired');
		$value = in_array($key, $fieldsRequired);
		$pInputModel->setValue($value);
		$pInputModel->setValuesAvailable($key);
	}

	/**
	 * @param InputModelBase $pInputModel
	 * @param string $key Name of input
	 */
	public function callbackValueInputModelIsAvailableOptions(InputModelBase $pInputModel, string $key)
	{
		$fieldsAvOpt = $this->getValue('fieldsAvailableOptions');
		$value = in_array($key, $fieldsAvOpt);
		$pInputModel->setValue($value);
		$pInputModel->setValuesAvailable($key);
	}

	/**
	 * @return InputModelDB
	 */
	public function getInputModelModule()
	{
		$pInputModelFactoryConfig = new InputModelDBFactoryConfigForm();
		$pInputModelFactory = new InputModelDBFactory($pInputModelFactoryConfig);
		$label = __('Module', 'onoffice-for-wp-websites');
		$type = InputModelDBFactoryConfigForm::INPUT_FORM_MODULE;
		$pInputModel = $pInputModelFactory->create($type, $label, true);
		$pInputModel->setHtmlType(InputModelBase::HTML_TYPE_HIDDEN);
		$pInputModel->setValueCallback(function(InputModelBase $pInputModel, string $key) {
			$this->callbackValueInputModelModule($pInputModel, $key);
		});

		return $pInputModel;
	}

	/**
	 * @param InputModelBase $pInputModel
	 * @param string $key
	 * @throws DependencyException
	 * @throws NotFoundException
	 */
	private function callbackValueInputModelModule(InputModelBase $pInputModel, string $key)
	{
		$module = '';
		$pFieldsCollection = $this->getFieldsCollection();
		foreach ($this->_formModules as $formModule) {
			if ($pFieldsCollection->containsFieldByModule($formModule ?? '', $key)) {
				$module = $formModule;
				break;
			}
		}
		$moduleTranslated = __(ModuleTranslation::getLabelSingular($module ?? ''), 'onoffice-for-wp-websites');
		$label = sprintf(__('Module: %s', 'onoffice-for-wp-websites'), $moduleTranslated);
		$pInputModel->setLabel($label);
		$pInputModel->setValue($module);
	}

	/** @return string */
	public function getFormType()
		{ return $this->_formType; }

	/** @param string $formType */
	public function setFormType($formType)
		{ $this->_formType = $formType; }
}
