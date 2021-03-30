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

namespace onOffice\WPlugin\Record;

/**
 *
 * @url http://www.onoffice.de
 * @copyright 2003-2017, onOffice(R) GmbH
 *
 */

class RecordStructure
{
	/** */
	const NULL_ALLOWED = 'nullAllowed';

	/** */
	const EMPTY_VALUE = 'emptyValue';


	/** @var array */
	static private $_fieldsListview = array(
		RecordManager::TABLENAME_LIST_VIEW => array(
			'name' => array(
				self::NULL_ALLOWED => false,
				self::EMPTY_VALUE => '',
			),
			'filterId' => array(
				self::NULL_ALLOWED => true,
				self::EMPTY_VALUE => 0,
			),
			'sortby' => array(
				self::NULL_ALLOWED => false,
				self::EMPTY_VALUE => 'ASC',
			),
			'is_reference' => array(
				self::NULL_ALLOWED => false,
				self::EMPTY_VALUE => 0,
			),
			'show_status' => array(
				self::NULL_ALLOWED => false,
				self::EMPTY_VALUE => 0,
			),
			'template' => array(
				self::NULL_ALLOWED => false,
				self::EMPTY_VALUE => '',
			),
			'expose' => array(
				self::NULL_ALLOWED => true,
				self::EMPTY_VALUE => '',
			),
			'recordsPerPage' => array(
				self::NULL_ALLOWED => false,
				self::EMPTY_VALUE => 5,
			),
			'list_type' => array(
				self::NULL_ALLOWED => false,
				self::EMPTY_VALUE => '',
			),
		),
		RecordManager::TABLENAME_FIELDCONFIG => array(
			'order' => array(
				self::NULL_ALLOWED => false,
				self::EMPTY_VALUE => 0,
			),
			'fieldname' => array(
				self::NULL_ALLOWED => false,
				self::EMPTY_VALUE => '',
			),
		),
		RecordManager::TABLENAME_LISTVIEW_CONTACTPERSON => array(
			'order' => array(
				self::NULL_ALLOWED => false,
				self::EMPTY_VALUE => 0,
			),
			'fieldname' => array(
				self::NULL_ALLOWED => false,
				self::EMPTY_VALUE => '',
			),
		),
		RecordManager::TABLENAME_PICTURETYPES => array(
			'picturetype' => array(
				self::NULL_ALLOWED => false,
				self::EMPTY_VALUE => '',
			),
		),
		RecordManager::TABLENAME_SORTBYUSERVALUES => array(
			'sortbyuservalue' => array(
				self::NULL_ALLOWED => false,
				self::EMPTY_VALUE => '',
			),
		),
		RecordManager::TABLENAME_LIST_VIEW_ADDRESS => array(
			'name' => array(
				self::NULL_ALLOWED => false,
				self::EMPTY_VALUE => null, // force error
			),
			'filterId' => array(
				self::NULL_ALLOWED => true,
				self::EMPTY_VALUE => null,
			),
			'sortby' => array(
				self::NULL_ALLOWED => true,
				self::EMPTY_VALUE => null,
			),
			'sortorder' => array(
				self::NULL_ALLOWED => false,
				self::EMPTY_VALUE => 'ASC',
			),
			'template' => array(
				self::NULL_ALLOWED => true,
				self::EMPTY_VALUE => null,
			),
			'recordsPerPage' => array(
				self::NULL_ALLOWED => false,
				self::EMPTY_VALUE => 10,
			),
			'showPhoto' => array(
				self::NULL_ALLOWED => false,
				self::EMPTY_VALUE => 0,
			),
		),
		RecordManager::TABLENAME_FIELDCONFIG_ADDRESS => array(
			'order' => array(
				self::NULL_ALLOWED => false,
				self::EMPTY_VALUE => 0,
			),
			'fieldname' => array(
				self::NULL_ALLOWED => false,
				self::EMPTY_VALUE => '',
			),
		),
		RecordManager::TABLENAME_FORMS => array(
			'name' => array(
				self::NULL_ALLOWED => false,
				self::EMPTY_VALUE => null, // force error
			),
			'form_type' => array(
				self::NULL_ALLOWED => false,
				self::EMPTY_VALUE => 'contact',
			),
			'template' => array(
				self::NULL_ALLOWED => false,
				self::EMPTY_VALUE => null, // force error
			),
			'recipient' => array(
				self::NULL_ALLOWED => true,
				self::EMPTY_VALUE => null,
			),
			'subject' => array(
				self::NULL_ALLOWED => true,
				self::EMPTY_VALUE => null,
			),
			'createaddress' => array(
				self::NULL_ALLOWED => false,
				self::EMPTY_VALUE => 0,
			),
			'limitresults' => array(
				self::NULL_ALLOWED => true,
				self::EMPTY_VALUE => null,
			),
			'checkduplicates' => array(
				self::NULL_ALLOWED => false,
				self::EMPTY_VALUE => 0,
			),
			'pages' => array(
				self::NULL_ALLOWED => false,
				self::EMPTY_VALUE => 0,
			),
		),
		'oo_plugin_fieldconfig_form_defaults_values' => [
			'value' => array(
				self::NULL_ALLOWED => false,
				self::EMPTY_VALUE => '',
			),
		],
		'oo_plugin_fieldconfig_form_translated_labels' => [
			'value' => array(
				self::NULL_ALLOWED => false,
				self::EMPTY_VALUE => '',
			),
		],
	);


	/**
	 *
	 * @param string $table
	 * @param string $field
	 * @return array
	 *
	 */

	public static function getFieldByTable($table, $field)
	{
		$result = null;

		if (isset(self::$_fieldsListview[$table][$field])) {
			$result = self::$_fieldsListview[$table][$field];
		}

		return $result;
	}


	/**
	 *
	 * @param string $table
	 * @param string $field
	 * @return bool
	 *
	 */

	public static function isNullAllowed($table, $field)
	{
		$fieldValues = self::getFieldByTable($table, $field);
		return !is_null($fieldValues) && $fieldValues[self::NULL_ALLOWED];
	}



	/**
	 *
	 * @param string $table
	 * @param string $field
	 * @return mixed
	 *
	 */

	public static function getEmptyValue($table, $field)
	{
		$returnValue = false;
		$fieldValues = self::getFieldByTable($table, $field);

		if (!is_null($fieldValues)) {
			$returnValue = $fieldValues[self::EMPTY_VALUE];
		}

		return $returnValue;
	}
}
