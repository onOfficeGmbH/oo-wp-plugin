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

namespace onOffice\WPlugin\Gui;

use onOffice\SDK\onOfficeSDK;
use onOffice\WPlugin\Controller\SortList\SortListTypes;
use onOffice\WPlugin\DataView\DataListView;
use onOffice\WPlugin\DataView\DataListViewFactory;
use onOffice\WPlugin\DataView\UnknownViewException;
use onOffice\WPlugin\Field\FieldModuleCollectionDecoratorGeoPositionBackend;
use onOffice\WPlugin\Model\FormModel;
use onOffice\WPlugin\Model\FormModelBuilder\FormModelBuilderDBEstateListSettings;
use onOffice\WPlugin\Model\InputModel\InputModelDBFactoryConfigEstate;
use onOffice\WPlugin\Model\InputModelBuilder\InputModelBuilderGeoRange;
use onOffice\WPlugin\Model\InputModelLabel;
use onOffice\WPlugin\Record\BooleanValueToFieldList;
use onOffice\WPlugin\Record\RecordManagerReadListViewEstate;
use onOffice\WPlugin\Types\FieldsCollection;
use onOffice\WPlugin\Record\RecordManager;
use stdClass;
use function __;
use function add_screen_option;
use function wp_enqueue_script;
use function wp_localize_script;

/**
 *
 * @url http://www.onoffice.de
 * @copyright 2003-2017, onOffice(R) GmbH
 *
 */

