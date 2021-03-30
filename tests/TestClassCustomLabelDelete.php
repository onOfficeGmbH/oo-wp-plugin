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

namespace onOffice\tests;

use Generator;
use onOffice\WPlugin\Field\CustomLabel\CustomLabelDelete;
use onOffice\WPlugin\Field\CustomLabel\Exception\CustomLabelDeleteException;
use WP_UnitTestCase;
use wpdb;

/**
 *
 */
class TestClassCustomLabelDelete
	extends WP_UnitTestCase
{
	/** @var CustomLabelDelete */
	private $_pSubject = null;

	/** @var wpdb */
	private $_pWPDB = null;

	/**
	 * @before
	 */
	public function prepare()
	{
		$this->_pWPDB = $this->getMockBuilder(wpdb::class)
			->setMethods(['query', 'prepare'])
			->disableOriginalConstructor()
			->getMock();
		$this->_pWPDB->prefix = 'wp_test_';
		$this->_pSubject = new CustomLabelDelete($this->_pWPDB);
	}

	/**
	 * @throws CustomLabelDeleteException
	 */
	public function testDeleteSingleCustomLabelByFieldname()
	{
		$expectedQuery = "DELETE wp_test_oo_plugin_fieldconfig_form_customs_labels, wp_test_oo_plugin_fieldconfig_form_translated_labels "
			. "FROM wp_test_oo_plugin_fieldconfig_form_customs_labels "
			. "INNER JOIN wp_test_oo_plugin_fieldconfig_form_translated_labels "
			. "ON wp_test_oo_plugin_fieldconfig_form_customs_labels.customs_labels_id = wp_test_oo_plugin_fieldconfig_form_translated_labels.input_id WHERE "
			. "wp_test_oo_plugin_fieldconfig_form_customs_labels.form_id = %d AND "
			. "wp_test_oo_plugin_fieldconfig_form_customs_labels.fieldname = %s AND "
			. "wp_test_oo_plugin_fieldconfig_form_customs_labels.locale = %s";
		$this->_pWPDB->expects($this->once())->method('prepare')
			->with($expectedQuery, 13, 'objektart', 'de_DE')
			->will($this->returnValue('testQuery'));
		$this->_pWPDB->expects($this->once())->method('query')->will($this->returnValue(true));
		$this->_pSubject->deleteSingleCustomLabelByFieldname(13, 'objektart', 'de_DE');
	}

	/**
	 * @throws CustomLabelDeleteException
	 */
	public function testDeleteByFormIdAndFieldNamesEmptyFieldlist()
	{
		$this->_pWPDB->expects($this->never())->method('query');
		$this->_pSubject->deleteByFormIdAndFieldNames(13, []);
	}

	/**
	 * @throws CustomLabelDeleteException
	 */
	public function c()
	{
		$expectedQuery = "DELETE wp_test_oo_plugin_fieldconfig_form_customs_labels, wp_test_oo_plugin_fieldconfig_form_translated_labels "
			. "FROM wp_test_oo_plugin_fieldconfig_form_customs_labels "
			. "INNER JOIN wp_test_oo_plugin_fieldconfig_form_translated_labels "
			. "ON wp_test_oo_plugin_fieldconfig_form_customs_labels.customs_labels_id = wp_test_oo_plugin_fieldconfig_form_translated_labels.input_id WHERE "
			. "wp_test_oo_plugin_fieldconfig_form_customs_labels.form_id = '13' AND "
			. "wp_test_oo_plugin_fieldconfig_form_customs_labels.fieldname IN('testField1', 'testField2')";
		$this->_pWPDB->expects($this->once())->method('query')->with($expectedQuery)->will($this->returnValue(true));

		$this->_pSubject->deleteByFormIdAndFieldNames(13, ['testField1', 'testField2']);
	}

	/**
	 * @throws CustomLabelDeleteException
	 */
	public function testDeleteByFormIdAndFieldNamesFailureFalse()
	{
		$this->expectException(CustomLabelDeleteException::class);
		$this->_pWPDB->expects($this->once())->method('query')->will($this->returnValue(false));
		$this->_pSubject->deleteByFormIdAndFieldNames(13, ['testField2']);
	}

	/**
	 * @throws CustomLabelDeleteException
	 */
	public function testDeleteSingleCustomLabelByFieldnameFailure()
	{
		$this->expectException(CustomLabelDeleteException::class);
		$this->_pWPDB->expects($this->once())->method('prepare')
			->will($this->returnValue('testQuery'));
		$this->_pWPDB->expects($this->once())->method('query')->will($this->returnValue(false));
		$this->_pSubject->deleteSingleCustomLabelByFieldname(13, 'objektart', 'de_DE');
	}

	/**
	 * @throws CustomLabelDeleteException
	 */
	public function testDeleteSingleCustomLabelById()
	{
		$expectedQuery = "DELETE wp_test_oo_plugin_fieldconfig_form_customs_labels, wp_test_oo_plugin_fieldconfig_form_translated_labels "
			. "FROM wp_test_oo_plugin_fieldconfig_form_customs_labels "
			. "INNER JOIN wp_test_oo_plugin_fieldconfig_form_translated_labels "
			. "ON wp_test_oo_plugin_fieldconfig_form_customs_labels.customs_labels_id = wp_test_oo_plugin_fieldconfig_form_translated_labels.input_id WHERE "
			. "wp_test_oo_plugin_fieldconfig_form_customs_labels.customs_labels_id = %d";
		$this->_pWPDB->expects($this->once())->method('prepare')->with($expectedQuery,
			1337)->will($this->returnValue('testQuery2'));
		$this->_pWPDB->expects($this->once())->method('query')->with('testQuery2')->will($this->returnValue(true));
		$this->_pSubject->deleteSingleCustomLabelById(1337);
	}

	/**
	 * @throws CustomLabelDeleteException
	 */
	public function testDeleteSingleCustomLabelByIdFailure()
	{
		$this->expectException(CustomLabelDeleteException::class);
		$this->_pWPDB->expects($this->once())->method('prepare')->will($this->returnValue('testQuery2'));
		$this->_pWPDB->expects($this->once())->method('query')->with('testQuery2')->will($this->returnValue(false));
		$this->_pSubject->deleteSingleCustomLabelById(1337);
	}
}
