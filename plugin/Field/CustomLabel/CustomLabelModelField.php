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

/**
 *
 */
class CustomLabelModelField
{
	/** @var Field */
	private $_pField = null;

	/** @var int */
	private $_formId = 0;

	/** @var int */
	private $_customsLabelsId = 0;

	/** @var array */
	private $_valuesByLocale = [];


	/**
	 *
	 * @param int $formId
	 * @param Field $pField
	 *
	 */

	public function __construct(int $formId, Field $pField)
	{
		$this->_pField = $pField;
		$this->_formId = $formId;
	}


	/**
	 *
	 * @return Field
	 *
	 */

	public function getField(): Field
	{
		return $this->_pField;
	}


	/**
	 *
	 * @return int
	 *
	 */

	public function getFormId(): int
	{
		return $this->_formId;
	}


	/**
	 *
	 * @param string $locale
	 * @param string $value
	 *
	 */

	public function addValueByLocale(string $locale, string $value)
	{
		$this->_valuesByLocale[$locale] = $value;
	}


	/**
	 *
	 * @return array
	 *
	 */

	public function getValuesByLocale(): array
	{
		return $this->_valuesByLocale;
	}
}