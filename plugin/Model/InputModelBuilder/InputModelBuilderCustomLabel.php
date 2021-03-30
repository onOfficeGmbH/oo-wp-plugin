<?php

/**
 *
 *    Copyright (C) 2019 onOffice GmbH
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

declare (strict_types=1);

namespace onOffice\WPlugin\Model\InputModelBuilder;

use onOffice\WPlugin\Field\UnknownFieldException;
use onOffice\WPlugin\Model\InputModel\InputModelDBFactory;
use onOffice\WPlugin\Model\InputModel\InputModelDBFactoryConfigForm;
use onOffice\WPlugin\Model\InputModelBase;
use onOffice\WPlugin\Model\InputModelDB;
use onOffice\WPlugin\Model\InputModelOption;
use onOffice\WPlugin\Types\Field;
use onOffice\WPlugin\Types\FieldsCollection;
use onOffice\WPlugin\Types\FieldTypes;

class InputModelBuilderCustomLabel
{
	/** @var InputModelDBFactoryConfigForm */
	private $_pInputModelDBFactoryConfigForm;


	/**
	 *
	 * @param InputModelDBFactoryConfigForm $pInputModelDBFactoryConfigForm
	 *
	 */

	public function __construct(InputModelDBFactoryConfigForm $pInputModelDBFactoryConfigForm)
	{
		$this->_pInputModelDBFactoryConfigForm = $pInputModelDBFactoryConfigForm;
	}


	/**
	 *
	 * @param FieldsCollection $pFieldsCollection
	 * @param array $presetValuesCustomLabel
	 * @return InputModelDB
	 *
	 */

	public function createInputModelCustomLabel(FieldsCollection $pFieldsCollection,
		array $presetValuesCustomLabel): InputModelDB
	{
		$pFieldsCollectionClone = $this->cloneFieldsCollectionWithDummyField($pFieldsCollection);
		$pInputModelFactory = new InputModelDBFactory($this->_pInputModelDBFactoryConfigForm);
		$label = __('Custom Label', 'onoffice-for-wp-websites');
		$type = InputModelDBFactoryConfigForm::INPUT_FORM_CUSTOM_LABEL;

		/** @var InputModelDB $pInputModel */
		$pInputModel = $pInputModelFactory->create($type, $label, true);
		$pInputModel->setHtmlType(InputModelBase::HTML_TYPE_TEXT);
		$pInputModel->setValueCallback(function(InputModelBase $pInputModel, string $key) use
			($presetValuesCustomLabel, $pFieldsCollectionClone) {
				try {
					$pField = $pFieldsCollectionClone->getFieldByKeyUnsafe($key);
					$this->callbackValueInputModelCustomLabel($pInputModel, $pField, $presetValuesCustomLabel);
				} catch (UnknownFieldException $pE) {}
			});
		return $pInputModel;
	}

	/**
	 * @param FieldsCollection $pFieldsCollection
	 * @return FieldsCollection
	 */
	private function cloneFieldsCollectionWithDummyField(FieldsCollection $pFieldsCollection): FieldsCollection
	{
		$pFieldsCollectionClone = clone $pFieldsCollection; // shallow copy
		$pDummyField = new Field('dummy_key', 'dummy_module');
		$pDummyField->setType(FieldTypes::FIELD_TYPE_INTEGER);
		$pFieldsCollectionClone->addField($pDummyField);
		return $pFieldsCollectionClone;
	}

	/**
	 *
	 * @param InputModelBase $pInputModel
	 * @param Field $pField
	 * @param array $presetValuesCustomLabel
	 *
	 */

	public function callbackValueInputModelCustomLabel(
		InputModelBase $pInputModel,
		Field $pField,
		array $presetValuesCustomLabel)
	{
		$fieldsCustomLabel = $presetValuesCustomLabel[$pField->getName()] ?? '';
		$pInputModel->setValue($fieldsCustomLabel);
		$type = InputModelOption::HTML_TYPE_TEXT;
		$pInputModel->setHtmlType($type);
		$pInputModel->setLabelOnlyValues($pField->getLabelOnlyValues());
	}
}
