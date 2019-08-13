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

use DI\ContainerBuilder;
use onOffice\SDK\onOfficeSDK;
use onOffice\WPlugin\DataFormConfiguration\UnknownFormException;
use onOffice\WPlugin\Field\Collection\FieldsCollectionBuilderShort;
use onOffice\WPlugin\Field\Collection\FieldsCollectionToContentFieldLabelArrayConverter;
use onOffice\WPlugin\Model\FormModel;
use onOffice\WPlugin\Model\FormModelBuilder\FormModelBuilder;
use onOffice\WPlugin\Model\FormModelBuilder\FormModelBuilderDBForm;
use onOffice\WPlugin\Model\InputModel\InputModelDBFactoryConfigForm;
use onOffice\WPlugin\Record\BooleanValueToFieldList;
use onOffice\WPlugin\Record\RecordManager;
use onOffice\WPlugin\Record\RecordManagerFactory;
use onOffice\WPlugin\Record\RecordManagerInsertException;
use onOffice\WPlugin\Record\RecordManagerReadForm;
use onOffice\WPlugin\Translation\ModuleTranslation;
use onOffice\WPlugin\Types\FieldsCollection;
use stdClass;
use const ONOFFICE_DI_CONFIG_PATH;
use function __;
use function add_screen_option;
use function esc_sql;
use function wp_enqueue_script;

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

	/** @var bool */
	private $_showEstateFields = false;

	/** @var bool */
	private $_showAddressFields = false;

	/** @var bool */
	private $_showSearchCriteriaFields = false;

	/** @var array */
	private $_sortableFieldModules = array();

	/** @var string */
	private $_type = null;

	/** @var FormModelBuilderDBForm */
	private $_pFormModelBuilder = null;

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
	 * @param array $row
	 * @return bool
	 *
	 */

	protected function checkFixedValues($row)
	{
		$table = RecordManager::TABLENAME_FORMS;
		$result = isset($row[$table]['name']) && $row[$table]['name'] != null;

		return $result;
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
			.'oo_plugin_forms` WHERE `form_id` = "'.esc_sql($recordId).'" AND '
			.'`form_type` = "'.esc_sql($this->getType()).'"');

		if ($value != (int)$recordId) {
			throw new UnknownFormException;
		}
	}


	/**
	 *
	 * Since checkbox are only being submitted if checked they need to be reorganized
	 *
	 * @param stdClass $pValues
	 *
	 */

	protected function prepareValues(stdClass $pValues)
	{
		$pBoolToFieldList = new BooleanValueToFieldList(new InputModelDBFactoryConfigForm, $pValues);
		$pBoolToFieldList->fillCheckboxValues(InputModelDBFactoryConfigForm::INPUT_FORM_REQUIRED);
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
		$type = RecordManagerFactory::TYPE_FORM;

		if ($recordId != 0) {
			$action = RecordManagerFactory::ACTION_UPDATE;
			// update by row
			$pRecordManagerUpdateForm = RecordManagerFactory::createByTypeAndAction($type, $action, $recordId);
			$result = $pRecordManagerUpdateForm->updateByRow($row[RecordManager::TABLENAME_FORMS]);

			if (array_key_exists(RecordManager::TABLENAME_FIELDCONFIG_FORMS, $row)) {
				$result = $result && $pRecordManagerUpdateForm->updateFieldConfigByRow
					($row[RecordManager::TABLENAME_FIELDCONFIG_FORMS]);
			}
		} else {
			$action = RecordManagerFactory::ACTION_INSERT;
			// insert
			$pRecordManagerInsertForm = RecordManagerFactory::createByTypeAndAction($type, $action);

			try {
				$recordId = $pRecordManagerInsertForm->insertByRow($row);

				$rowFieldConfig = $this->addOrderValues($row, RecordManager::TABLENAME_FIELDCONFIG_FORMS);
				$rowFieldConfig = $this->prepareRelationValues
					(RecordManager::TABLENAME_FIELDCONFIG_FORMS, 'form_id', $row, $recordId);
				$row[RecordManager::TABLENAME_FIELDCONFIG_FORMS] = $rowFieldConfig;
				$pRecordManagerInsertForm->insertAdditionalValues($row);
				$result = true;
			} catch (RecordManagerInsertException $pException) {
				$result = false;
				$recordId = null;
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
		$row = $this->addOrderValues($row, RecordManager::TABLENAME_FIELDCONFIG_FORMS);
		$row[RecordManager::TABLENAME_FORMS]['form_type'] = $this->getType();

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
			self::VIEW_SAVE_SUCCESSFUL_MESSAGE => __('The Form was saved.', 'onoffice'),
			self::VIEW_SAVE_FAIL_MESSAGE => __('There was a problem saving the form. Please make '
					.'sure the name of the form is unique.', 'onoffice'),
			self::ENQUEUE_DATA_MERGE => array(
					AdminPageSettingsBase::POST_RECORD_ID,
					self::GET_PARAM_TYPE,
				),
			AdminPageSettingsBase::POST_RECORD_ID => (int)$this->getListViewId(),
			self::MODULE_LABELS => ModuleTranslation::getAllLabelsSingular(true),
			/* translators: %s is a translated module name */
			self::FIELD_MODULE => __('Module: %s', 'onoffice'),
		);
	}


	/**
	 *
	 * Call this first in overriding class
	 *
	 */

	protected function buildForms()
	{
		add_screen_option('layout_columns', array('max' => 2, 'default' => 2) );
		$this->_pFormModelBuilder = new FormModelBuilderDBForm($this->getPageSlug());
		$this->_pFormModelBuilder->setFormType($this->getType());
		$pFormModel = $this->_pFormModelBuilder->generate($this->getListViewId());
		$this->addFormModel($pFormModel);

		$pInputModelName = $this->_pFormModelBuilder->createInputModelName();
		$pFormModelName = new FormModel();
		$pFormModelName->setPageSlug($this->getPageSlug());
		$pFormModelName->setGroupSlug(self::FORM_RECORD_NAME);
		$pFormModelName->setLabel(__('choose name', 'onoffice'));
		$pFormModelName->addInputModel($pInputModelName);

		$pInputModelType = $this->_pFormModelBuilder->createInputModelFormType();
		$pFormModelName->addInputModel($pInputModelType);

		if ($this->getListViewId() !== null) {
			$pInputModelEmbedCode = $this->_pFormModelBuilder->createInputModelEmbedCode();
			$pFormModelName->addInputModel($pInputModelEmbedCode);
		}

		$this->addFormModel($pFormModelName);

		$pInputModelTemplate = $this->_pFormModelBuilder->createInputModelTemplate('form');
		$pFormModelLayoutDesign = new FormModel();
		$pFormModelLayoutDesign->setPageSlug($this->getPageSlug());
		$pFormModelLayoutDesign->setGroupSlug(self::FORM_VIEW_LAYOUT_DESIGN);
		$pFormModelLayoutDesign->setLabel(__('Layout & Design', 'onoffice'));
		$pFormModelLayoutDesign->addInputModel($pInputModelTemplate);
		$this->addFormModel($pFormModelLayoutDesign);
	}


	/**
	 *
	 */

	protected function generateMetaBoxes()
	{
		$pFormLayoutDesign = $this->getFormModelByGroupSlug(self::FORM_VIEW_LAYOUT_DESIGN);
		$this->createMetaBoxByForm($pFormLayoutDesign, 'normal');
	}


	/**
	 *
	 */

	protected function generateAccordionBoxes()
	{
		$this->cleanPreviousBoxes();
		$pDefaultFieldsCollection = $this->readFields();
		$modules = [];

		if ($this->_showEstateFields) {
			$modules []= onOfficeSDK::MODULE_ESTATE;
		}

		if ($this->_showAddressFields) {
			$modules []= onOfficeSDK::MODULE_ADDRESS;
		}

		if ($this->_showSearchCriteriaFields) {
			$modules []= onOfficeSDK::MODULE_SEARCHCRITERIA;
		}

		$pFieldsCollectionConverter = new FieldsCollectionToContentFieldLabelArrayConverter();

		foreach ($modules as $module) {
			$fieldNames = $pFieldsCollectionConverter->convert($pDefaultFieldsCollection, $module);

			foreach (array_keys($fieldNames) as $category) {
				$slug = $this->generateGroupSlugByModuleCategory($module, $category);
				$pFormFieldsConfig = $this->getFormModelByGroupSlug($slug);
				$pFormFieldsConfig->setOoModule($module);
				$this->createMetaBoxByForm($pFormFieldsConfig, 'side');
			}
		}
	}


	/**
	 *
	 * @return FieldsCollection
	 *
	 */

	private function readFields(): FieldsCollection
	{
		$pDIBuilder = new ContainerBuilder;
		$pDIBuilder->addDefinitions(ONOFFICE_DI_CONFIG_PATH);
		$pContainer = $pDIBuilder->build();

		$pFieldsCollectionBuilder = $pContainer->get(FieldsCollectionBuilderShort::class);
		$pDefaultFieldsCollection = new FieldsCollection();

		if ($this->_showEstateFields || $this->_showAddressFields) {
			$pFieldsCollectionBuilder->addFieldsAddressEstate($pDefaultFieldsCollection);
		}

		if ($this->_showSearchCriteriaFields) {
			$pFieldsCollectionBuilder
				->addFieldsSearchCriteria($pDefaultFieldsCollection)
				->addFieldsSearchCriteriaSpecificBackend($pDefaultFieldsCollection);
		}

		return $pDefaultFieldsCollection;
	}


	/**
	 *
	 * Call this in method `buildForms` of overriding class
	 *
	 * Don't forget to call
	 * <code>
	 * 		$this->addSortableFieldsList($this->getSortableFieldModules(), $pFormModelBuilder,
	 *			InputModelBase::HTML_TYPE_COMPLEX_SORTABLE_DETAIL_LIST_FORM);
	 * </code>
	 * afterwards.
	 *
	 */

	protected function addFieldConfigurationForMainModules(FormModelBuilder $pFormModelBuilder)
	{
		$pFieldsCollection = $this->readFields();

		if ($this->_showEstateFields) {
			$this->addFieldConfigurationByModule($pFormModelBuilder, $pFieldsCollection, onOfficeSDK::MODULE_ESTATE);
		}

		if ($this->_showAddressFields) {
			$this->addFieldConfigurationByModule($pFormModelBuilder, $pFieldsCollection, onOfficeSDK::MODULE_ADDRESS);
		}

		if ($this->_showSearchCriteriaFields) {
			$this->addFieldConfigurationByModule($pFormModelBuilder, $pFieldsCollection, onOfficeSDK::MODULE_SEARCHCRITERIA);
		}
	}


	/**
	 *
	 * @param FormModelBuilder $pFormModelBuilder
	 * @param FieldsCollection $pFieldsCollection
	 * @param string $module
	 *
	 */

	private function addFieldConfigurationByModule(
		FormModelBuilder $pFormModelBuilder, FieldsCollection $pFieldsCollection, string $module)
	{
		$pFieldsCollectionConverter = new FieldsCollectionToContentFieldLabelArrayConverter();
		$fieldNames = $pFieldsCollectionConverter->convert($pFieldsCollection, $module);
		$this->addFieldsConfiguration($module, $pFormModelBuilder, $fieldNames, true);
		$this->addSortableFieldModule($module);
	}


	/**
	 *
	 */

	public function doExtraEnqueues()
	{
		parent::doExtraEnqueues();
		wp_enqueue_script('oo-checkbox-js');
	}


	/**
	 *
	 * @param string $module
	 *
	 */

	protected function removeSortableFieldModule($module)
	{
		if (in_array($module, $this->_sortableFieldModules)) {
			$key = array_search($module, $this->_sortableFieldModules);
			unset($this->_sortableFieldModules[$key]);
		}
	}

	/** @return string */
	public function getType()
		{ return $this->_type; }

	/** @param string $type */
	public function setType($type)
		{ $this->_type = $type; }

	/** @return FormModelBuilder */
	protected function getFormModelBuilder()
		{ return $this->_pFormModelBuilder; }

	/** @param string $module */
	protected function addSortableFieldModule($module)
		{ $this->_sortableFieldModules []= $module; }

	/** @return array */
	protected function getSortableFieldModules()
		{ return $this->_sortableFieldModules; }

	/** @return bool */
	public function getShowEstateFields()
		{ return $this->_showEstateFields; }

	/** @return bool */
	public function getShowAddressFields()
		{ return $this->_showAddressFields; }

	/** @return bool */
	public function getShowSearchCriteriaFields()
		{ return $this->_showSearchCriteriaFields; }

	/** @param bool $showEstateFields */
	public function setShowEstateFields($showEstateFields)
		{ $this->_showEstateFields = (bool)$showEstateFields; }

	/** @param bool $showAddressFields */
	public function setShowAddressFields($showAddressFields)
		{ $this->_showAddressFields = (bool)$showAddressFields; }

	/** @param bool $showSearchCriteriaFields */
	public function setShowSearchCriteriaFields($showSearchCriteriaFields)
		{ $this->_showSearchCriteriaFields = (bool)$showSearchCriteriaFields; }
}