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

namespace onOffice\WPlugin\Gui;

use onOffice\WPlugin\DataFormConfiguration\UnknownFormException;
use onOffice\WPlugin\Model\InputModel\InputModelDBFactory;
use onOffice\WPlugin\Model\InputModel\InputModelDBFactoryConfigForm;
use onOffice\WPlugin\Record\RecordManager;
use onOffice\WPlugin\Record\RecordManagerInsertForm;
use onOffice\WPlugin\Record\RecordManagerReadForm;
use onOffice\WPlugin\Record\RecordManagerUpdateForm;
use onOffice\WPlugin\Utility\ModuleTranslation;
use stdClass;

/**
 *
 * @url http://www.onoffice.de
 * @copyright 2003-2017, onOffice(R) GmbH
 *
 */

abstract class AdminPageFormSettingsBase
	extends AdminPageSettingsBase
{
	/** */
	const FORM_VIEW_LAYOUT_DESIGN = 'viewlayoutdesign';

	/** */
	const FORM_VIEW_FORM_SPECIFIC = 'viewformspecific';

	/** */
	const MODULE_LABELS = 'modulelabels';

	/** */
	const FIELD_MODULE = 'fieldmodule';

	/** */
	const GET_PARAM_TYPE = 'type';

	/** @var string */
	private $_type = null;

	/**
	 *
	 * @param string $pageSlug
	 *
	 */

	public function __construct($pageSlug)
	{
		$this->setPageTitle(__('Edit Form', 'onoffice'));
		parent::__construct($pageSlug);
	}


	/**
	 *
	 * @param int $recordId
	 * @throws UnknownFormException
	 *
	 */

	protected function validate($recordId = 0)
	{
		if ((int)$recordId === 0) {
			return;
		}

		$pRecordReadManager = new RecordManagerReadForm();
		$pWpDb = $pRecordReadManager->getWpdb();
		$prefix = $pRecordReadManager->getTablePrefix();
		$value = $pWpDb->get_var('SELECT form_id FROM `'.esc_sql($prefix)
			.'oo_plugin_forms` WHERE `form_id` = "'.esc_sql($recordId).'"');

		if ($value != (int)$recordId) {
			throw new UnknownFormException;
		}
	}


	/**
	 *
	 * Since checkbox are only being submitted if checked they need to be reorganized
	 *
	 * @param stdClass $values
	 *
	 */

	protected function prepareValues(stdClass $values) {
		$pInputModelFactory = new InputModelDBFactory(new InputModelDBFactoryConfigForm());
		$pInputModelRequired = $pInputModelFactory->create
			(InputModelDBFactoryConfigForm::INPUT_FORM_REQUIRED, 'required', true);
		$identifierRequired = $pInputModelRequired->getIdentifier();
		$pInputModelFieldName = $pInputModelFactory->create
			(InputModelDBFactory::INPUT_FIELD_CONFIG, 'fields', true);
		$identifierFieldName = $pInputModelFieldName->getIdentifier();
		// use order of fieldname-array, add required fields (which come in an array of names)
		if (property_exists($values, $identifierRequired) &&
			property_exists($values, $identifierFieldName)) {
			$fieldsArray = (array)$values->$identifierFieldName;
			$requiredFields = (array)$values->$identifierRequired;
			$newRequiredFields = array_fill_keys(array_keys($fieldsArray), '0');

			foreach ($requiredFields as $requiredField) {
				$keyIndex = array_search($requiredField, $fieldsArray);
				$newRequiredFields[$keyIndex] = '1';
			}

			$values->$identifierRequired = $newRequiredFields;
		}
	}


	/**
	 *
	 * @param array $row
	 * @param stdClass $pResult
	 * @param int $recordId
	 *
	 */

	protected function updateValues(array $row, stdClass $pResult, $recordId = null)
	{
		$result = false;

		if ($recordId != 0) {
			// update by row
			$pRecordManagerUpdateForm = new RecordManagerUpdateForm($recordId);
			$result = $pRecordManagerUpdateForm->updateByRow($row[RecordManager::TABLENAME_FORMS]);

			if (array_key_exists(RecordManager::TABLENAME_FIELDCONFIG_FORMS, $row)) {
				$result = $result && $pRecordManagerUpdateForm->updateFieldConfigByRow
					($row[RecordManager::TABLENAME_FIELDCONFIG_FORMS]);
			}
		} else {
			// insert
			$pRecordManagerInsertForm = new RecordManagerInsertForm();
			$recordId = $pRecordManagerInsertForm->insertByRow($row);
			$result = ($recordId != null);

			if ($result) {
				$row = $this->addOrderValues($row);
				$row = $this->prepareRelationValues
					(RecordManager::TABLENAME_FIELDCONFIG_FORMS, 'form_id', $row, $recordId);
				$pRecordManagerInsertForm->insertAdditionalValues($row);
			}
		}

		$pResult->result = $result;
		$pResult->record_id = $recordId;
	}


	/**
	 *
	 * @param array $row
	 * @return array
	 *
	 */

	protected function setFixedValues(array $row)
	{
		$row = $this->addOrderValues($row);
		$row[RecordManager::TABLENAME_FORMS]['form_type'] = $this->getType();

		return $row;
	}


	/**
	 *
	 * @param array $row
	 * @return array
	 *
	 */

	protected function addOrderValues(array $row)
	{
		$table = RecordManager::TABLENAME_FIELDCONFIG_FORMS;
		if (array_key_exists($table, $row)) {
			array_walk($row[$table], function (&$value, $key) {
				$value['order'] = $key + 1;
			});
		}
		return $row;
	}


	/**
	 *
	 * @return array
	 *
	 */

	public function getEnqueueData()
	{
		return array(
			self::GET_PARAM_TYPE => $this->getType(),
			self::VIEW_SAVE_SUCCESSFUL_MESSAGE => __('The Form was Saved.', 'onoffice'),
			self::VIEW_SAVE_FAIL_MESSAGE => __('There was a problem saving the form. Please make '
					.'sure the name of the form is unique.', 'onoffice'),
			self::ENQUEUE_DATA_MERGE => array(
					AdminPageSettingsBase::POST_RECORD_ID,
					self::GET_PARAM_TYPE,
				),
			AdminPageSettingsBase::POST_RECORD_ID => (int)$this->getListViewId(),
			self::MODULE_LABELS => ModuleTranslation::getAllLabelsSingular(true),
			self::FIELD_MODULE => __('Module: %s', 'onoffice'),
		);
	}


	/**
	 *
	 */

	public function doExtraEnqueues()
	{
		parent::doExtraEnqueues();

		wp_register_script('oo-forms-js',
			plugin_dir_url(ONOFFICE_PLUGIN_DIR.'/index.php').'/js/forms.js', array('jquery'), '', true);
		wp_enqueue_script('oo-forms-js');
	}


	/** @return string */
	public function getType()
		{ return $this->_type; }

	/** @param string $type */
	public function setType($type)
		{ $this->_type = $type; }
}
