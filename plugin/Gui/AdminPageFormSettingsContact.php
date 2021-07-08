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
use onOffice\WPlugin\DataFormConfiguration\DataFormConfiguration;
use onOffice\WPlugin\Field\FieldModuleCollectionDecoratorFormContact;
use onOffice\WPlugin\Model\FormModel;
use onOffice\WPlugin\Model\FormModelBuilder\FormModelBuilder;
use onOffice\WPlugin\Model\InputModel\InputModelConfigurationFormContact;
use onOffice\WPlugin\Model\InputModel\InputModelDBBuilderGeneric;
use onOffice\WPlugin\Model\InputModel\InputModelDBFactory;
use onOffice\WPlugin\Model\InputModel\InputModelDBFactoryConfigForm;
use onOffice\WPlugin\Model\InputModelBase;
use onOffice\WPlugin\Model\InputModelBuilder\InputModelBuilderGeoRange;
use onOffice\WPlugin\Record\RecordManagerReadForm;
use onOffice\WPlugin\Types\FieldsCollection;
use function __;

/**
 *
 */

class AdminPageFormSettingsContact
	extends AdminPageFormSettingsBase
{
	/** */
	const FORM_VIEW_GEOFIELDS = 'geofields';

	/** @var bool message field has no module */
	private $_showMessageInput = false;

	/** @var array */
	private $_additionalCategories = array();

	/** @var bool */
	private $_showCreateAddress = false;

	/** @var bool */
	private $_showCheckDuplicates = false;

	/** @var bool */
	private $_showNewsletterCheckbox = false;

	/** @var bool */
	private $_showGeoPositionSettings = false;

	/** @var bool */
	private $_showEstateContextCheckbox = false;

    /** @var bool */
    private $_showContactTypeSelect = false;


	/**
	 *
	 */

	protected function buildForms()
	{
		parent::buildForms();
		$pFormModelBuilder = $this->getFormModelBuilder();
		$pConfigForm = new InputModelDBFactoryConfigForm();
		$pInputModelDBFactory = new InputModelDBFactory($pConfigForm);
		$pInputModelConfiguration = new InputModelConfigurationFormContact();
		$pInputModelBuilder = new InputModelDBBuilderGeneric($pInputModelDBFactory, $pInputModelConfiguration);
		$pRecordReadManager = new RecordManagerReadForm();

		if ($this->getListViewId() != null) {
			$values = $pRecordReadManager->getRowById($this->getListViewId());
			$pInputModelBuilder->setValues($values);
		}

		$pInputModelRecipient = $pFormModelBuilder->createInputModelRecipient();
		$pInputModelSubject = $pInputModelBuilder->build(InputModelDBFactoryConfigForm::INPUT_FORM_SUBJECT);
		$pInputModelCaptcha = $pFormModelBuilder->createInputModelCaptchaRequired();
		$pFormModelFormSpecific = new FormModel();
		$pFormModelFormSpecific->setPageSlug($this->getPageSlug());
		$pFormModelFormSpecific->setGroupSlug(self::FORM_VIEW_FORM_SPECIFIC);
		$pFormModelFormSpecific->setLabel(__('Form Specific Options', 'onoffice-for-wp-websites'));
		$pFormModelFormSpecific->addInputModel($pInputModelRecipient);
		$pFormModelFormSpecific->addInputModel($pInputModelSubject);
		$pFormModelFormSpecific->addInputModel($pInputModelCaptcha);

		if ($this->_showCreateAddress) {
			$pInputModel = $pInputModelBuilder->build(InputModelDBFactoryConfigForm::INPUT_FORM_CREATEADDRESS);
			$pFormModelFormSpecific->addInputModel($pInputModel);
		}

		if ($this->_showCheckDuplicates) {
			$pInputModel = $pInputModelBuilder->build(InputModelDBFactoryConfigForm::INPUT_FORM_CHECKDUPLICATES);
			$pFormModelFormSpecific->addInputModel($pInputModel);
		}

		if ($this->_showNewsletterCheckbox) {
			$pInputModel = $pInputModelBuilder->build(InputModelDBFactoryConfigForm::INPUT_FORM_NEWSLETTER);
			$pFormModelFormSpecific->addInputModel($pInputModel);
		}

		if ($this->_showEstateContextCheckbox) {
			$pInputModel = $pInputModelBuilder->build(InputModelDBFactoryConfigForm::INPUT_FORM_ESTATE_CONTEXT_AS_HEADING);
			$pFormModelFormSpecific->addInputModel($pInputModel);
		}

		if ($this->_showContactTypeSelect) {
			$pInputModelContactType = $pFormModelBuilder->createInputModelContactType();
			$pFormModelFormSpecific->addInputModel($pInputModelContactType);
		}

		$this->addFormModel($pFormModelFormSpecific);
		$this->buildGeoPositionSettings();
		$this->addFieldConfigurationForMainModules($pFormModelBuilder);
		$this->buildMessagesInput($pFormModelBuilder);


		$this->addSortableFieldsList($this->getSortableFieldModules(), $pFormModelBuilder,
			InputModelBase::HTML_TYPE_COMPLEX_SORTABLE_DETAIL_LIST);
	}


	/**
	 *
	 * @param FormModelBuilder $pFormModelBuilder
	 *
	 */

	private function buildMessagesInput(FormModelBuilder $pFormModelBuilder)
	{
		if ($this->_showMessageInput) {
			$pFieldCollection = new FieldModuleCollectionDecoratorFormContact(new FieldsCollection());
			$category = __('Form Specific Fields', 'onoffice-for-wp-websites');
			$this->_additionalCategories []= $category;
			$pFieldMessage = $pFieldCollection->getFieldByModuleAndName('', 'message');

			$fieldNameMessage = [
				$category => [
					'message' => $pFieldMessage->getLabel(),
				],
			];
			$this->addFieldsConfiguration(null, $pFormModelBuilder, $fieldNameMessage, true);
			$this->addSortableFieldModule(null);
		}
	}


	/**
	 *
	 */

	private function buildGeoPositionSettings()
	{
		if ($this->_showGeoPositionSettings) {
			$pDataFormConfiguration = new DataFormConfiguration;
			$pDataFormConfiguration->setId($this->getListViewId() ?? 0);

			$pFormModelGeoFields = new FormModel();
			$pFormModelGeoFields->setPageSlug($this->getPageSlug());
			$pFormModelGeoFields->setGroupSlug(self::FORM_VIEW_GEOFIELDS);
			$pFormModelGeoFields->setLabel(__('Geo Fields', 'onoffice-for-wp-websites'));
			$pInputModelBuilderGeoRange = new InputModelBuilderGeoRange(onOfficeSDK::MODULE_SEARCHCRITERIA);
			foreach ($pInputModelBuilderGeoRange->build($pDataFormConfiguration) as $pInputModel) {
				$pFormModelGeoFields->addInputModel($pInputModel);
			}

			$this->addFormModel($pFormModelGeoFields);
		}
	}


	/**
	 *
	 */

	protected function generateMetaBoxes()
	{
		$pFormFormSpecific = $this->getFormModelByGroupSlug(self::FORM_VIEW_FORM_SPECIFIC);
		$this->createMetaBoxByForm($pFormFormSpecific, 'side');

		if ($this->_showGeoPositionSettings) {
			$pFormGeoPosition = $this->getFormModelByGroupSlug(self::FORM_VIEW_GEOFIELDS);
			$this->createMetaBoxByForm($pFormGeoPosition, 'side');
		}

		parent::generateMetaBoxes();
	}


	/**
	 *
	 */

	protected function generateAccordionBoxes()
	{
		parent::generateAccordionBoxes();

		foreach ($this->_additionalCategories as $category) {
			$slug = $this->generateGroupSlugByModuleCategory(null, $category);
			$pFormFieldsConfigCategory = $this->getFormModelByGroupSlug($slug);
			$this->createMetaBoxByForm($pFormFieldsConfigCategory, 'side');
		}
	}

	/** @param bool $showMessageInput */
	public function setShowMessageInput(bool $showMessageInput)
		{ $this->_showMessageInput = $showMessageInput; }

	/** @param bool $showCreateAddress */
	public function setShowCreateAddress(bool $showCreateAddress)
		{ $this->_showCreateAddress = $showCreateAddress; }

	/** @param bool $showCheckDuplicates */
	public function setShowCheckDuplicates(bool $showCheckDuplicates)
		{ $this->_showCheckDuplicates = $showCheckDuplicates; }

	/** @param bool $showNewsletterCheckbox */
	public function setShowNewsletterCheckbox(bool $showNewsletterCheckbox)
		{ $this->_showNewsletterCheckbox = $showNewsletterCheckbox; }

	/** @param bool $showGeoPositionSettings */
	public function setShowGeoPositionSettings(bool $showGeoPositionSettings)
		{ $this->_showGeoPositionSettings = $showGeoPositionSettings; }

	/** @param bool $showEstateContextCheckbox */
	public function setShowEstateContextCheckbox(bool $showEstateContextCheckbox)
		{ $this->_showEstateContextCheckbox = $showEstateContextCheckbox; }

    /** @param bool $showContactTypeSelect */
    public function setShowContactTypeSelect(bool $showContactTypeSelect)
		{ $this->_showContactTypeSelect = $showContactTypeSelect; }
}
