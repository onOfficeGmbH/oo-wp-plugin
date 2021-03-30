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

declare(strict_types=1);

namespace onOffice\tests;


use Closure;
use Generator;
use onOffice\WPlugin\Model\InputModel\InputModelDBFactoryConfigForm;
use onOffice\WPlugin\Model\InputModelBase;
use onOffice\WPlugin\Model\InputModelBuilder\InputModelBuilderCustomLabel;
use onOffice\WPlugin\Model\InputModelDB;
use onOffice\WPlugin\Types\Field;
use onOffice\WPlugin\Types\FieldsCollection;
use onOffice\WPlugin\Types\FieldTypes;

class TestClassInputModelBuilderCustomLabel
	extends \WP_UnitTestCase
{
	/** @var InputModelBuilderCustomLabel */
	private $_pSubject = null;

	/**
	 * @before
	 */
	public function prepare()
	{
		$pInputModelDBFactoryConfigForm = new InputModelDBFactoryConfigForm;
		$this->_pSubject = new InputModelBuilderCustomLabel($pInputModelDBFactoryConfigForm);
	}

	/**
	 * @dataProvider dataProviderCallbackValueInputModelCustomLabel
	 * @param InputModelDB $pExpectedInputModel
	 * @param string $fieldType
	 * @param array $permittedValues
	 * @param array $presetValues
	 */
	public function testCallbackValueInputModelCustomLabel(
		InputModelDB $pExpectedInputModel,
		string $fieldType,
		array $permittedValues,
		array $presetValues
	) {
		$pInputModel = new InputModelDB('testInput', 'testLabel');
		$pField = new Field('testInput', 'testModule');
		$pField->setType($fieldType);
		$pField->setPermittedvalues($permittedValues);
		$this->assertEquals('text', $pInputModel->getHtmlType());
		$this->_pSubject->callbackValueInputModelCustomLabel($pInputModel, $pField, $presetValues);
		$this->assertEquals($pExpectedInputModel, $pInputModel);
	}

	/**
	 * @return Generator
	 */
	public function dataProviderCallbackValueInputModelCustomLabel(): Generator
	{
		$pInputModelBase = new InputModelDB('testInput', 'testLabel');
		$pExpectedInputModel = clone $pInputModelBase;
		$pExpectedInputModel->setHtmlType(InputModelDB::HTML_TYPE_TEXT);
		$pExpectedInputModel->setValue('');
		yield [$pExpectedInputModel, FieldTypes::FIELD_TYPE_TEXT, [], []];
	}

	/**
	 * @return Closure
	 */
	public function testCreateInputModelCustomLabel(): Closure
	{
		$pFieldsCollection = $this->createFieldsCollection();
		$presetValues = [];
		$pResult = $this->_pSubject->createInputModelCustomLabel($pFieldsCollection, $presetValues);
		$this->assertInstanceOf(InputModelDB::class, $pResult);
		$this->assertSame(InputModelBase::HTML_TYPE_TEXT, $pResult->getHtmlType());
		$this->assertTrue($pResult->getIsMulti());
		$this->assertInstanceOf(Closure::class, $pResult->getValueCallback());
		return $pResult->getValueCallback();
	}

	/**
	 * @return FieldsCollection
	 */
	private function createFieldsCollection(): FieldsCollection
	{
		$pFieldsCollection = new FieldsCollection;

		$pFieldString = new Field('testField', 'testModule');
		$pFieldString->setType(FieldTypes::FIELD_TYPE_TEXT);
		$pFieldsCollection->addField($pFieldString);


		return $pFieldsCollection;
	}
}
