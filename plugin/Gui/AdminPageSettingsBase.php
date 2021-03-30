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

use Exception;
use onOffice\WPlugin\Model;
use onOffice\WPlugin\Model\FormModelBuilder\FormModelBuilder;
use onOffice\WPlugin\Model\InputModelBase;
use onOffice\WPlugin\Model\InputModelDB;
use onOffice\WPlugin\Model\InputModelDBAdapterRow;
use onOffice\WPlugin\Renderer\InputModelRenderer;
use stdClass;
use const ONOFFICE_PLUGIN_DIR;
use function __;
use function add_action;
use function do_accordion_sections;
use function do_action;
use function do_meta_boxes;
use function do_settings_sections;
use function get_current_screen;
use function json_decode;
use function json_encode;
use function plugin_dir_url;
use function submit_button;
use function wp_die;
use function wp_enqueue_script;
use function wp_nonce_field;
use function wp_register_script;
use function wp_verify_nonce;

/**
 *
 */

abstract class AdminPageSettingsBase
	extends AdminPageAjax
{
	/** caution: also needs to be set in Javascript */
	const POST_RECORD_ID = 'record_id';

	/** GET param for the view ID */
	const GET_PARAM_VIEWID = 'id';

	/** */
	const FORM_RECORD_NAME = 'recordname';

	/** */
	const FORM_VIEW_PICTURE_TYPES = 'viewpicturetypes';

	/** */
	const FORM_VIEW_SORTABLE_FIELDS_CONFIG = 'viewSortableFieldsConfig';

	/** */
	const FORM_VIEW_LAYOUT_DESIGN = 'viewlayoutdesign';

	/** */
	const FORM_VIEW_RECORDS_FILTER = 'viewrecordsfilter';

	/** */
	const VIEW_SAVE_SUCCESSFUL_MESSAGE = 'view_save_success_message';

	/** */
	const VIEW_SAVE_FAIL_MESSAGE = 'view_save_fail_message';

	/** @var string */
	private $_pageTitle = null;

	/** @var int */
	private $_listViewId = null;


	/**
	 *
	 * @param string $pageSlug
	 *
	 */

	public function __construct($pageSlug)
	{
		parent::__construct($pageSlug);
		$this->_listViewId = filter_input(INPUT_GET, self::GET_PARAM_VIEWID);
	}


	/**
	 *
	 */

	public function handleAdminNotices()
	{
		add_action('admin_notices', array($this, 'addAdminNoticeWrapper'));
	}


	/**
	 *
	 * @throws Exception
	 *
	 */

	abstract protected function validate($recordId = null);


	/**
	 *
	 * rest will be added via js
	 *
	 */

	public function addAdminNoticeWrapper()
	{
		echo '<div id="onoffice-notice-wrapper"></div>';
	}


	/**
	 *
	 */

	public function renderContent()
	{
		do_action('add_meta_boxes', get_current_screen()->id, null);
		$this->generateMetaBoxes();

		/* @var $pInputModelRenderer InputModelRenderer */
		$pInputModelRenderer = $this->getContainer()->get(InputModelRenderer::class);
		$pFormViewName = $this->getFormModelByGroupSlug(self::FORM_RECORD_NAME);
		$pFormViewSortableFields = $this->getFormModelByGroupSlug(self::FORM_VIEW_SORTABLE_FIELDS_CONFIG);

		wp_nonce_field( $this->getPageSlug() );

		$this->generatePageMainTitle($this->_pageTitle);
		echo '<div id="onoffice-ajax">';
		wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
		wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
		echo '<div id="poststuff">';
		echo '<div id="post-body" class="metabox-holder columns-'
			.(1 == get_current_screen()->get_columns() ? '1' : '2').'">';
		echo '<div id="post-body-content">';
		$pInputModelRenderer->buildForAjax($pFormViewName);
		echo '</div>';
		echo '<div class="postbox-container" id="postbox-container-1">';
		do_meta_boxes(get_current_screen()->id, 'normal', null );
		echo '</div>';
		echo '<div class="postbox-container" id="postbox-container-2">';
		do_meta_boxes(get_current_screen()->id, 'side', null );
		do_meta_boxes(get_current_screen()->id, 'advanced', null );
		echo '</div>';
		echo '<div class="clear"></div>';
		do_action('add_meta_boxes', get_current_screen()->id, null);
		echo '<div style="float:left;">';
		$this->generateAccordionBoxes();
		echo '</div>';
		echo '<div id="listSettings" style="float:left;" class="postbox">';
		do_accordion_sections(get_current_screen()->id, 'side', null);
		echo '</div>';
		echo '<div class="fieldsSortable postbox">';
		echo '<h2 class="hndle ui-sortable-handle"><span>'.__('Fields', 'onoffice-for-wp-websites').'</span></h2>';
		$pInputModelRenderer->buildForAjax($pFormViewSortableFields);
		echo '</div>';
		echo '<div class="clear"></div>';
		echo '</div>';
		echo '</div>';
		do_settings_sections( $this->getPageSlug() );
		submit_button(null, 'primary', 'send_ajax');

		echo '<script>'
			.'jQuery(document).ready(function(){'
				.'onOffice.ajaxSaver = new onOffice.ajaxSaver("onoffice-ajax");'
				.'onOffice.ajaxSaver.register();'
			.'});'
		.'</script>';
	}


	/**
	 *
	 */

	public function ajax_action()
	{
		$this->buildForms();
		$action = filter_input(INPUT_POST, 'action');
		$nonce = filter_input(INPUT_POST, 'nonce');
		$recordId = (int)filter_input(INPUT_POST, self::POST_RECORD_ID);
		$this->validate($recordId);

		if (!wp_verify_nonce($nonce, $action)) {
			wp_die();
		}

		$mainRecordId = $recordId != 0 ? $recordId : null;

		$values = json_decode(filter_input(INPUT_POST, 'values'));
		$this->prepareValues($values);
		$pInputModelDBAdapterRow = new InputModelDBAdapterRow();

		foreach ($this->getFormModels() as $pFormModel) {
			foreach ($pFormModel->getInputModel() as $pInputModel) {
				if ($pInputModel instanceof InputModelDB) {
					$identifier = $pInputModel->getIdentifier();
					$value = isset($values->$identifier) ? $values->$identifier : null;
					$pInputModel->setValue($value);
					$pInputModel->setMainRecordId($mainRecordId ?? 0);
					$pInputModelDBAdapterRow->addInputModelDB($pInputModel);
				}
			}
		}

		$row = $pInputModelDBAdapterRow->createUpdateValuesByTable();
		$row = $this->setFixedValues($row);
		$checkResult = $this->checkFixedValues($row);
		$pResultObject = new stdClass();
		$pResultObject->result = false;
		$pResultObject->record_id = $recordId;
		$row['oo_plugin_fieldconfig_form_defaults_values'] =
			(array)($row['oo_plugin_fieldconfig_form_defaults_values']['value'] ?? []) +
			(array)($values->{'defaultvalue-lang'}) ?? [];
		$row['oo_plugin_fieldconfig_form_translated_labels'] =
			(array)($row['oo_plugin_fieldconfig_form_translated_labels']['value'] ?? []) +
			(array)($values->{'customlabel-lang'}) ?? [];

		if ($checkResult) {
			$this->updateValues($row, $pResultObject, $recordId);
		}

		$pResultObject->messageKey = $this->getResponseMessagekey($pResultObject->result);

		echo json_encode($pResultObject);

		wp_die();
	}


	/**
	 *
	 * @param bool $result
	 * @return string
	 *
	 */

	protected function getResponseMessagekey($result)
	{
		if ($result) {
			return self::VIEW_SAVE_SUCCESSFUL_MESSAGE;
		}

		return self::VIEW_SAVE_FAIL_MESSAGE;
	}


	/**
	 *
	 * Check values here, throw \Exception if anything is wrong
	 *
	 * @param array $row
	 * @return bool
	 *
	 */

	protected function checkFixedValues($row)
	{
		return true;
	}


	/**
	 *
	 * @param array $row
	 * @param string $mainTableName
	 * @return array
	 *
	 */

	protected function setRecordsPerPage(array $row, string $mainTableName): array
	{
		$recordsPerPage = (int)$row[$mainTableName]['recordsPerPage'];
		$row[$mainTableName]['recordsPerPage'] = $recordsPerPage > 0 ? $recordsPerPage : 20;
		return $row;
	}

	/**
	 *
	 * @param string $module
	 * @param string $category
	 * @return string
	 *
	 */

	protected function generateGroupSlugByModuleCategory($module, $category)
	{
		return $module.'/'.$category;
	}


	/**
	 *
	 * @param string $table
	 * @param string $column
	 * @param array $values
	 * @param int $recordId
	 * @return array
	 *
	 */

	protected function prepareRelationValues($table, $column, array $values, $recordId): array
	{
		$valuesTable = $values[$table] ?? [];

		array_walk($valuesTable, function (&$value, $key) use ($column, $recordId) {
			if (is_array($value) && array_key_exists($column, $value)) {
				$value[$column] = $recordId;
			}
		});

		return $valuesTable;
	}


	/**
	 *
	 * @param stdClass $pValues
	 *
	 */

	protected function prepareValues(stdClass $pValues) {}

	/**
	 *  following characters are compatible across all platforms:
	 * Upper-case and lower-case letters: A-Z a-z (+ Umlaut)
	 * Digits: 0-9
	 * Underscore: _
	 * @param string $name
	 * @return string
	 */
	protected function sanitizeShortcodeName(string $name): string
	{
		return preg_replace('/[^a-zA-Z0-9äÄöÖüÜß:_ \-]/u', '', $name);
	}


	/**
	 *
	 * @param string|null $module
	 * @param FormModelBuilder $pFormModelBuilder
	 * @param array $fieldNames
	 * @param bool $addModule
	 *
	 */

	protected function addFieldsConfiguration($module, FormModelBuilder $pFormModelBuilder,
		array $fieldNames, $addModule = false)
	{
		foreach ($fieldNames as $category => $fields) {
			$slug = $this->generateGroupSlugByModuleCategory($module, $category);
			$pInputModelFieldsConfig = $pFormModelBuilder->createInputModelFieldsConfigByCategory
				($slug, $fields, $category);
			if ($addModule) {
				$pInputModelFieldsConfig->setModule($module ?? '');
			}

			$pFormModelFieldsConfig = new Model\FormModel();
			$pFormModelFieldsConfig->setPageSlug($this->getPageSlug());
			$pFormModelFieldsConfig->setGroupSlug($slug);
			$pFormModelFieldsConfig->setLabel($category);
			$pFormModelFieldsConfig->addInputModel($pInputModelFieldsConfig);
			$this->addFormModel($pFormModelFieldsConfig);
		}
	}


	/**
	 *
	 * @param array $modules
	 * @param FormModelBuilder $pFormModelBuilder
	 * @param string $htmlType
	 *
	 */

	protected function addSortableFieldsList(array $modules, FormModelBuilder $pFormModelBuilder,
		$htmlType = InputModelBase::HTML_TYPE_COMPLEX_SORTABLE_DETAIL_LIST)
	{
		$pInputModelSortableFields = $pFormModelBuilder->createSortableFieldList($modules, $htmlType);
		$pFormModelSortableFields = new Model\FormModel();
		$pFormModelSortableFields->setPageSlug($this->getPageSlug());
		$pFormModelSortableFields->setGroupSlug
			(AdminPageEstateListSettings::FORM_VIEW_SORTABLE_FIELDS_CONFIG);
		$pFormModelSortableFields->setLabel(__('Fields Configuration', 'onoffice-for-wp-websites'));
		$pFormModelSortableFields->addInputModel($pInputModelSortableFields);
		$this->addFormModel($pFormModelSortableFields);

		$pFormHidden = new Model\FormModel();
		$pFormHidden->setIsInvisibleForm(true);

		foreach ($pInputModelSortableFields->getReferencedInputModels() as $pReference) {
			$pFormHidden->addInputModel($pReference);
		}

		$this->addFormModel($pFormHidden);
	}


	/**
	 *
	 * @param array $row
	 * @param stdClass $pResult having the properties `result` and `record_id`
	 * @param int $recordId
	 *
	 */

	abstract protected function updateValues(array $row, stdClass $pResult, $recordId = null);


	/**
	 *
	 */

	public function doExtraEnqueues()
	{
		wp_register_script('admin-js', plugin_dir_url(ONOFFICE_PLUGIN_DIR.'/index.php').'/js/admin.js',
			['jquery'], '', true);

		wp_register_script('oo-checkbox-js',
			plugin_dir_url(ONOFFICE_PLUGIN_DIR.'/index.php').'/js/checkbox.js', ['jquery'], '', true);
		wp_register_script('onoffice-default-form-values-js',
			plugin_dir_url(ONOFFICE_PLUGIN_DIR.'/index.php').'js/onoffice-default-form-values.js', ['onoffice-multiselect'], '', true);
		wp_register_script('onoffice-custom-form-label-js',
			plugin_dir_url(ONOFFICE_PLUGIN_DIR.'/index.php').'js/onoffice-custom-form-label.js', ['onoffice-multiselect'], '', true);

		wp_enqueue_script('postbox');
		wp_enqueue_script('admin-js');

		wp_register_script('chosen-jquery',
			plugin_dir_url(ONOFFICE_PLUGIN_DIR.'/index.php').'third_party/chosen/chosen.jquery.js', ['jquery'], '', true);

		wp_register_script('chosen-prism',
			plugin_dir_url(ONOFFICE_PLUGIN_DIR.'/index.php').'third_party/chosen/docsupport/prism.js', ['chosen-jquery'], '', true);

		wp_register_script('chosen-init',
			plugin_dir_url(ONOFFICE_PLUGIN_DIR.'/index.php').'third_party/chosen/docsupport/init.js', ['chosen-jquery', 'chosen-prism'], '', true);

		wp_enqueue_script('chosen-init');

		wp_register_script('oo-sanitize-shortcode-name',
			plugin_dir_url(ONOFFICE_PLUGIN_DIR.'/index.php').'/js/onoffice-sanitize-shortcode-name.js',
			['jquery'], '', true);
	}


	/**
	 *
	 * @param array $row
	 * @param string $table
	 * @return array
	 *
	 */

	protected function addOrderValues(array $row, $table)
	{
		if (array_key_exists($table, $row)) {
			array_walk($row[$table], function (&$value, $key) {
				$value['order'] = (int)$key + 1;
			});
		}
		return $row;
	}


	/**
	 *
	 * @param array $row
	 * @return array
	 *
	 */

	protected function setFixedValues(array $row)
	{
		return $row;
	}


	/**
	 *
	 */

	protected function generateMetaBoxes()
		{}


	/**
	 *
	 */

	protected function generateAccordionBoxes()
		{}


	/**
	 *
	 * @global array $wp_meta_boxes
	 *
	 */

	final protected function cleanPreviousBoxes()
	{
		global $wp_meta_boxes;
		$wp_meta_boxes[get_current_screen()->id] = array();
	}


	/** @return string */
	protected function getPageTitle()
		{ return $this->_pageTitle; }

	/** @param string $pageTitle */
	protected function setPageTitle($pageTitle)
		{ $this->_pageTitle = $pageTitle; }

		/** @return string */
	public function getListViewId()
		{ return $this->_listViewId; }
}
