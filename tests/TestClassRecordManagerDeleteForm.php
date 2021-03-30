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

namespace onOffice\tests;

use onOffice\WPlugin\Record\RecordManagerDeleteForm;
use WP_UnitTestCase;
use wpdb;

/**
 *
 * Test for class RecordManagerDeleteForm
 *
 */

class TestClassRecordManagerDeleteForm
	extends WP_UnitTestCase
{
	/** @var RecordManagerDeleteForm */
	private $_pSubject = null;

	/** @var wpdb */
	private $_pWpdbMock = null;


	/**
	 *
	 * @before
	 *
	 */

	public function prepare()
	{
		$this->_pWpdbMock = $this->getMockBuilder(wpdb::class)
			->disableOriginalConstructor()
			->getMock();
		$this->_pWpdbMock->prefix = 'wp_test_';
		$this->_pSubject = new RecordManagerDeleteForm($this->_pWpdbMock);

	}


	/**
	 *
	 */

	public function testDeleteByIds()
	{
		$this->_pWpdbMock->expects($this->exactly(12))->method('delete')->with($this->logicalOr(
			$this->equalTo('wp_test_oo_plugin_forms'),
			$this->equalTo('wp_test_oo_plugin_form_fieldconfig'),
			$this->equalTo('wp_test_oo_plugin_fieldconfig_form_defaults'),
			$this->equalTo('wp_test_oo_plugin_fieldconfig_form_customs_labels')
		));
		$this->_pWpdbMock->expects($this->once())->method('prepare')
			->with('DELETE FROM wp_test_oo_plugin_fieldconfig_form_defaults_values '
				.'WHERE defaults_id IN (%d, %d, %d)', [1 ,2 ,3])
			->will($this->returnValue('DELETE FROM wp_test_oo_plugin_fieldconfig_form_defaults_values '
				.'WHERE defaults_id IN (1, 2, 3)'));
		$this->_pWpdbMock->expects($this->once())->method('query')
			->with('DELETE FROM wp_test_oo_plugin_fieldconfig_form_defaults_values '
				.'WHERE defaults_id IN (1, 2, 3)');
		$this->_pWpdbMock->expects($this->exactly(6))->method('get_col')
			->will($this->returnCallback(function(string $query): array {
			return $query === "SELECT defaults_id "
				."FROM wp_test_oo_plugin_fieldconfig_form_defaults "
				."WHERE form_id = '14'" ? [1, 2, 3] : [];
		}));

		$this->_pSubject->deleteByIds([13, 14, 15]);
	}

	/**
	 *
	 */

	public function testDeleteTranslatedLabelsByIds()
	{
		$this->_pWpdbMock->expects($this->exactly(12))->method('delete')->with($this->logicalOr(
			$this->equalTo('wp_test_oo_plugin_forms'),
			$this->equalTo('wp_test_oo_plugin_form_fieldconfig'),
			$this->equalTo('wp_test_oo_plugin_fieldconfig_form_defaults'),
			$this->equalTo('wp_test_oo_plugin_fieldconfig_form_customs_labels')
		));
		$this->_pWpdbMock->expects($this->once())->method('prepare')
			->with('DELETE FROM wp_test_oo_plugin_fieldconfig_form_translated_labels '
				.'WHERE input_id IN (%d, %d, %d)', [1 ,2 ,3])
			->will($this->returnValue('DELETE FROM wp_test_oo_plugin_fieldconfig_form_translated_labels '
				.'WHERE input_id IN (1, 2, 3)'));
		$this->_pWpdbMock->expects($this->once())->method('query')
			->with('DELETE FROM wp_test_oo_plugin_fieldconfig_form_translated_labels '
				.'WHERE input_id IN (1, 2, 3)');
		$this->_pWpdbMock->expects($this->exactly(6))->method('get_col')
			->will($this->returnCallback(function(string $query): array {
				return $query === "SELECT customs_labels_id "
				."FROM wp_test_oo_plugin_fieldconfig_form_customs_labels "
				."WHERE form_id = '14'" ? [1, 2, 3] : [];
			}));

		$this->_pSubject->deleteByIds([13, 14, 15]);
	}
}
