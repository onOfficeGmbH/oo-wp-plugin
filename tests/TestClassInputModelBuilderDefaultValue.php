<?php

/**
 *
 *    Copyright (C) 2018 onOffice GmbH
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

declare(strict_types=1);

namespace onOffice\tests;


use Closure;
use Generator;
use onOffice\WPlugin\Model\InputModel\InputModelDBFactoryConfigForm;
use onOffice\WPlugin\Model\InputModelBase;
use onOffice\WPlugin\Model\InputModelBuilder\InputModelBuilderDefaultValue;
use onOffice\WPlugin\Model\InputModelDB;
use onOffice\WPlugin\Types\Field;
use onOffice\WPlugin\Types\FieldsCollection;
use onOffice\WPlugin\Types\FieldTypes;

class TestClassInputModelBuilderDefaultValue
	extends \WP_UnitTestCase
{
	/** @var InputModelBuilderDefaultValue */
	private $_pSubject = null;

	/**
	 * @before
	 */
	public function prepare()
	{
		$pInputModelDBFactoryConfigForm = new InputModelDBFactoryConfigForm;
		$this->_pSubject = new InputModelBuilderDefaultValue($pInputModelDBFactoryConfigForm);
	}

	/**
	 * @dataProvider dataProviderCallbackValueInputModelDefaultValue
	 * @param InputModelDB $pExpectedInputModel
	 * @param string $fieldType
	 * @param array $permittedValues
	 * @param array $presetValues
	 */
	public function testCallbackValueInputModelDefaultValue(
		InputModelDB $pExpectedInputModel,
		string $fieldType,
		array $permittedValues,
		array $presetValues)
	{
		$pInputModel = new InputModelDB('testInput', 'testLabel');
		$pField = new Field('testInput', 'testModule');
		$pField->setType($fieldType);
		$pField->setPermittedvalues($permittedValues);
		$this->assertEquals('text', $pInputModel->getHtmlType());
		$this->_pSubject->callbackValueInputModelDefaultValue($pInputModel, $pField, $presetValues);
		$this->assertEquals($pExpectedInputModel, $pInputModel);
	}

	/**
	 * @return Generator
	 */
	public function dataProviderCallbackValueInputModelDefaultValue(): Generator
	{
		$pInputModelBase = new InputModelDB('testInput', 'testLabel');
		$pExpectedInputModelSingleSelect = clone $pInputModelBase;
		$pExpectedInputModelSingleSelect->setHtmlType(InputModelDB::HTML_TYPE_SELECT);
		$pExpectedInputModelSingleSelect->setValue('');
		$pExpectedInputModelSingleSelect->setValuesAvailable(['' => '']);
		yield [$pExpectedInputModelSingleSelect, FieldTypes::FIELD_TYPE_SINGLESELECT, [], []];

		$pExpectedInputModelSingleSelect = new InputModelDB('testInput', 'testLabel');
		$pExpectedInputModelSingleSelect->setHtmlType(InputModelDB::HTML_TYPE_SELECT);
		$pExpectedInputModelSingleSelect->setValue(['n']);
		$pExpectedInputModelSingleSelect->setValuesAvailable(['n' => '', 'y' => 'MySpace', '' => '']);
		yield [
			$pExpectedInputModelSingleSelect,
			FieldTypes::FIELD_TYPE_BOOLEAN,
			['n' => '', 'y' => 'MySpace', '' => ''],
			['testInput' => ['n']],
		];
	}

	/**
	 * @return Closure
	 */
	public function testCreateInputModelDefaultValue(): Closure
	{
		$pFieldsCollection = $this->createFieldsCollection();
		$presetValues = [];
		$pResult = $this->_pSubject->createInputModelDefaultValue($pFieldsCollection, $presetValues);
		$this->assertInstanceOf(InputModelDB::class, $pResult);
		$this->assertSame(InputModelBase::HTML_TYPE_TEXT, $pResult->getHtmlType());
		$this->assertTrue($pResult->getIsMulti());
		$this->assertInstanceOf(Closure::class, $pResult->getValueCallback());
		return $pResult->getValueCallback();
	}

	/**
	 * @depends testCreateInputModelDefaultValue
	 * @param Closure $pClosure
	 */
	public function testValueCallback(Closure $pClosure)
	{
		$pInputModel = new InputModelDB('test', 'testLabel');
		$pClosure($pInputModel, 'testFieldString');
		$this->assertEquals(InputModelBase::HTML_TYPE_TEXT, $pInputModel->getHtmlType());
		$pClosure($pInputModel, 'testFieldSingleSelect');
		$this->assertEquals(InputModelBase::HTML_TYPE_SELECT, $pInputModel->getHtmlType());
		$pClosure($pInputModel, 'dummy_key');
		$this->assertEquals(InputModelBase::HTML_TYPE_NUMBER, $pInputModel->getHtmlType());
		$pClosure($pInputModel, 'testFieldInteger');
		$this->assertEquals(InputModelBase::HTML_TYPE_NUMBER, $pInputModel->getHtmlType());
		$pClosure($pInputModel, 'testFieldFloat');
		$this->assertEquals(InputModelBase::HTML_TYPE_NUMBER, $pInputModel->getHtmlType());
	}

	/**
	 * @return FieldsCollection
	 */
	private function createFieldsCollection(): FieldsCollection
	{
		$pFieldsCollection = new FieldsCollection;

		$pFieldString = new Field('testFieldString', 'testModule');
		$pFieldString->setType(FieldTypes::FIELD_TYPE_TEXT);
		$pFieldsCollection->addField($pFieldString);

		$pFieldSingleSelect = new Field('testFieldSingleSelect', 'testModule');
		$pFieldSingleSelect->setType(FieldTypes::FIELD_TYPE_SINGLESELECT);
		$pFieldsCollection->addField($pFieldSingleSelect);

		return $pFieldsCollection;
	}
}
