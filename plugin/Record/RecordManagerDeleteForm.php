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

declare (strict_types=1);

namespace onOffice\WPlugin\Record;

use wpdb;
use function esc_sql;


/**
 *
 */

class RecordManagerDeleteForm
	extends RecordManager
	implements RecordManagerDelete
{
	/** @var wpdb */
	private $_pWPDB;


	/**
	 *
	 * @param wpdb $pWPDB
	 *
	 */

	public function __construct(wpdb $pWPDB)
	{
		$this->_pWPDB = $pWPDB;
	}


	/**
	 *
	 * @param array $ids
	 *
	 */

	public function deleteByIds(array $ids)
	{
		$prefix = $this->_pWPDB->prefix;
		$pWpdb = $this->_pWPDB;

		foreach ($ids as $id) {
			$pWpdb->delete($prefix.'oo_plugin_forms', ['form_id' => $id]);
			$pWpdb->delete($prefix.'oo_plugin_form_fieldconfig', ['form_id' => $id]);
			$defaultIds = $pWpdb->get_col(
				"SELECT defaults_id "
				."FROM {$prefix}oo_plugin_fieldconfig_form_defaults "
				."WHERE form_id = '".esc_sql($id)."'");

			if ($defaultIds !== []) {
				$stringPlaceholders = array_fill(0, count($defaultIds), '%d');
				$placeHolders = implode(', ', $stringPlaceholders);
				$pWpdb->query($pWpdb->prepare("DELETE FROM {$prefix}oo_plugin_fieldconfig_form_defaults_values "
					."WHERE defaults_id IN ($placeHolders)", $defaultIds));
			}

			$inputIds = $pWpdb->get_col(
				"SELECT customs_labels_id "
				."FROM {$prefix}oo_plugin_fieldconfig_form_customs_labels "
				."WHERE form_id = '".esc_sql($id)."'");

			if ($inputIds !== []) {
				$stringPlaceholders = array_fill(0, count($inputIds), '%d');
				$placeHolders = implode(', ', $stringPlaceholders);
				$pWpdb->query($pWpdb->prepare("DELETE FROM {$prefix}oo_plugin_fieldconfig_form_translated_labels "
					."WHERE input_id IN ($placeHolders)", $inputIds));
			}

			$pWpdb->delete($prefix.'oo_plugin_fieldconfig_form_defaults', ['form_id' => $id]);
			$pWpdb->delete($prefix.'oo_plugin_fieldconfig_form_customs_labels', ['form_id' => $id]);
		}
	}
}