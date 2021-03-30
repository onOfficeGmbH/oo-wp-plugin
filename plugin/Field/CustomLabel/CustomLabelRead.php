<?php

/**
 *
 *    Copyright (C) 2021 onOffice GmbH
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

namespace onOffice\WPlugin\Field\CustomLabel;

use onOffice\WPlugin\Types\Field;
use wpdb;
use const OBJECT;
use function esc_sql;


/**
 *
 * Reads default values as defined in WP, not onOffice enterprise
 *
 */
class CustomLabelRead
{
	/** @var wpdb */
	private $_pWPDB;


	/**
	 * @param wpdb $pWPDB
	 */
	public function __construct(wpdb $pWPDB)
	{
		$this->_pWPDB = $pWPDB;
	}

	/**
	 * @param int $formId
	 * @param Field $pField
	 * @return CustomLabelModelField
	 */
	public function readCustomLabelsField(int $formId, Field $pField): CustomLabelModelField
	{
		$query = $this->createBaseQuery($formId, $pField);
		$rows = $this->_pWPDB->get_results($query, OBJECT);
		$pDataModel = new CustomLabelModelField($formId, $pField);

		foreach ($rows as $pRow) {
			$pDataModel->addValueByLocale($pRow->locale, $pRow->value);
		}

		return $pDataModel;
	}

	/**
	 * @param int $formId
	 * @param $field
	 * @param $current_lang
	 * @return array
	 */
	public function readCustomLabelByFormIdAndFieldName(int $formId, $field, $current_lang): array
	{
		$query = $this->createCustomsLabelsByFormIdQuery($formId, $field, $current_lang);
		return $this->_pWPDB->get_results($query, OBJECT);
	}


	/**
	 * @param int $formId
	 * @param Field $pField
	 * @return string
	 */
	private function createBaseQuery(int $formId, Field $pField): string
	{
		$prefix = $this->_pWPDB->prefix;
		$query = "SELECT {$prefix}oo_plugin_fieldconfig_form_customs_labels.customs_labels_id,"
			. "{$prefix}oo_plugin_fieldconfig_form_translated_labels.locale,\n"
			. "{$prefix}oo_plugin_fieldconfig_form_translated_labels.value\n"
			. "FROM {$prefix}oo_plugin_fieldconfig_form_customs_labels\n"
			. "INNER JOIN {$prefix}oo_plugin_fieldconfig_form_translated_labels\n"
			. "ON {$prefix}oo_plugin_fieldconfig_form_customs_labels.customs_labels_id = "
			. " {$prefix}oo_plugin_fieldconfig_form_translated_labels.input_id\n"
			. "WHERE {$prefix}oo_plugin_fieldconfig_form_customs_labels.fieldname = '" . esc_sql($pField->getName()) . "' AND\n"
			. " {$prefix}oo_plugin_fieldconfig_form_customs_labels.form_id = " . esc_sql($formId);
		return $query;
	}

	/**
	 * @param int $formId
	 * @param string $field
	 * @param $current_lang
	 * @return string
	 */
	private function createCustomsLabelsByFormIdQuery(int $formId, string $field, $current_lang): string
	{
		$prefix = $this->_pWPDB->prefix;
		$queryByFormId = "SELECT {$prefix}oo_plugin_fieldconfig_form_customs_labels.customs_labels_id,"
			. "{$prefix}oo_plugin_fieldconfig_form_translated_labels.locale,\n"
			. "{$prefix}oo_plugin_fieldconfig_form_translated_labels.value\n"
			. "FROM {$prefix}oo_plugin_fieldconfig_form_customs_labels\n"
			. "INNER JOIN {$prefix}oo_plugin_fieldconfig_form_translated_labels\n"
			. "ON {$prefix}oo_plugin_fieldconfig_form_customs_labels.customs_labels_id = "
			. " {$prefix}oo_plugin_fieldconfig_form_translated_labels.input_id\n"
			. "WHERE {$prefix}oo_plugin_fieldconfig_form_customs_labels.fieldname = '" . esc_sql($field) . "' AND\n"
			. "{$prefix}oo_plugin_fieldconfig_form_translated_labels.locale = '" . esc_sql($current_lang) . "' AND\n"
			. " {$prefix}oo_plugin_fieldconfig_form_customs_labels.form_id = " . esc_sql($formId);
		return $queryByFormId;
	}
}
