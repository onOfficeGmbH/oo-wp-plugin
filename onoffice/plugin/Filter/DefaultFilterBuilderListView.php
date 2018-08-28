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

namespace onOffice\WPlugin\Filter;

use onOffice\WPlugin\Controller\EstateListInputVariableReader;
use onOffice\WPlugin\Controller\EstateListInputVariableReaderConfig;
use onOffice\WPlugin\Controller\EstateListInputVariableReaderConfigFieldnames;
use onOffice\WPlugin\DataView\DataListView;
use onOffice\WPlugin\Favorites;
use onOffice\WPlugin\Types\FieldTypes;
use onOffice\WPlugin\Utility\__String;

/**
 *
 * @url http://www.onoffice.de
 * @copyright 2003-2017, onOffice(R) GmbH
 *
 */

class DefaultFilterBuilderListView
	implements DefaultFilterBuilder
{
	/** @var string */
	private $_pDataListView = null;

	/** @var EstateListInputVariableReaderConfig */
	private $_pEstateListInputVariableReaderConf = null;

	/** @var array */
	private $_defaultFilter = array(
		'veroeffentlichen' => array(
			array('op' => '=', 'val' => 1),
		),
	);



	/**
	 *
	 * @param DataListView $pDataListView
	 * @param EstateListInputVariableReaderConfig $pEstateListInputVariableReaderConf
	 *
	 */

	public function __construct(DataListView $pDataListView,
		EstateListInputVariableReaderConfig $pEstateListInputVariableReaderConf = null)
	{
		$this->_pDataListView = $pDataListView;
		$this->_pEstateListInputVariableReaderConf = $pEstateListInputVariableReaderConf;

		if ($this->_pEstateListInputVariableReaderConf === null) {
			$this->_pEstateListInputVariableReaderConf =
				new EstateListInputVariableReaderConfigFieldnames();
		}
	}


	/**
	 *
	 * @return array
	 *
	 */

	public function buildFilter()
	{
		$fieldFilter = $this->getPostFieldsFilter();
		$filter = array_merge($this->_defaultFilter, $fieldFilter);

		switch ($this->_pDataListView->getListType()) {
			case DataListView::LISTVIEW_TYPE_FAVORITES:
				$filter = $this->getFavoritesFilter();
				break;
			case DataListView::LISTVIEW_TYPE_REFERENCE:
				$filter = $this->getReferenceViewFilter();
				break;
		}

		return $filter;
	}


	/**
	 *
	 * @return array
	 *
	 */

	private function getFavoritesFilter()
	{
		$ids = Favorites::getAllFavorizedIds();

		$filter = $this->_defaultFilter;
		$filter['Id'] = array(
			array('op' => 'in', 'val' => $ids),
		);

		return $filter;
	}


	/**
	 *
	 * @return array
	 *
	 */

	private function getReferenceViewFilter()
	{
		$filter = $this->_defaultFilter;
		$filter['referenz'] = array(
			array('op' => '=', 'val' => 1),
		);

		return $filter;
	}


	/**
	 *
	 * @return array
	 *
	 */

	private function getPostFieldsFilter()
	{
		$filterableFields = $this->_pDataListView->getFilterableFields();
		$filter = array();
		$pEstateInputVars = new EstateListInputVariableReader
			($this->_pEstateListInputVariableReaderConf);

		foreach ($filterableFields as $fieldInput) {
			$type = $pEstateInputVars->getFieldType($fieldInput);
			$value = $pEstateInputVars->getFieldValue($fieldInput);

			if (is_null($value) || (is_string($value) &&
				__String::getNew($value)->isEmpty())) {
				continue;
			}

			$fieldFilter = $this->getFieldFilter($value, $type);
			$filter[$fieldInput] = $fieldFilter;
		}

		return $filter;
	}


	/**
	 *
	 * @param string|array $fieldValue
	 * @param string $type
	 * @return array
	 *
	 */

	private function getFieldFilter($fieldValue, $type)
	{
		$fieldFilter = array();

		if (FieldTypes::isNumericType($type) ||
			FieldTypes::isDateOrDateTime($type)) {
			if (!is_array($fieldValue)) {
				$fieldFilter []= array('op' => '=', 'val' => $fieldValue);
			} else {
				if (isset($fieldValue[0])) {
					$fieldFilter []= array('op' => '>=', 'val' => $fieldValue[0]);
				}

				if (isset($fieldValue[1])) {
					$fieldFilter []= array('op' => '<=', 'val' => $fieldValue[1]);
				}
			}
		} elseif ($type === FieldTypes::FIELD_TYPE_MULTISELECT ||
			$type === FieldTypes::FIELD_TYPE_SINGLESELECT) {
			$fieldFilter []= array('op' => 'in', 'val' => $fieldValue);
		} elseif ($type === FieldTypes::FIELD_TYPE_TEXT) {
			$fieldFilter []= array('op' => 'like', 'val' => '%'.$fieldValue.'%');
		} else {
			$fieldFilter []= array('op' => '=', 'val' => $fieldValue);
		}

		return $fieldFilter;
	}
}