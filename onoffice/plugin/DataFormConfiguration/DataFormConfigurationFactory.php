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

namespace onOffice\WPlugin\DataFormConfiguration;

use onOffice\WPlugin\DataFormConfiguration\DataFormConfiguration;
use onOffice\WPlugin\DataFormConfiguration\DataFormConfigurationApplicantSearch;
use onOffice\WPlugin\DataFormConfiguration\DataFormConfigurationContact;
use onOffice\WPlugin\Form;
use onOffice\WPlugin\Record\RecordManagerReadForm;

/**
 *
 * @url http://www.onoffice.de
 * @copyright 2003-2017, onOffice(R) GmbH
 *
 */

class DataFormConfigurationFactory
{
	/** @var string */
	private $_type = null;


	/** @var array */
	private $_formClassMapping = array
		(
			Form::TYPE_CONTACT => 'DataFormConfigurationContact',
			Form::TYPE_FREE => 'DataFormConfiguration',
			Form::TYPE_OWNER => 'DataFormConfigurationContact',
			Form::TYPE_INTEREST => 'DataFormConfigurationContact',
			Form::TYPE_APPLICANT_SEARCH => 'DataFormConfigurationApplicantSearch',
		);


	/**
	 *
	 * @param string $type
	 *
	 */

	public function __construct($type)
	{
		$this->_type = $type;
	}


	/**
	 *
	 * @throws UnknownFormException
	 * @return DataFormConfiguration
	 *
	 */

	public function createEmpty()
	{
		if (!array_key_exists($this->_type, $this->_formClassMapping)) {
			throw new UnknownFormException;
		}

		$class = __NAMESPACE__.'\\'.$this->_formClassMapping[$this->_type];
		/* @var $pConfig DataFormConfiguration */
		$pConfig = new $class;
		$pConfig->setFormType($this->_type);

		return $pConfig;
	}


	/**
	 *
	 * @param int $formId
	 * @return DataFormConfiguration;
	 *
	 */

	public function loadByFormId($formId)
	{
		$pRecordManagerRead = new RecordManagerReadForm();

		$rowMain = $pRecordManagerRead->getRowById($formId);
		$this->_type = $rowMain['form_type'];
		$pConfig = $this->createByRow($rowMain);
		$rowFields = $pRecordManagerRead->readFieldsByFormId($formId);

		foreach ($rowFields as $fieldRow) {
			$this->configureFieldsByRow($fieldRow, $pConfig);
		}

		return $pConfig;
	}


	/**
	 *
	 * @param array $row
	 * @return DataFormConfiguration
	 *
	 */

	public function createByRow(array $row)
	{
		$pConfig = $this->createEmpty();
		$this->configureGeneral($row, $pConfig);

		switch ($this->_type) {
			case Form::TYPE_CONTACT:
			case Form::TYPE_OWNER:
			case Form::TYPE_INTEREST:
				$this->configureContact($row, $pConfig);
				break;
			case Form::TYPE_APPLICANT_SEARCH:
				$this->configureApplicant($row, $pConfig);
				break;
		}

		return $pConfig;
	}


	/**
	 *
	 * @param array $row
	 * @param DataFormConfiguration $pFormConfiguration
	 *
	 */

	private function configureFieldsByRow($row, DataFormConfiguration $pFormConfiguration)
	{
		$fieldName = $row['fieldname'];
		$module = $row['module'];
		$pFormConfiguration->addInput($fieldName, $module);

		if ($row['required'] == 1) {
			$pFormConfiguration->addRequiredField($fieldName);
		}
	}


	/**
	 *
	 * @param array $row
	 * @param DataFormConfiguration $pConfig
	 *
	 */

	private function configureContact(array $row, DataFormConfigurationContact $pConfig)
	{
		$pConfig->setRecipient($row['recipient']);
		$pConfig->setSubject($row['subject']);
	}


	/**
	 *
	 * @param array $row
	 * @param DataFormConfiguration $pConfig
	 *
	 */

	private function configureGeneral(array $row, DataFormConfiguration $pConfig)
	{
		$pConfig->setFormName($row['name']);
		$pConfig->setTemplate($row['template']);

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

	private function configureApplicant(array $row, DataFormConfigurationApplicantSearch $pConfig)
	{
		$pConfig->setLimitResults($row['limitResults']);
	}


	/**
	 *
	 * @param array $rows rows from fieldconfig table
	 * @param DataFormConfiguration $pConfig
	 *
	 */

	public function addModulesByFields(array $rows, DataFormConfiguration $pConfig)
	{
		if (!array_key_exists('fieldname', $rows)) {
			return;
		}

		foreach ($rows['fieldname'] as $fieldName) {
			$module = null;
			if (isset($rows['module'][$fieldName])) {
				$module = $rows['module'][$fieldName];
			}

			$pConfig->addInput($fieldName, $module);

			if (!isset($rows['required'])) {
				continue;
			}

			$required = in_array($fieldName, $rows['required'], true);

			if ($required) {
				$pConfig->addRequiredField($fieldName);
			}
		}
	}
}
