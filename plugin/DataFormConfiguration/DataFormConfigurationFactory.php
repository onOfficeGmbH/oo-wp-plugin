<?php

/**
 *
 *    Copyright (C) 2017-2019 onOffice GmbH
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

namespace onOffice\WPlugin\DataFormConfiguration;

use onOffice\WPlugin\Controller\GeoPositionFieldHandler;
use onOffice\WPlugin\DataFormConfiguration;
use onOffice\WPlugin\Form;
use onOffice\WPlugin\GeoPosition;
use onOffice\WPlugin\Record\RecordManagerReadForm;

/**
 *
 */

class DataFormConfigurationFactory
{
	/** @var string */
	private $_type = null;

	/** @var RecordManagerReadForm */
	private $_pRecordManagerRead = null;

	/** @var GeoPositionFieldHandler */
	private $_pGeoPositionFieldHandler = null;

	/** @var bool */
	private $_isAdminInterface = false;


	/** @var array */
	private $_formClassMapping = [
		Form::TYPE_CONTACT => DataFormConfigurationContact::class,
		Form::TYPE_OWNER => DataFormConfigurationOwner::class,
		Form::TYPE_INTEREST => DataFormConfigurationInterest::class,
		Form::TYPE_APPLICANT_SEARCH => DataFormConfigurationApplicantSearch::class,
	];


	/**
	 *
	 * @param string $type Optional when loading by ID/name
	 * @param RecordManagerReadForm $pRecordManagerReadForm
	 *
	 */

	public function __construct(string $type = null,
		RecordManagerReadForm $pRecordManagerReadForm = null,
		GeoPositionFieldHandler $pGeoPositionFieldHandler = null)
	{
		$this->_type = $type;
		$this->_pRecordManagerRead = $pRecordManagerReadForm ?? new RecordManagerReadForm();
		$this->_pGeoPositionFieldHandler = $pGeoPositionFieldHandler ?? new GeoPositionFieldHandler();
	}


	/**
	 *
	 * @param bool $adminInterface
	 *
	 */

	public function setIsAdminInterface(bool $adminInterface)
	{
		$this->_isAdminInterface = $adminInterface;
	}


	/**
	 *
	 * @return bool
	 *
	 */

	public function getIsAdminInterface(): bool
	{
		return $this->_isAdminInterface;
	}


	/**
	 *
	 * @param bool $setDefaultFields
	 * @throws UnknownFormException
	 * @return DataFormConfiguration\DataFormConfiguration
	 *
	 */

	public function createEmpty(bool $setDefaultFields = true)
	{
		if (!isset($this->_formClassMapping[$this->_type])) {
			throw new UnknownFormException;
		}

		$class = $this->_formClassMapping[$this->_type];
		/* @var $pConfig DataFormConfiguration */
		$pConfig = new $class;
		$pConfig->setFormType($this->_type);

		if ($setDefaultFields) {
			$pConfig->setDefaultFields();
		}

		return $pConfig;
	}


	/**
	 *
	 * @param int $formId
	 * @return DataFormConfiguration\DataFormConfiguration
	 *
	 */

	public function loadByFormId(int $formId)
	{
		$rowMain = $this->_pRecordManagerRead->getRowById($formId);
		$this->_type = $rowMain['form_type'];
		$pConfig = $this->createByRow($rowMain);
		return $pConfig;
	}

	/**
	 * @param string $name
	 * @return DataFormConfiguration\DataFormConfiguration
	 * @throws UnknownFormException
	 */
	public function loadByFormName(string $name)
	{
		$rowMain = $this->_pRecordManagerRead->getRowByName($name);
		$this->_type = $rowMain['form_type'];
		return $this->createByRow($rowMain);
	}

	/**
	 * @param array $row
	 * @return DataFormConfiguration\DataFormConfiguration
	 * @throws UnknownFormException
	 */
	private function createByRow(array $row)
	{
		$pConfig = $this->createEmpty(false);
		$this->configureGeneral($row, $pConfig);

		switch ($this->_type) {
			case Form::TYPE_CONTACT:
				$this->configureContact($row, $pConfig);
				break;
			case Form::TYPE_OWNER:
				$this->configureOwner($row, $pConfig);
				break;
			case Form::TYPE_INTEREST:
				$this->configureInterest($row, $pConfig);
				break;
			case Form::TYPE_APPLICANT_SEARCH:
				$this->configureApplicantSearch($row, $pConfig);
				break;
		}

		$formId = $row['form_id'];
		$rowFields = $this->_pRecordManagerRead->readFieldsByFormId($formId);
		$this->_pGeoPositionFieldHandler->readValues($pConfig);
		$rowFields = $this->configureGeoFields($rowFields);

		foreach ($rowFields as $fieldRow) {
			$this->configureFieldsByRow($fieldRow, $pConfig);
		}

		return $pConfig;
	}


