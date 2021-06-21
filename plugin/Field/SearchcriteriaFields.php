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

namespace onOffice\WPlugin\Field;

use DI\DependencyException;
use DI\NotFoundException;
use onOffice\SDK\onOfficeSDK;
use onOffice\WPlugin\Field\Collection\FieldsCollectionBuilderShort;
use onOffice\WPlugin\Field\Collection\FieldsCollectionConfiguratorForm;
use onOffice\WPlugin\GeoPosition;
use onOffice\WPlugin\Types\FieldsCollection;
use onOffice\WPlugin\Types\FieldTypes;
use onOffice\WPlugin\Utility\__String;

/**
 *
 */

class SearchcriteriaFields
{
	/** */
	const RANGE_FROM = '__von';

	/** */
	const RANGE_UPTO = '__bis';

	/** @var FieldsCollectionBuilderShort */
	private $_pFieldsCollectionBuilder = null;

	/**
	 * @param FieldsCollectionBuilderShort $pFieldsCollectionBuilder
	 */
	public function __construct(FieldsCollectionBuilderShort $pFieldsCollectionBuilder)
	{
		$this->_pFieldsCollectionBuilder = $pFieldsCollectionBuilder;
	}


	/**
	 * SearchCriteria fields have the suffixes `__von` and `__bis`
	 *
	 * @param string $input
	 * @return string
	 *
	 */
	public function getFieldNameOfInput($input): string
	{
		$inputConfigName = $input;
		$pInputStr = __String::getNew($input);

		if ($pInputStr->endsWith('__von') ||
			$pInputStr->endsWith('__bis')) {
			$inputConfigName = $pInputStr->sub(0, -5);
		}

		return $inputConfigName;
	}

	/**
	 * @param array $inputFormFields
	 * @return array
	 * @throws DependencyException
	 * @throws NotFoundException
	 */
	public function getFormFields(array $inputFormFields): array
	{
		$pFieldsCollection = $this->loadSearchCriteriaFields();
		return $this->generateFieldModuleArray($pFieldsCollection, $inputFormFields);
	}

	/**
	 * @param array $inputFormFields
	 * @return array
	 * @throws DependencyException
	 * @throws NotFoundException
	 */
	public function getFieldLabelsOfInputs(array $inputFormFields): array
	{
		$pFieldsCollection = new FieldsCollection();
		$this->_pFieldsCollectionBuilder
			->addFieldsSearchCriteria($pFieldsCollection)
			->addFieldsAddressEstate($pFieldsCollection);

		$pGeoFieldsCollection = new FieldModuleCollectionDecoratorGeoPositionFrontend(new FieldsCollection);
		$pFieldsCollection->merge($pGeoFieldsCollection);

		$output = [];
		$pGeoPosition = new GeoPosition;
		$geoRangeFields = array_flip($pGeoPosition->getSearchCriteriaFields());

		foreach ($inputFormFields as $name => $value) {
			$aliasedFieldName = $this->getFieldNameOfInput($name);
			if (in_array($name, $pGeoPosition->getSearchCriteriaFields())) {
				$aliasedFieldName = $geoRangeFields[$name];
			}
			$module = onOfficeSDK::MODULE_ESTATE;
			$pField = $pFieldsCollection->getFieldByModuleAndName($module, $aliasedFieldName);

			if (FieldTypes::isRangeType($pField->getType()))
			{
				if (stristr($name, self::RANGE_FROM)) {
					$output[$pField->getLabel().' (min)'] = $value;
				} elseif (stristr($name, self::RANGE_UPTO)) {
					$output[$pField->getLabel().' (max)'] = $value;
				} else {
					$output[$pField->getLabel()] = $value;
				}
			} else if (FieldTypes::isMultipleSelectType($pField->getType())) {
				if (is_array($value)) {
					$tmpOutput = [];
					foreach ($value as $val) {
						$tmpOutput []= (array_key_exists($val, $pField->getPermittedvalues()) ? $pField->getPermittedvalues()[$val] : $val);
					}
					$output[$pField->getLabel()] = implode(', ', $tmpOutput);
				}
				else {
					$output[$pField->getLabel()] = (array_key_exists($value, $pField->getPermittedvalues()) ? $pField->getPermittedvalues()[$value] : $value);
				}
			} else {
				$output[$pField->getLabel()] = $value;
			}
		}
		return $output;
	}

	/**
	 * @param array $inputFormFields
	 * @return array
	 * @throws DependencyException
	 * @throws NotFoundException
	 */
	public function getFormFieldsWithRangeFields(array $inputFormFields): array
	{
		$pFieldsCollection = $this->loadSearchCriteriaFields();
		$newFormFields = $this->generateFieldModuleArray($pFieldsCollection, $inputFormFields);
		$fieldList = $pFieldsCollection->getFieldsByModule(onOfficeSDK::MODULE_SEARCHCRITERIA);

		foreach ($fieldList as $name => $pField) {
			if (FieldTypes::isRangeType($pField->getType())) {
				unset($newFormFields[$name]);
				$newFormFields[$name.self::RANGE_FROM] = onOfficeSDK::MODULE_SEARCHCRITERIA;
				$newFormFields[$name.self::RANGE_UPTO] = onOfficeSDK::MODULE_SEARCHCRITERIA;
			}
		}

		return $newFormFields;
	}

	/**
	 * @return FieldsCollection
	 * @throws DependencyException
	 * @throws NotFoundException
	 */
	private function loadSearchCriteriaFields(): FieldsCollection
	{
		$pFieldsCollection = new FieldsCollection();
		$this->_pFieldsCollectionBuilder->addFieldsSearchCriteria($pFieldsCollection);
		return $pFieldsCollection;
	}

	/**
	 * @param FieldsCollection $pFieldsCollection
	 * @param array $inputFormFields
	 * @return array
	 */
	private function generateFieldModuleArray(
		FieldsCollection $pFieldsCollection,
		array $inputFormFields): array
	{
		$fieldList = $pFieldsCollection->getFieldsByModule(onOfficeSDK::MODULE_SEARCHCRITERIA);

		$fields = array_unique(array_keys($fieldList));
		$module = array_fill(0, count($fields), onOfficeSDK::MODULE_SEARCHCRITERIA);

		$fieldsModulesCombined = array_combine($fields, $module);

		if ($fieldsModulesCombined !== false) {
			$inputFormFields += $fieldsModulesCombined;
		}

		return $inputFormFields;
	}
}
