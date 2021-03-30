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

use onOffice\WPlugin\Field\CustomLabel\CustomLabelCreate;
use onOffice\WPlugin\Field\CustomLabel\CustomLabelModelField;
use onOffice\WPlugin\Record\RecordManagerFactory;
use onOffice\WPlugin\Record\RecordManagerInsertGeneric;
use onOffice\WPlugin\Types\Field;
use WP_UnitTestCase;

/**
 *
 */
class TestClassCustomLabelCreate
	extends WP_UnitTestCase
{
	/** @var CustomLabelCreate */
	private $_pSubject = null;

	/** @var RecordManagerFactory */
	private $_pRecordManagerFactory = null;

	/** @var RecordManagerInsertGeneric */
	private $_pRecordManagerInsertGeneric = null;


	/**
	 *
	 * @before
	 *
	 */

	public function prepare()
	{
		$this->_pRecordManagerFactory = $this->getMockBuilder(RecordManagerFactory::class)
			->setMethods(['createRecordManagerInsertGeneric'])
			->getMock();
		$this->_pRecordManagerInsertGeneric = $this->getMockBuilder(RecordManagerInsertGeneric::class)
			->setConstructorArgs(['testMainTable'])
			->getMock();
		$this->_pRecordManagerFactory->method('createRecordManagerInsertGeneric')
			->will($this->returnValue($this->_pRecordManagerInsertGeneric));

		$this->_pSubject = new CustomLabelCreate($this->_pRecordManagerFactory);
	}

	/**
	 *
	 */

	public function testCreateForField()
	{
		$this->_pRecordManagerInsertGeneric->expects($this->exactly(3))->method('insertByRow')
			->will($this->returnCallback(function (array $values) {
				$valuesConf = [
					121 => [
						'oo_plugin_fieldconfig_form_customs_labels' => [
							'form_id' => 14,
							'fieldname' => 'testField2'
						]
					],
					130 => [
						'oo_plugin_fieldconfig_form_translated_labels' => [
							'input_id' => 121,
							'locale' => 'de_DE',
							'value' => 'Custom Label DE'
						]
					],
					56 => [
						'oo_plugin_fieldconfig_form_translated_labels' => [
							'input_id' => 121,
							'locale' => 'fr_BE',
							'value' => 'Custom Label BE'
						]
					],
				];
				$returnValue = array_search($values, $valuesConf);
				return $returnValue ?: 0;
			}));
		$pField = new Field('testField2', 'testModule2');
		$pCustomLabels = new CustomLabelModelField(14, $pField);
		$pCustomLabels->addValueByLocale('de_DE', 'Custom Label DE');
		$pCustomLabels->addValueByLocale('fr_BE', 'Custom Label BE');
		$resultId = $this->_pSubject->createForField($pCustomLabels);
		$this->assertEquals(121, $resultId);
	}
}
