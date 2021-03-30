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

use onOffice\SDK\onOfficeSDK;
use onOffice\WPlugin\Field\CustomLabel\CustomLabelCreate;
use onOffice\WPlugin\Field\CustomLabel\ModelToOutputConverter\CustomLabelRowSaver;
use onOffice\WPlugin\Language;
use onOffice\WPlugin\Types\Field;
use onOffice\WPlugin\Types\FieldsCollection;
use onOffice\WPlugin\Types\FieldTypes;


class TestClassCustomLabelRowSaver
	extends \WP_UnitTestCase
{
	/** @var array */
	const EXAMPLE_RECORDS = [
		'testField' => [
			'native' => 'testEN',
			'de_DE' => 'testDE',
		],
	];

	/** @var CustomLabelRowSaver */
	private $_pSubject = null;

	/** @var CustomLabelCreate */
	private $_pCustomLabelCreate = null;

	/** @var Language */
	private $_pLanguage = null;


	/**
	 * @before
	 */
	public function prepare()
	{
		$this->_pCustomLabelCreate = $this->getMockBuilder(CustomLabelCreate::class)
			->disableOriginalConstructor()
			->getMock();
		$this->_pLanguage = $this->getMockBuilder(Language::class)
			->getMock();
		$this->_pSubject = new CustomLabelRowSaver($this->_pCustomLabelCreate, $this->_pLanguage);
	}

	/**
	 *
	 */
	public function testSaveCustomLabelsField()
	{
		$this->_pCustomLabelCreate->expects($this->once())->method('createForField');
		$pFieldsCollection = $this->buildFieldsCollection();
		$this->_pSubject->saveCustomLabels(13, [
			'testField' => self::EXAMPLE_RECORDS['testField'],
		], $pFieldsCollection);
	}

	/**
	 * @return FieldsCollection
	 */
	private function buildFieldsCollection(): FieldsCollection
	{
		$pFieldsCollection = new FieldsCollection;
		$pFieldText = new Field('testField', onOfficeSDK::MODULE_ESTATE);
		$pFieldText->setType(FieldTypes::FIELD_TYPE_TEXT);
		$pFieldsCollection->addField($pFieldText);
		return $pFieldsCollection;
	}
}
