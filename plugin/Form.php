<?php

/**
 *
 *    Copyright (C) 2016-2019 onOffice GmbH
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

namespace onOffice\WPlugin;

use DI\Container;
use DI\ContainerBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use onOffice\SDK\onOfficeSDK;
use onOffice\WPlugin\Controller\EstateTitleBuilder;
use onOffice\WPlugin\Controller\GeoPositionFieldHandler;
use onOffice\WPlugin\DataFormConfiguration\DataFormConfiguration;
use onOffice\WPlugin\DataFormConfiguration\DataFormConfigurationApplicantSearch;
use onOffice\WPlugin\DataFormConfiguration\DataFormConfigurationContact;
use onOffice\WPlugin\DataFormConfiguration\DataFormConfigurationFactory;
use onOffice\WPlugin\DataFormConfiguration\UnknownFormException;
use onOffice\WPlugin\Field\Collection\FieldsCollectionBuilderShort;
use onOffice\WPlugin\Field\Collection\FieldsCollectionConfiguratorForm;
use onOffice\WPlugin\Field\CompoundFieldsFilter;
use onOffice\WPlugin\Field\DefaultValue\ModelToOutputConverter\DefaultValueModelToOutputConverter;
use onOffice\WPlugin\Field\DistinctFieldsHandler;
use onOffice\WPlugin\Field\UnknownFieldException;
use onOffice\WPlugin\Record\RecordManagerFactory;
use onOffice\WPlugin\Types\Field;
use onOffice\WPlugin\Types\FieldsCollection;
use onOffice\WPlugin\Types\FieldTypes;
use onOffice\WPlugin\WP\WPQueryWrapper;
use function __;
use function esc_html;
use const ONOFFICE_DI_CONFIG_PATH;

/**
 *
 */

class Form
{
	/** contact form */
	const TYPE_CONTACT = 'contact';

	/** owner form */
	const TYPE_OWNER = 'owner';

	/** applicant form (with search criteria) */
	const TYPE_INTEREST = 'interest';

	/** applicant-search form */
	const TYPE_APPLICANT_SEARCH = 'applicantsearch';

	/** @var int */
	private $_formNo = null;

	/** @var FormData */
	private $_pFormData;

	/** @var array */
	private $_genericSettings = [];

	/** @var int */
	private $_countAbsoluteResults = 0;

	/** @var FieldsCollection */
	private $_pFieldsCollection;

	/** @var Container */
	private $_pContainer;

	/**
	 * @param string $formName
	 * @param string $type
	 * @param Container|null $pContainer Manual instance creation possible
	 * @throws DependencyException
	 * @throws NotFoundException
	 * @throws UnknownFieldException
	 * @throws UnknownFormException
	 */
	public function __construct(string $formName, string $type, Container $pContainer = null)
	{
		$this->setGenericSetting('submitButtonLabel', __('Submit', 'onoffice-for-wp-websites'));
		$this->setGenericSetting('formId', 'onoffice-form');
		$this->_pContainer = $pContainer ?? $this->buildContainer();
		$pFieldsCollection = new FieldsCollection();
		$pFieldBuilderShort = $this->_pContainer->get(FieldsCollectionBuilderShort::class);
		$pFieldBuilderShort
			->addFieldsAddressEstate($pFieldsCollection)
			->addFieldsSearchCriteria($pFieldsCollection)
			->addFieldsFormFrontend($pFieldsCollection)
			->addCustomLabelFieldsFormFrontend($pFieldsCollection, $formName);

		$pFormPost = FormPostHandler::getInstance($type);
		FormPost::incrementFormNo();
		$this->_formNo = $pFormPost->getFormNo();

		$pFormConfigFactory = $this->_pContainer->get(DataFormConfigurationFactory::class);
		$pFormConfig = $pFormConfigFactory->loadByFormName($formName);
		$this->_pFieldsCollection = $this->buildFieldsCollectionForForm($pFieldsCollection, $type, $pFormConfig);
		try {
			$this->_pFormData = $pFormPost->getFormDataInstance($formName, $this->_formNo);
			$this->setCountAbsoluteResults($pFormPost->getAbsolutCountResults());
		} catch (UnknownFormException $pE) {
			// no form sent
			$pRecordManagerFactory = $this->_pContainer->get(RecordManagerFactory::class);
			$pGeoPositionDefaults = new GeoPositionFieldHandler($pRecordManagerFactory);
			$pGeoPositionDefaults->readValues($pFormConfig);

			$this->_pFormData = new FormData($pFormConfig, $this->_formNo);
			$this->_pFormData->setRequiredFields($this->getRequiredFields());
			$this->_pFormData->setFormtype($pFormConfig->getFormType());
			$this->_pFormData->setFormSent(false);
			$this->_pFormData->setValues
				(['range' => $pGeoPositionDefaults->getRadiusValue()] + $this->getDefaultValues());
		}
	}

