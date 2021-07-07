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

use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use onOffice\SDK\onOfficeSDK;
use onOffice\WPlugin\Controller\Exception\UnknownModuleException;
use onOffice\WPlugin\DataView\DataDetailViewHandler;
use onOffice\WPlugin\Field\Collection\FieldsCollectionBuilderShort;
use onOffice\WPlugin\Field\Collection\FieldsCollectionToContentFieldLabelArrayConverter;
use onOffice\WPlugin\Field\FieldModuleCollectionDecoratorGeoPositionBackend;
use onOffice\WPlugin\Field\FieldModuleCollectionDecoratorInternalAnnotations;
use onOffice\WPlugin\Field\FieldModuleCollectionDecoratorReadAddress;
use onOffice\WPlugin\Model\ExceptionInputModelMissingField;
use onOffice\WPlugin\Model\FormModel;
use onOffice\WPlugin\Model\FormModelBuilder\FormModelBuilderEstateDetailSettings;
use onOffice\WPlugin\Model\InputModel\InputModelOptionFactoryDetailView;
use onOffice\WPlugin\Model\InputModelBase;
use onOffice\WPlugin\Model\InputModelOption;
use onOffice\WPlugin\Model\InputModelOptionAdapterArray;
use onOffice\WPlugin\Renderer\InputModelRenderer;
use onOffice\WPlugin\Types\FieldsCollection;
use stdClass;
use function __;
use function add_action;
use function add_screen_option;
use function do_accordion_sections;
use function do_action;
use function do_meta_boxes;
use function do_settings_sections;
use function edit_post_link;
use function esc_attr;
use function esc_attr_e;
use function esc_html;
use function esc_html__;
use function get_current_screen;
use function get_the_title;
use function json_decode;
use function json_encode;
use function plugin_dir_url;
use function submit_button;
use function wp_die;
use function wp_enqueue_script;
use function wp_nonce_field;
use function wp_register_script;
use function wp_verify_nonce;
use const ONOFFICE_PLUGIN_DIR;

/**
 *
 */

