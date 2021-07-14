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

class InputModelBuilderDefaultValue
{
	/** */
	const FIELD_TYPE_TO_HTML_TYPE_MAPPING = [
		FieldTypes::FIELD_TYPE_TEXT => InputModelOption::HTML_TYPE_TEXT,
		FieldTypes::FIELD_TYPE_VARCHAR => InputModelOption::HTML_TYPE_TEXT,
		FieldTypes::FIELD_TYPE_DATE => InputModelOption::HTML_TYPE_TEXT,
		FieldTypes::FIELD_TYPE_DATETIME => InputModelOption::HTML_TYPE_TEXT,
		FieldTypes::FIELD_TYPE_FLOAT => InputModelOption::HTML_TYPE_NUMBER,
		FieldTypes::FIELD_TYPE_INTEGER => InputModelOption::HTML_TYPE_NUMBER,
		FieldTypes::FIELD_TYPE_MULTISELECT => InputModelOption::HTML_TYPE_SELECT,
		FieldTypes::FIELD_TYPE_SINGLESELECT => InputModelOption::HTML_TYPE_SELECT,
		FieldTypes::FIELD_TYPE_BOOLEAN => InputModelOption::HTML_TYPE_SELECT,
	];


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
	 * @param array $presetValuesDefaultValue
	 * @return InputModelDB
	 *
	 */

	public function createInputModelDefaultValue(FieldsCollection $pFieldsCollection,
		array $presetValuesDefaultValue): InputModelDB
	{
		$pFieldsCollectionClone = $this->cloneFieldsCollectionWithDummyField($pFieldsCollection);
		$pInputModelFactory = new InputModelDBFactory($this->_pInputModelDBFactoryConfigForm);
		$label = __('Default Value', 'onoffice-for-wp-websites');
		$type = InputModelDBFactoryConfigForm::INPUT_FORM_DEFAULT_VALUE;
		/** @var InputModelDB $pInputModel */
		$pInputModel = $pInputModelFactory->create($type, $label, true);
		$pInputModel->setHtmlType(InputModelBase::HTML_TYPE_TEXT);
		$pInputModel->setValueCallback(function(InputModelBase $pInputModel, string $key) use
			($presetValuesDefaultValue, $pFieldsCollectionClone) {
				try {
					$pField = $pFieldsCollectionClone->getFieldByKeyUnsafe($key);
					$this->callbackValueInputModelDefaultValue($pInputModel, $pField, $presetValuesDefaultValue);
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
	 * @param array $presetValuesDefaultValue
	 *
	 */

	public function callbackValueInputModelDefaultValue(
		InputModelBase $pInputModel,
		Field $pField,
		array $presetValuesDefaultValue)
	{
		$fieldsDefaultValue = $presetValuesDefaultValue[$pField->getName()] ?? '';
		$pInputModel->setValue($fieldsDefaultValue);
		$pInputModel->setValuesAvailable(['' => ''] + $pField->getPermittedvalues());
		$type = self::FIELD_TYPE_TO_HTML_TYPE_MAPPING[$pField->getType()] ?? InputModelOption::HTML_TYPE_TEXT;
		$pInputModel->setHtmlType($type);
		$pInputModel->setLabelOnlyValues($pField->getLabelOnlyValues());
	}
}