	/**
	 * @param FieldsCollection $pFieldsCollection
	 * @param string $type
	 * @param DataFormConfiguration $pConfiguration
	 * @return FieldsCollection
	 * @throws DependencyException
	 * @throws NotFoundException
	 * @throws UnknownFieldException
	 */
	private function buildFieldsCollectionForForm(
		FieldsCollection $pFieldsCollection,
		string $type,
		DataFormConfiguration $pConfiguration): FieldsCollection
	{
		/** @var FieldsCollectionConfiguratorForm $pFieldsCollectionConfiguratorForm */
		$pFieldsCollectionConfiguratorForm = $this->_pContainer->get(FieldsCollectionConfiguratorForm::class);

		if ($pConfiguration instanceof DataFormConfigurationApplicantSearch) {
			/** @var DistinctFieldsHandler $pDistinctFieldsHandler */
			$pDistinctFieldsHandler = $this->_pContainer->get(DistinctFieldsHandler::class);
			return $pDistinctFieldsHandler->modifyFieldsCollectionForSearchCriteria
				($pConfiguration, $pFieldsCollection);
		}
		return $pFieldsCollectionConfiguratorForm->buildForFormType($pFieldsCollection, $type);
	}

	/**
	 * @return Container
	 * @throws \Exception
	 */
	private function buildContainer(): Container
	{
		$pContainerBuilder = new ContainerBuilder;
		$pContainerBuilder->addDefinitions(ONOFFICE_DI_CONFIG_PATH);
		return $pContainerBuilder->build();
	}

	/**
	 *
	 * @param string $field
	 * @return string
	 * @throws DependencyException
	 * @throws NotFoundException
	 */

	private function getModuleOfField(string $field)
	{
		return $this->getInputFields()[$field] ?? null;
	}

	/**
	 *
	 * @return array
	 * @throws DependencyException
	 * @throws NotFoundException
	 */

	public function getInputFields(): array
	{
		/** @var CompoundFieldsFilter $pCompoundFieldsFilter */
		$pCompoundFieldsFilter = $this->_pContainer->get(CompoundFieldsFilter::class);
		$inputs = $this->getDataFormConfiguration()->getInputs();

		$inputsSplitCompound = $pCompoundFieldsFilter->mergeAssocFields($this->_pFieldsCollection, $inputs);
		$inputsAll = array_merge($inputsSplitCompound, $this->getFormSpecificFields());
		$inputsAll = $this->filterActiveInputFields($inputsAll);

		return $inputsAll;
	}


	/**
	 * @param $inputs
	 * @return array
	 *
	 */

	private function filterActiveInputFields($inputs): array
	{
		$activeInputs = [];

		foreach ($inputs as $name => $module) {
			if ($this->_pFieldsCollection->containsFieldByModule($module, $name)) {
				$activeInputs[$name] = $module;
			}
		}

		return $activeInputs;
	}


	/**
	 *
	 * @return array
	 *
	 */

	private function getFormSpecificFields(): array
	{
		$newFields = [];
		$pDataFormConfiguration = $this->getDataFormConfiguration();

		if ($pDataFormConfiguration->getFormType() === self::TYPE_CONTACT &&
			$pDataFormConfiguration instanceof DataFormConfigurationContact) {
			if ($pDataFormConfiguration->getNewsletterCheckbox()) {
				$newFields['newsletter'] = onOfficeSDK::MODULE_ADDRESS;
			}
		}

		return $newFields;
	}

	/**
	 *
	 * @return array
	 * @throws DependencyException
	 * @throws NotFoundException
	 */

	public function getRequiredFields(): array
	{
		/** @var CompoundFieldsFilter $pCompoundFieldsFilter */
		$pCompoundFieldsFilter = $this->_pContainer->get(CompoundFieldsFilter::class);
		$requiredFields = $this->getDataFormConfiguration()->getRequiredFields();
		$requiredFieldsSplitCompound = $pCompoundFieldsFilter->mergeFields
			($this->_pFieldsCollection, $requiredFields);
		return $this->executeGeoPositionFix($requiredFieldsSplitCompound);
	}


	/**
	 *
	 * @param array $requiredFields
	 * @return array
	 *
	 */