class AdminPageEstateDetail
	extends AdminPageAjax
{
	/** */
	const VIEW_SAVE_SUCCESSFUL_MESSAGE = 'view_save_success_message';

	/** */
	const VIEW_SAVE_FAIL_MESSAGE = 'view_save_fail_message';

	/** */
	const FORM_VIEW_LAYOUT_DESIGN = 'viewlayoutdesign';

	/** */
	const FORM_VIEW_CONTACT_FORM = 'viewcontactform';

	/** */
	const FORM_VIEW_PICTURE_TYPES = 'viewpicturetypes';

	/** */
	const FORM_VIEW_ADDITIONAL_MEDIA = 'viewdocumenttypes';

	/** */
	const FORM_VIEW_CONTACT_DATA_FIELDS = 'viewcontactdatafields';

	/** */
	const FORM_VIEW_SORTABLE_FIELDS_CONFIG = 'viewSortableFieldsConfig';

	/**
	 *
	 */

	public function renderContent()
	{
		$pDataDetailViewHandler = new DataDetailViewHandler();
		$pDataView = $pDataDetailViewHandler->getDetailView();
		do_action('add_meta_boxes', get_current_screen()->id, null);
		$this->generateMetaBoxes();

		$pFieldsCollection = $this->readAllFields();

		/* @var $pRenderer InputModelRenderer */
		$pRenderer = $this->getContainer()->get(InputModelRenderer::class);
		$pFormViewSortableFields = $this->getFormModelByGroupSlug(self::FORM_VIEW_SORTABLE_FIELDS_CONFIG);
		$pFormViewSortablecontactFields = $this->getFormModelByGroupSlug(self::FORM_VIEW_CONTACT_DATA_FIELDS);

		wp_nonce_field( $this->getPageSlug() );

		$this->generatePageMainTitle(__('Edit List View', 'onoffice-for-wp-websites'));
		echo '<div id="onoffice-ajax">';
		wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
		wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
		echo '<div id="poststuff">';
		$pageId = $pDataView->getPageId();

		echo '<span class="viewusage">';
		if ($pageId != null) {
			/* translators: %s will be replaced with a link to the appropriate page. */
			printf(esc_attr(__('Detail view in use on page %s', 'onoffice-for-wp-websites')),
				'<span class="italic">'.esc_html(get_the_title($pageId)).'</span>');
			edit_post_link(__('Edit Page', 'onoffice-for-wp-websites'), ' ', '', $pageId);
		} else {
			esc_attr_e('Detail view is not in use yet. '
				.'Insert this code on a page to get the detail view there:', 'onoffice-for-wp-websites');
			echo ' <code>[oo_estate view="'.$pDataView->getName().'"]</code>';
		}
		echo '</span>';

		echo '<div id="post-body" class="metabox-holder columns-'
			.(1 == get_current_screen()->get_columns() ? '1' : '2').'">';
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
		$this->generateAccordionBoxesContactPerson($pFieldsCollection);
		echo '</div>';
		echo '<div id="listSettings" style="float:left;" class="postbox">';
		do_accordion_sections(get_current_screen()->id, 'contactperson', null);
		echo '</div>';
		echo '<div class="fieldsSortable postbox" id="'
			.esc_attr(self::getSpecialDivId(onOfficeSDK::MODULE_ADDRESS)).'">';
		echo '<h2 class="hndle ui-sortable-handle"><span>'.__('Contact Person Fields', 'onoffice-for-wp-websites').'</span></h2>';
		$pRenderer->buildForAjax($pFormViewSortablecontactFields);
		echo '</div>';
		echo '<div class="clear"></div>';

		echo '<div class="clear"></div>';
		do_action('add_meta_boxes', get_current_screen()->id, null);
		echo '<div style="float:left;">';
		$this->generateAccordionBoxes($pFieldsCollection);
		echo '</div>';
		echo '<div id="listSettings" style="float:left;" class="postbox">';
		do_accordion_sections(get_current_screen()->id, 'advanced', null);
		echo '</div>';
		echo '<div class="fieldsSortable postbox" id="'
			.esc_attr(self::getSpecialDivId(onOfficeSDK::MODULE_ESTATE)).'">';
		echo '<h2 class="hndle ui-sortable-handle"><span>'.__('Real Estate Fields', 'onoffice-for-wp-websites').'</span></h2>';
		$pRenderer->buildForAjax($pFormViewSortableFields);
		echo '</div>';
		echo '<div class="clear"></div>';
		echo '</div>';
		echo '</div>';

		do_settings_sections($this->getPageSlug());
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
	 * @param string $subTitle
	 *
	 */

	public function generatePageMainTitle($subTitle)
	{
		echo '<h1 class="wp-heading-inline">'.esc_html__('onOffice', 'onoffice-for-wp-websites');
		echo ' › '.esc_html__($subTitle, 'onoffice-for-wp-websites');
		echo ' › '.esc_html__('Detail View', 'onoffice-for-wp-websites');
		echo '</h1>';
		echo '<hr class="wp-header-end">';
	}


	/**
	 *
	 */

	private function generateMetaBoxes()
	{
		$pFormPictureTypes = $this->getFormModelByGroupSlug(self::FORM_VIEW_PICTURE_TYPES);
		$this->createMetaBoxByForm($pFormPictureTypes, 'side');

		$pFormLayoutDesign = $this->getFormModelByGroupSlug(self::FORM_VIEW_LAYOUT_DESIGN);
		$this->createMetaBoxByForm($pFormLayoutDesign, 'normal');

		$pFormContactForm = $this->getFormModelByGroupSlug(self::FORM_VIEW_CONTACT_FORM);
		$this->createMetaBoxByForm($pFormContactForm, 'normal');

		$pFormDocumentTypes = $this->getFormModelByGroupSlug(self::FORM_VIEW_ADDITIONAL_MEDIA);
		$this->createMetaBoxByForm($pFormDocumentTypes, 'side');

	}

	/**
	 * @param FieldsCollection $pFieldsCollection
	 * @throws DependencyException
	 * @throws NotFoundException
	 */
	protected function generateAccordionBoxes(FieldsCollection $pFieldsCollection)
	{
		$pFieldsCollectionConverter = $this->getContainer()->get(FieldsCollectionToContentFieldLabelArrayConverter::class);
		$fieldsEstate = $pFieldsCollectionConverter->convert($pFieldsCollection, onOfficeSDK::MODULE_ESTATE);

		foreach (array_keys($fieldsEstate) as $category) {
			$pFormFieldsConfig = $this->getFormModelByGroupSlug(onOfficeSDK::MODULE_ESTATE.$category);
			$this->createMetaBoxByForm($pFormFieldsConfig, 'advanced');
		}
	}

	/**
	 * @param FieldsCollection $pFieldsCollection
	 * @throws DependencyException
	 * @throws NotFoundException
	 */
	protected function generateAccordionBoxesContactPerson(FieldsCollection $pFieldsCollection)
	{
		$pFieldsCollectionConverter = $this->getContainer()->get(FieldsCollectionToContentFieldLabelArrayConverter::class);
		$fieldNamesContactData = $pFieldsCollectionConverter->convert($pFieldsCollection, onOfficeSDK::MODULE_ADDRESS);

		foreach (array_keys($fieldNamesContactData) as $category) {
			$pFormFieldsConfig = $this->getFormModelByGroupSlug(onOfficeSDK::MODULE_ADDRESS.$category);
			$this->createMetaBoxByForm($pFormFieldsConfig, 'contactperson');
		}
	}

	/**
	 *
	 */
	protected function buildForms()
	{
		add_screen_option('layout_columns', array('max' => 2, 'default' => 2) );
		$pFormModelBuilder = new FormModelBuilderEstateDetailSettings();
		$pFormModel = $pFormModelBuilder->generate($this->getPageSlug());
		$this->addFormModel($pFormModel);

		$pInputModelTemplate = $pFormModelBuilder->createInputModelTemplate();
		$pInputShowStatus = $pFormModelBuilder->createInputModelShowStatus();
		$pFormModelLayoutDesign = new FormModel();
		$pFormModelLayoutDesign->setPageSlug($this->getPageSlug());
		$pFormModelLayoutDesign->setGroupSlug(self::FORM_VIEW_LAYOUT_DESIGN);
		$pFormModelLayoutDesign->setLabel(__('Layout & Design', 'onoffice-for-wp-websites'));
		$pFormModelLayoutDesign->addInputModel($pInputModelTemplate);
		$pFormModelLayoutDesign->addInputModel($pInputShowStatus);
		$this->addFormModel($pFormModelLayoutDesign);

		$pInputModelShortCodeForm = $pFormModelBuilder->createInputModelShortCodeForm();
		$pFormModelShortCodeForm = new FormModel();
		$pFormModelShortCodeForm->setPageSlug($this->getPageSlug());
		$pFormModelShortCodeForm->setGroupSlug(self::FORM_VIEW_CONTACT_FORM);
		$pFormModelShortCodeForm->setLabel(__('Contact Form', 'onoffice-for-wp-websites'));
		$pFormModelShortCodeForm->addInputModel($pInputModelShortCodeForm);
		$this->addFormModel($pFormModelShortCodeForm);

		$pInputModelPictureTypes = $pFormModelBuilder->createInputModelPictureTypes();
		$pFormModelPictureTypes = new FormModel();
		$pFormModelPictureTypes->setPageSlug($this->getPageSlug());
		$pFormModelPictureTypes->setGroupSlug(self::FORM_VIEW_PICTURE_TYPES);
		$pFormModelPictureTypes->setLabel(__('Photo Types', 'onoffice-for-wp-websites'));
		$pFormModelPictureTypes->addInputModel($pInputModelPictureTypes);
		$this->addFormModel($pFormModelPictureTypes);

		$pInputModelDocumentTypes = $pFormModelBuilder->createInputModelExpose();
		$pInputModelMovieLinks = $pFormModelBuilder->createInputModelMovieLinks();
		$pFormModelDocumentTypes = new FormModel();
		$pFormModelDocumentTypes->setPageSlug($this->getPageSlug());
		$pFormModelDocumentTypes->setGroupSlug(self::FORM_VIEW_ADDITIONAL_MEDIA);
		$pFormModelDocumentTypes->setLabel(__('Additional Media', 'onoffice-for-wp-websites'));
		$pFormModelDocumentTypes->addInputModel($pInputModelDocumentTypes);
		$pFormModelDocumentTypes->addInputModel($pInputModelMovieLinks);
		$this->addFormModel($pFormModelDocumentTypes);

		$pFieldsCollection = $this->readAllFields();
		$pFieldsCollectionConverter = $this->getContainer()->get(FieldsCollectionToContentFieldLabelArrayConverter::class);
		$fieldsEstate = $pFieldsCollectionConverter->convert($pFieldsCollection, onOfficeSDK::MODULE_ESTATE);
		$fieldsAddress = $pFieldsCollectionConverter->convert($pFieldsCollection, onOfficeSDK::MODULE_ADDRESS);
		$this->addFieldsConfiguration(onOfficeSDK::MODULE_ESTATE,
			self::FORM_VIEW_SORTABLE_FIELDS_CONFIG, $pFormModelBuilder, $fieldsEstate);
		$this->addFieldsConfiguration(onOfficeSDK::MODULE_ADDRESS,
			self::FORM_VIEW_CONTACT_DATA_FIELDS, $pFormModelBuilder, $fieldsAddress);
	}

	/**
	 * @return FieldsCollection
	 * @throws DependencyException
	 * @throws NotFoundException
	 */
	private function readAllFields(): FieldsCollection
	{
		$pFieldsCollection = new FieldsCollection;
		$pFieldsCollection->merge
			(new FieldModuleCollectionDecoratorInternalAnnotations
				(new FieldModuleCollectionDecoratorGeoPositionBackend
					(new FieldModuleCollectionDecoratorReadAddress(new FieldsCollection()))));
		$this->getContainer()->get(FieldsCollectionBuilderShort::class)
			->addFieldsAddressEstate($pFieldsCollection);
		return $pFieldsCollection;
	}

	/**
	 *
	 */
	public function ajax_action()
	{
		$this->buildForms();
		$action = filter_input(INPUT_POST, 'action');
		$nonce = filter_input(INPUT_POST, 'nonce');

		if (!wp_verify_nonce($nonce, $action)) {
			wp_die();
		}

		$values = json_decode(filter_input(INPUT_POST, 'values'));
		$pInputModelDBAdapterArray = new InputModelOptionAdapterArray();

		foreach ($this->getFormModels() as $pFormModel) {
			foreach ($pFormModel->getInputModel() as $pInputModel) {
				if ($pInputModel instanceof InputModelOption) {
					$identifier = $pInputModel->getIdentifier();

					$value = isset($values->$identifier) ? $values->$identifier : null;
					$pInputModel->setValue($value);
					$pInputModelDBAdapterArray->addInputModelOption($pInputModel);
				}
			}
		}

		$pDataDetailViewHandler = new DataDetailViewHandler();
		$valuesPrefixless = $pInputModelDBAdapterArray->generateValuesArray();
		$pDataDetailView = $pDataDetailViewHandler->createDetailViewByValues($valuesPrefixless);
		$pResultObject = new stdClass();

		try {
			$pDataDetailViewHandler->saveDetailView($pDataDetailView);
			$pResultObject->result = true;
		} catch (Exception $pEx) {
			$pResultObject->result = false;
		}

		$pResultObject->record_id = null;
		$pResultObject->messageKey = $pResultObject->result ?
			self::VIEW_SAVE_SUCCESSFUL_MESSAGE :
			self::VIEW_SAVE_FAIL_MESSAGE;

		echo json_encode($pResultObject);

		wp_die();
	}

	/**
	 *
	 */
	public function doExtraEnqueues()
	{
		wp_register_script('admin-js', plugin_dir_url(ONOFFICE_PLUGIN_DIR.'/index.php').'/js/admin.js',
			array('jquery'), '', true);

		wp_enqueue_script('admin-js');
		wp_enqueue_script('postbox');
	}

	/**
	 *
	 */
	public function handleAdminNotices()
	{
		add_action('admin_notices', array($this, 'addAdminNoticeWrapper'));
	}

	/**
	 * rest will be added via js
	 */
	public function addAdminNoticeWrapper()
	{
		echo '<div id="onoffice-notice-wrapper"></div>';
	}

	/**
	 * @return array
	 */
	public function getEnqueueData(): array
	{
		return array(
			self::VIEW_SAVE_SUCCESSFUL_MESSAGE => __('The detail view has been saved.', 'onoffice-for-wp-websites'),
			self::VIEW_SAVE_FAIL_MESSAGE => __('There was a problem saving the detail view.', 'onoffice-for-wp-websites'),
			AdminPageEstate::PARAM_TAB => AdminPageEstate::PAGE_ESTATE_DETAIL,
			self::ENQUEUE_DATA_MERGE => array(AdminPageEstate::PARAM_TAB),
		);
	}

	/**
	 * @param string $module
	 * @param $groupSlug
	 * @param FormModelBuilderEstateDetailSettings $pFormModelBuilder
	 * @param array $fieldNames
	 * @throws UnknownModuleException
	 * @throws ExceptionInputModelMissingField
	 */
	private function addFieldsConfiguration($module, $groupSlug, FormModelBuilderEstateDetailSettings $pFormModelBuilder,
		array $fieldNames)
	{
		foreach ($fieldNames as $category => $fields) {
			$pInputModelFieldsConfig = $pFormModelBuilder->createInputModelFieldsConfigByCategory
				($module.$category, $fields, $category);
			$pInputModelFieldsConfig->setSpecialDivId(self::getSpecialDivId($module));
			$pFormModelFieldsConfig = new FormModel();
			$pFormModelFieldsConfig->setPageSlug($this->getPageSlug());
			$pFormModelFieldsConfig->setGroupSlug($module.$category);
			$pFormModelFieldsConfig->setLabel($category);
			$pFormModelFieldsConfig->addInputModel($pInputModelFieldsConfig);
			$this->addFormModel($pFormModelFieldsConfig);
		}

		$pInputModelSortableFields = $pFormModelBuilder->createSortableFieldList($module,
			InputModelBase::HTML_TYPE_COMPLEX_SORTABLE_DETAIL_LIST);
		$pFormModelSortableFields = new FormModel();
		$pFormModelSortableFields->setPageSlug($this->getPageSlug());
		$pFormModelSortableFields->setGroupSlug($groupSlug);
		$pFormModelSortableFields->addInputModel($pInputModelSortableFields);
		$this->addFormModel($pFormModelSortableFields);

		$pFormHidden = new FormModel();
		$pFormHidden->setIsInvisibleForm(true);

		foreach ($pInputModelSortableFields->getReferencedInputModels() as $pReference) {
			$pFormHidden->addInputModel($pReference);
		}

		$this->addFormModel($pFormHidden);
	}


	/**
	 * @param string $module
	 * @return string
	 */
	private static function getSpecialDivId($module)
	{
		return 'actionFor'.$module;
	}
}
