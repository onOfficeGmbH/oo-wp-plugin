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

use onOffice\WPlugin\Field\CustomLabel\CustomLabelModelField;
use onOffice\WPlugin\Types\Field;
use WP_UnitTestCase;

/**
 *
 */
class TestClassCustomLabelModelField
	extends WP_UnitTestCase
{
	/** */
	const EXPECTATION_VALUES_BY_LOCALE = [
		'de_DE' => 'Hallo',
		'fr_BE' => 'Bonjour',
		'zh_CN' => '你好',
	];


	/**
	 *
	 */

	public function testValuesByLocale()
	{
		$pField = new Field('testField', 'testModule');
		$pCustomLabelModelField = new CustomLabelModelField(12, $pField);
		$this->assertEmpty($pCustomLabelModelField->getValuesByLocale());

		$pCustomLabelModelField->addValueByLocale('de_DE', 'Hallo');
		$pCustomLabelModelField->addValueByLocale('fr_BE', 'Bonjour');
		$pCustomLabelModelField->addValueByLocale('zh_CN', '你好');

		$this->assertEquals(self::EXPECTATION_VALUES_BY_LOCALE, $pCustomLabelModelField->getValuesByLocale());
	}
}