	private function executeGeoPositionFix(array $requiredFields): array
	{
		if (in_array(GeoPosition::FIELD_GEO_POSITION, $requiredFields))	{
			$pGeoPosition = new GeoPositionFieldHandler();
			$pGeoPosition->readValues($this->getDataFormConfiguration());
			$geoPositionFields = $pGeoPosition->getActiveFields();
			unset($requiredFields[GeoPosition::FIELD_GEO_POSITION]);
			$requiredFields = array_merge($requiredFields, $geoPositionFields);
		}

		return $requiredFields;
	}

	/**
	 *
	 * @param string $field
	 * @return bool
	 * @throws DependencyException
	 * @throws NotFoundException
	 */

	public function isRequiredField(string $field): bool
	{
		$requiredFields = $this->getRequiredFields();
		$pGeoPosition = new GeoPositionFieldHandler();
		$pGeoPosition->readValues($this->getDataFormConfiguration());

		if (in_array($field, $pGeoPosition->getActiveFields()) &&
			in_array(GeoPosition::FIELD_GEO_POSITION, $requiredFields, true)) {
			return true;
		}

		return in_array($field, $requiredFields, true);
	}

	/**
	 * @return DataFormConfiguration
	 */
	private function getDataFormConfiguration(): DataFormConfiguration
	{
		return $this->_pFormData->getDataFormConfiguration();
	}


	/**
	 *
	 * @return string
	 *
	 */

	public function getFormStatus()
	{
		return $this->_pFormData->getStatus();
	}

	/**
	 *
	 * @param string $field
	 * @param bool $raw
	 *
	 * @return string
	 *
	 */

	public function getFieldLabel(string $field, bool $raw = false): string
	{
		$module = $this->getModuleOfField($field);
		$label = $this->_pFieldsCollection->getFieldByModuleAndName($module, $field)->getLabel();

		if (false === $raw) {
			$label = esc_html($label);
		}

		return $label;
	}


	/**
	 *
	 * @param string $field
	 * @return bool
	 *
	 */

	public function isSearchcriteriaField(string $field): bool
	{
		$module = $this->getModuleOfField($field);
		return $module === onOfficeSDK::MODULE_SEARCHCRITERIA;
	}

	/**
	 *
	 * @param string $field
	 * @return bool
	 *
	 */

	public function inRangeSearchcriteriaInfos(string $field): bool
	{
		$module = $this->getModuleOfField($field);

		return $module === onOfficeSDK::MODULE_SEARCHCRITERIA &&
			$this->_pFieldsCollection->getFieldByModuleAndName($module, $field)->getIsRangeField();
	}


	/**
	 *
	 * @return array
	 *
	 */

	public function getSearchcriteriaRangeInfos(): array
	{
		$allFields = $this->_pFieldsCollection->getAllFields();
		$rangeInfos = [];

		foreach ($allFields as $pField) {
			if ($pField->getModule() === onOfficeSDK::MODULE_SEARCHCRITERIA &&
				$pField->getIsRangeField()) {
				$rangeInfos[$pField->getName()] = $pField->getRangeFieldTranslations();
			}
		}

		return $rangeInfos;
	}


	/**
	 *
	 * @return array
	 *
	 */

	public function getUmkreisFields(): array
	{
		$result = [];
		$searchCriteriaFields = $this->_pFieldsCollection
			->getFieldsByModule(onOfficeSDK::MODULE_SEARCHCRITERIA);

		foreach ($searchCriteriaFields as $pField) {
			/* @var $pField Field */
			if ($pField->getIsRangeField()) {
				$result[$pField->getName()] = $pField->getAsRow();
			}
		}

		return $result;
	}


	/**
	 *
	 * @param string $field
	 * @return array
	 *
	 */

	public function getSearchcriteriaRangeInfosForField(string $field): array
	{
		$returnValues = [];
		$module = $this->getModuleOfField($field);

		if ($module === onOfficeSDK::MODULE_SEARCHCRITERIA) {
			$returnValues = $this->_pFieldsCollection
				->getFieldByModuleAndName($module, $field)
				->getRangeFieldTranslations();
		}

		return $returnValues;
	}

	/**
	 * @param string $field
	 * @param bool $raw
	 * @return array
	 * @throws DependencyException
	 * @throws UnknownFieldException
	 * @throws NotFoundException
	 */
	public function getPermittedValues($field, $raw = false): array
	{
		$module = $this->getModuleOfField($field);
		$result = $this->_pFieldsCollection
			->getFieldByModuleAndName($module, $field)
			->getPermittedvalues();

		if (!$raw) {
			$result = $this->escapePermittedValues($result);
		}
		return $result;
	}