	/**
	 *
	 * @param array $row
	 * @param DataFormConfiguration\DataFormConfiguration $pFormConfiguration
	 *
	 */

	private function configureFieldsByRow(array $row,
		DataFormConfiguration\DataFormConfiguration $pFormConfiguration)
	{
		$fieldName = $row['fieldname'];
		$module = $row['module'];
		$pFormConfiguration->addInput($fieldName, $module);

		if ($row['required'] == 1) {
			$pFormConfiguration->addRequiredField($fieldName);
		}

		if (array_key_exists('availableOptions', $row) && $row['availableOptions'] == 1) {
			$pFormConfiguration->addAvailableOptionsField($fieldName);
		}
	}


	/**
	 *
	 * @param array $result
	 * @return array
	 *
	 */

	private function configureGeoFields(array $result): array
	{
		$arrayPosition = array_search(GeoPosition::FIELD_GEO_POSITION, array_column($result, 'fieldname'));
		if ($arrayPosition === false || $this->_isAdminInterface) {
			return $result;
		}

		$row = $result[$arrayPosition];
		unset($result[$arrayPosition]);

		$formId = $row['form_id'];
		$required = $row['required'];
		$geoPositionSettings = $this->_pGeoPositionFieldHandler->getActiveFields();
		$fieldMapping = (new GeoPosition)->getSearchCriteriaFields();

		foreach ($geoPositionSettings as $field) {
			if ($this->_type === Form::TYPE_APPLICANT_SEARCH && $field === GeoPosition::ESTATE_LIST_SEARCH_RADIUS) {
				continue;
			}
			$geoPositionField = [
				'form_id' => $formId,
				'required' => $required,
				'fieldname' => $fieldMapping[$field],
				'fieldlabel' => null,
				'module' => $row['module'],
				'individual_fieldname' => 0,
			];

			$result []= $geoPositionField;
		}

		return $result;
	}


	/**
	 *
	 * @param array $row
	 * @param DataFormConfigurationContact $pConfig
	 *
	 */

	private function configureContact(array $row, DataFormConfigurationContact $pConfig)
	{
		$pConfig->setRecipient($row['recipient']);
		$pConfig->setSubject($row['subject']);
		$pConfig->setCreateAddress((bool)$row['createaddress']);
		$pConfig->setCheckDuplicateOnCreateAddress((bool)$row['checkduplicates']);
		$pConfig->setNewsletterCheckbox((bool)$row['newsletter']);
		$pConfig->setShowEstateContext((bool)$row['show_estate_context']);
		$pConfig->setContactTypeField($row['contact_type'] ?? '');
	}


	/**
	 *
	 * @param array $row
	 * @param DataFormConfiguration\DataFormConfiguration $pConfig
	 *
	 */

	private function configureGeneral(array $row,
		DataFormConfiguration\DataFormConfiguration $pConfig)
	{
		$pConfig->setFormName($row['name']);
		$pConfig->setTemplate($row['template']);
		$pConfig->setCaptcha($row['captcha']);
		$pConfig->setId($row['form_id']);

		if (array_key_exists('form_type', $row)) {
			$pConfig->setFormType($row['form_type']);
		}
	}


	/**
	 *
	 * @param array $row
	 * @param DataFormConfigurationApplicantSearch $pConfig
	 *
	 */

	private function configureApplicantSearch(array $row, DataFormConfigurationApplicantSearch $pConfig)
	{
		$pConfig->setLimitResults($row['limitresults']);
	}


	/**
	 *
	 * @param array $row
	 * @param DataFormConfigurationOwner $pConfig
	 *
	 */

	private function configureOwner(array $row, DataFormConfigurationOwner $pConfig)
	{
		$pConfig->setRecipient($row['recipient']);
		$pConfig->setSubject($row['subject']);
		$pConfig->setPages($row['pages']);
		$pConfig->setCheckDuplicateOnCreateAddress((bool)$row['checkduplicates']);
		$pConfig->setContactTypeField($row['contact_type'] ?? '');
	}


	/**
	 *
	 * @param array $row
	 * @param DataFormConfigurationInterest $pConfig
	 *
	 */

	private function configureInterest(array $row, DataFormConfigurationInterest $pConfig)
	{
		$pConfig->setRecipient($row['recipient']);
		$pConfig->setSubject($row['subject']);
		$pConfig->setCheckDuplicateOnCreateAddress($row['checkduplicates']);
		$pConfig->setContactTypeField($row['contact_type'] ?? '');
	}


	/**
	 *
	 * @param string $type
	 * @return DataFormConfigurationFactory
	 *
	 */

	public function withType(string $type): DataFormConfigurationFactory
	{
		$pClone = clone $this;
		$pClone->_type = $type;
		return $pClone;
	}
}