class AdminPageEstateListSettings
	extends AdminPageEstateListSettingsBase
{
	/** */
	const FORM_VIEW_GEOFIELDS = 'geofields';

	/**
	 *
	 * @param string $pageSlug
	 *
	 */

	public function __construct($pageSlug)
	{
		parent::__construct($pageSlug);
		$this->setPageTitle(__('Edit List View', 'onoffice-for-wp-websites'));
	}


	/**
	 *
	 */

	protected function buildForms()
	{
		add_screen_option('layout_columns', array('max' => 2, 'default' => 2) );
		$pFormModelBuilder = new FormModelBuilderDBEstateListSettings();
		$pFormModel = $pFormModelBuilder->generate($this->getPageSlug(), $this->getListViewId());
		$this->addFormModel($pFormModel);

		$pInputModelName = $pFormModelBuilder->createInputModelName();
		$pFormModelName = new FormModel();
		$pFormModelName->setPageSlug($this->getPageSlug());
		$pFormModelName->setGroupSlug(self::FORM_RECORD_NAME);
		$pFormModelName->setLabel(__('choose name', 'onoffice-for-wp-websites'));
		$pFormModelName->addInputModel($pInputModelName);
		$this->addFormModel($pFormModelName);

		$pInputModelFilter = $pFormModelBuilder->createInputModelFilter();
		$pInputModelRecordsPerPage = $pFormModelBuilder->createInputModelRecordsPerPage();
		$pInputModelSortBy = $pFormModelBuilder->createInputModelSortBy(onOfficeSDK::MODULE_ESTATE);
		$pInputModelSortOrder = $pFormModelBuilder->createInputModelSortOrder();
		$pInputModelListType = $pFormModelBuilder->createInputModelListType();
		$pInputModelShowStatus = $pFormModelBuilder->createInputModelShowStatus();
		$pInputModelRandomSort = $pFormModelBuilder->createInputModelRandomSort();

		$pInputModelSortBySetting = $pFormModelBuilder->createInputModelSortBySetting();
		$pInputModelSortByChosen = $pFormModelBuilder->createInputModelSortByChosen();
		$pInputModelSortByDefault = $pFormModelBuilder->createInputModelSortByDefault();
		$pInputModelSortByspec = $pFormModelBuilder->createInputModelSortBySpec();

		$pFormModelRecordsFilter = new FormModel();
		$pFormModelRecordsFilter->setPageSlug($this->getPageSlug());
		$pFormModelRecordsFilter->setGroupSlug(self::FORM_VIEW_RECORDS_FILTER);
		$pFormModelRecordsFilter->setLabel(__('Filters & Records', 'onoffice-for-wp-websites'));
		$pFormModelRecordsFilter->addInputModel($pInputModelFilter);
		$pFormModelRecordsFilter->addInputModel($pInputModelRecordsPerPage);
		$pFormModelRecordsFilter->addInputModel($pInputModelSortBySetting);
		$pFormModelRecordsFilter->addInputModel($pInputModelSortByChosen);
		$pFormModelRecordsFilter->addInputModel($pInputModelSortByDefault);
		$pFormModelRecordsFilter->addInputModel($pInputModelSortByspec);
		$pFormModelRecordsFilter->addInputModel($pInputModelSortBy);
		$pFormModelRecordsFilter->addInputModel($pInputModelSortOrder);
		$pFormModelRecordsFilter->addInputModel($pInputModelRandomSort);

		$pFormModelRecordsFilter->addInputModel($pInputModelListType);
		$this->addFormModel($pFormModelRecordsFilter);

		$pInputModelTemplate = $pFormModelBuilder->createInputModelTemplate('estate');
		$pFormModelLayoutDesign = new FormModel();
		$pFormModelLayoutDesign->setPageSlug($this->getPageSlug());
		$pFormModelLayoutDesign->setGroupSlug(self::FORM_VIEW_LAYOUT_DESIGN);
		$pFormModelLayoutDesign->setLabel(__('Layout & Design', 'onoffice-for-wp-websites'));
		$pFormModelLayoutDesign->addInputModel($pInputModelTemplate);
		$pFormModelLayoutDesign->addInputModel($pInputModelShowStatus);
		$this->addFormModel($pFormModelLayoutDesign);

		$pInputModelPictureTypes = $pFormModelBuilder->createInputModelPictureTypes();
		$pFormModelPictureTypes = new FormModel();
		$pFormModelPictureTypes->setPageSlug($this->getPageSlug());
		$pFormModelPictureTypes->setGroupSlug(self::FORM_VIEW_PICTURE_TYPES);
		$pFormModelPictureTypes->setLabel(__('Photo Types', 'onoffice-for-wp-websites'));
		$pFormModelPictureTypes->addInputModel($pInputModelPictureTypes);
		$this->addFormModel($pFormModelPictureTypes);

		$pInputModelDocumentTypes = $pFormModelBuilder->createInputModelExpose();
		$pFormModelDocumentTypes = new FormModel();
		$pFormModelDocumentTypes->setPageSlug($this->getPageSlug());
		$pFormModelDocumentTypes->setGroupSlug(self::FORM_VIEW_DOCUMENT_TYPES);
		$pFormModelDocumentTypes->setLabel(__('Downloadable Documents', 'onoffice-for-wp-websites'));
		$pFormModelDocumentTypes->addInputModel($pInputModelDocumentTypes);
		$this->addFormModel($pFormModelDocumentTypes);

		$pListView = new DataListView($this->getListViewId() ?? 0, '');

		$pFormModelGeoFields = new FormModel();
		$pFormModelGeoFields->setPageSlug($this->getPageSlug());
			$pFormModelGeoFields->setGroupSlug(self::FORM_VIEW_GEOFIELDS);
		$pFormModelGeoFields->setLabel(__('Geo Fields', 'onoffice-for-wp-websites'));
		$pInputModelBuilderGeoRange = new InputModelBuilderGeoRange(onOfficeSDK::MODULE_ESTATE);
		foreach ($pInputModelBuilderGeoRange->build($pListView) as $pInputModel) {
			$pFormModelGeoFields->addInputModel($pInputModel);
		}

		$geoNotice = __('At least the following fields must be active: country, radius and city or postcode.', 'onoffice-for-wp-websites');
		$pInputModelGeoLabel = new InputModelLabel(null, $geoNotice);
		$pInputModelGeoLabel->setValueEnclosure(InputModelLabel::VALUE_ENCLOSURE_ITALIC);
		$pFormModelGeoFields->addInputModel($pInputModelGeoLabel);

		$this->addFormModel($pFormModelGeoFields);

		$pFieldCollection = new FieldModuleCollectionDecoratorGeoPositionBackend(new FieldsCollection());
		$fieldNames = $this->readFieldnamesByContent(onOfficeSDK::MODULE_ESTATE, $pFieldCollection);

		$this->addFieldsConfiguration(onOfficeSDK::MODULE_ESTATE, $pFormModelBuilder, $fieldNames);
		$this->addSortableFieldsList([onOfficeSDK::MODULE_ESTATE], $pFormModelBuilder);
	}


	/**
	 *
	 */

	protected function generateMetaBoxes()
	{
		$pFormRecordsFilter = $this->getFormModelByGroupSlug(self::FORM_VIEW_RECORDS_FILTER);
		$this->createMetaBoxByForm($pFormRecordsFilter, 'normal');

		$pFormPictureTypes = $this->getFormModelByGroupSlug(self::FORM_VIEW_PICTURE_TYPES);
		$this->createMetaBoxByForm($pFormPictureTypes, 'side');

		$pFormLayoutDesign = $this->getFormModelByGroupSlug(self::FORM_VIEW_LAYOUT_DESIGN);
		$this->createMetaBoxByForm($pFormLayoutDesign, 'side');

		$pFormDocumentTypes = $this->getFormModelByGroupSlug(self::FORM_VIEW_DOCUMENT_TYPES);
		$this->createMetaBoxByForm($pFormDocumentTypes, 'normal');

		$pFormGeoPosition = $this->getFormModelByGroupSlug(self::FORM_VIEW_GEOFIELDS);
		$this->createMetaBoxByForm($pFormGeoPosition, 'normal');
	}


	/**
	 *
	 */

	protected function generateAccordionBoxes()
	{
		$this->cleanPreviousBoxes();
		$module = onOfficeSDK::MODULE_ESTATE;

		$pFieldCollection = new FieldModuleCollectionDecoratorGeoPositionBackend(new FieldsCollection());
		$fieldNames = array_keys($this->readFieldnamesByContent($module, $pFieldCollection));

		foreach ($fieldNames as $category) {
			$slug = $this->generateGroupSlugByModuleCategory($module, $category);
			$pFormFieldsConfig = $this->getFormModelByGroupSlug($slug);
			$pFormFieldsConfig->setOoModule($module);
			$this->createMetaBoxByForm($pFormFieldsConfig, 'side');
		}
	}


	/**
	 *
	 * Since checkboxes are only being submitted if checked they need to be reorganized
	 * @todo Examine booleans automatically
	 *
	 * @param stdClass $pValues
	 *
	 */

	protected function prepareValues(stdClass $pValues)
	{
		$pBoolToFieldList = new BooleanValueToFieldList(new InputModelDBFactoryConfigEstate, $pValues);
		$pBoolToFieldList->fillCheckboxValues(InputModelDBFactoryConfigEstate::INPUT_FIELD_FILTERABLE);
		$pBoolToFieldList->fillCheckboxValues(InputModelDBFactoryConfigEstate::INPUT_FIELD_HIDDEN);
		$pBoolToFieldList->fillCheckboxValues(InputModelDBFactoryConfigEstate::INPUT_FIELD_AVAILABLE_OPTIONS);
	}


	/**
	 *
	 * @param int $recordId
	 * @throws UnknownViewException
	 *
	 */

	protected function validate($recordId = null)
	{
		if ($recordId == null) {
			return;
		}

		$pRecordReadManager = new RecordManagerReadListViewEstate();
		$values = $pRecordReadManager->getRowById($recordId);
		$pFactory = new DataListViewFactory();
		$pDataListView = $pFactory->createListViewByRow($values);

		if (!in_array($pDataListView->getListType(), array('default', 'reference', 'favorites'))) {
			throw new UnknownViewException;
		}
	}


	/**
	 *
	 */

	public function doExtraEnqueues()
	{
		parent::doExtraEnqueues();
		wp_enqueue_script('oo-checkbox-js');
		wp_localize_script('oo-sanitize-shortcode-name', 'shortcode', ['name' => 'oopluginlistviews-name']);
		wp_enqueue_script('oo-sanitize-shortcode-name');
	}
}