	/**
	 *
	 * @param string $field
	 * @return string
	 *
	 */

	public function getFieldType($field): string
	{
		$module = $this->getModuleOfField($field);
		return $this->_pFieldsCollection->getFieldByModuleAndName($module, $field)->getType();
	}


	/**
	 *
	 * @param array $keyValues
	 * @return array
	 *
	 */

	private function escapePermittedValues(array $keyValues): array
	{
		$result = [];

		foreach ($keyValues as $key => $value) {
			$result[esc_html($key)] = esc_html($value);
		}

		return $result;
	}


	/**
	 *
	 * @param string $field
	 * @param bool $raw
	 * @return string
	 *
	 */

	public function getFieldValue($field, $raw = false)
	{
		$values = $this->_pFormData->getValues();
		$fieldValue = $values[$field] ?? '';

		if ($raw) {
			return $fieldValue;
		}

		return esc_html($fieldValue);
	}

	/**
	 * @return array
	 * @throws DependencyException
	 * @throws NotFoundException
	 */
	private function getDefaultValues(): array
	{
		/** @var DefaultValueModelToOutputConverter $pDefaultValueRead */
		$pDefaultValueRead = $this->_pContainer->get(DefaultValueModelToOutputConverter::class);
		$formId = $this->getDataFormConfiguration()->getId();
		$values = [];

		foreach ($this->_pFieldsCollection->getAllFields() as $pField) {
			$value = $pDefaultValueRead->getConvertedField($formId, $pField);
			$values[$pField->getName()] = $value[0] ?? '';

			if ($pField->getIsRangeField()) {
				$values[$pField->getName().'__von'] = $value['min'] ?? '';
				$values[$pField->getName().'__bis'] = $value['max'] ?? '';
			} elseif ($pField->getType() === FieldTypes::FIELD_TYPE_MULTISELECT) {
				$values[$pField->getName()] = $value;
			} elseif (FieldTypes::isStringType($pField->getType())) {
				$values[$pField->getName()] = ($value['native'] ?? '') ?: (array_shift($value) ?? '');
			}
		}
		return array_filter($values);
	}

	/**
	 *
	 * @param string $field
	 * @return bool
	 *
	 */

	public function isMissingField($field): bool
	{
		return $this->_pFormData->getFormSent() &&
			in_array($field, $this->_pFormData->getMissingFields(), true);
	}


	/**
	 *
	 * @return string
	 *
	 */

	public function getEstateContextLabel(): string
	{
		$result = '';
		$estateId = (int)(new WPQueryWrapper)->getWPQuery()->get('estate_id', 0);
		if ($this->getDataFormConfiguration()->getShowEstateContext() && $estateId !== 0) {
			$pEstateTitleBuilder = new EstateTitleBuilder();
			/* translators: %1$s is the estate title, %5$s is the estate ID */
			$format = __('Your Inquiry about Real Estate “%1$s” (%5$s)', 'onoffice-for-wp-websites');
			$result = $pEstateTitleBuilder->buildTitle($estateId, $format);
		}
		return esc_html($result);
	}


	/**
	 *
	 * @return int
	 *
	 */

	public function getFormNo()
	{
		return esc_html($this->_formNo);
	}


	/**
	 *
	 * @return string
	 *
	 */

	public function getFormId()
	{
		return esc_html($this->getDataFormConfiguration()->getFormName());
	}


	/**
	 *
	 * @return bool
	 *
	 */

	public function needsReCaptcha(): bool
	{
		return $this->getDataFormConfiguration()->getCaptcha();
	}


	/**
	 *
	 * @param string $settingName
	 * @return string
	 *
	 */

	public function getGenericSetting(string $settingName)
	{
		return $this->_genericSettings[$settingName] ?? null;
	}


	/**
	 *
	 * @param string $settingName
	 * @param string $value
	 *
	 */

	public function setGenericSetting(string $settingName, $value)
	{
		$this->_genericSettings[$settingName] = $value;
	}


	/** @return array */
	public function getResponseFieldsValues()
		{ return $this->_pFormData->getResponseFieldsValues(); }


	/** @return int */
	public function getCountAbsolutResults(): int
		{ return $this->_countAbsoluteResults; }


	/** @var int $countAbsolute */
	private function setCountAbsoluteResults(int $countAbsolute)
		{ $this->_countAbsoluteResults = $countAbsolute; }
}
