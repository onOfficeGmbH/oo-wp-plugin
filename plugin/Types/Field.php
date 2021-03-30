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

declare (strict_types=1);

namespace onOffice\WPlugin\Types;

use function __;

/**
 *
 */

class Field
{
	/** @var string */
	private $_name = '';

	/** @var string */
	private $_type = FieldTypes::FIELD_TYPE_VARCHAR;

	/** @var int */
	private $_length = 0;

	/** @var array */
	private $_permittedvalues = [];

	/** @var string */
	private $_default = null;

	/** @var string */
	private $_label = '';

	/** @var string */
	private $_category = '';

	/** @var string */
	private $_module = '';

	/** @var bool */
	private $_isRangeField = false;

	/** @var array */
	private $_rangeFieldTranslations = [];

	/** @var array */
	private $_compoundFields = [];

	/** @var array */
	private $_labelOnlyValues = [];


	/**
	 *
	 * @param string $name
	 * @param string $module
	 * @param string $label
	 *
	 */

	public function __construct(string $name, string $module, string $label = '')
	{
		$this->_name = $name;
		$this->_module = $module;
		$this->_label = $label;
	}


	/**
	 *
	 * @return string
	 *
	 */

	public function getType(): string
	{
		return $this->_type;
	}


	/**
	 *
	 * @return int
	 *
	 */

	public function getLength(): int
	{
		return $this->_length;
	}


	/**
	 *
	 * @return array
	 *
	 */

	public function getPermittedvalues(): array
	{
		return $this->_permittedvalues;
	}

	/**
	 *
	 * @return string
	 *
	 */

	public function getDefault()
	{
		return $this->_default;
	}


	/**
	 *
	 * @return string
	 *
	 */

	public function getLabel(): string
	{
		return $this->_label;
	}


	/**
	 *
	 * @param string $type
	 *
	 */

	public function setType(string $type)
	{
		$this->_type = $type;
	}


	/**
	 *
	 * @param int $length
	 *
	 */

	public function setLength(int $length)
	{
		$this->_length = $length;
	}


	/**
	 *
	 * @param array $permittedvalues
	 *
	 */

	public function setPermittedvalues(array $permittedvalues)
	{
		$this->_permittedvalues = $permittedvalues;
	}

	/**
	 *
	 * @param string $default
	 *
	 */

	public function setDefault($default)
	{
		$this->_default = $default;
	}


	/**
	 *
	 * @param string $label
	 *
	 */

	public function setLabel(string $label)
	{
		$this->_label = $label;
	}


	/**
	 *
	 * @return string
	 *
	 */

	public function getName(): string
	{
		return $this->_name;
	}


	/**
	 *
	 * @return string
	 *
	 */

	public function getCategory(): string
	{
		return $this->_category;
	}


	/**
	 *
	 * @param string  $category
	 *
	 */

	public function setCategory(string $category)
	{
		$this->_category = $category;
	}


	/**
	 *
	 * @return string
	 *
	 */

	public function getModule(): string
	{
		return $this->_module;
	}


	/**
	 *
	 * @return bool
	 *
	 */

	public function getIsRangeField(): bool
	{
		return $this->_isRangeField;
	}


	/**
	 *
	 * @return array
	 *
	 */

	public function getRangeFieldTranslations(): array
	{
		return $this->_rangeFieldTranslations;
	}


	/**
	 *
	 * @param bool $isRangeField
	 *
	 */

	public function setIsRangeField(bool $isRangeField)
	{
		$this->_isRangeField = $isRangeField;
	}


	/**
	 *
	 * @param array $rangeFieldTranslations
	 *
	 */

	public function setRangeFieldTranslations(array $rangeFieldTranslations)
	{
		$this->_rangeFieldTranslations = $rangeFieldTranslations;
	}


	/**
	 *
	 * @param array $compoundFields
	 *
	 */

	public function setCompoundFields(array $compoundFields)
	{
		$this->_compoundFields = $compoundFields;
	}


	/**
	 *
	 * @return array
	 *
	 */

	public function getCompoundFields(): array
	{
		return $this->_compoundFields;
	}

	/**
	 * @return array
	 */
	public function getLabelOnlyValues(): array
	{
		return $this->_labelOnlyValues;
	}

	/**
	 * @param array $labelOnlyValues
	 */
	public function setLabelOnlyValues(array $labelOnlyValues)
	{
		$this->_labelOnlyValues = $labelOnlyValues;
	}


	/**
	 *
	 * @return array
	 *
	 */

	public function getAsRow(): array
	{
		return [
			'label' => $this->_label,
			'type' => $this->_type,
			'default' => $this->_default,
			'length' => $this->_length === 0 ? null : $this->_length,
			'permittedvalues' => $this->_permittedvalues,
			'content' => $this->_category,
			'module' => $this->_module,
			'rangefield' => $this->_isRangeField,
			'additionalTranslations' => $this->_rangeFieldTranslations,
			'compoundFields' => $this->_compoundFields,
			'labelOnlyValues' => $this->_labelOnlyValues,
		];
	}


	/**
	 *
	 * @param string $fieldName
	 * @param array $row
	 * @return Field
	 *
	 */

	public static function createByRow(string $fieldName, array $row): Field
	{
		$label = __($row['label'], 'onoffice-for-wp-websites') ?: sprintf('(%s)', $fieldName);
		$pField = new Field($fieldName, $row['module'] ?? '', $label);
		$pField->setDefault($row['default'] ?? null);
		$pField->setLength($row['length'] ?? 0);
		$pField->setPermittedvalues($row['permittedvalues'] ?? []);
		$pField->setCategory($row['content'] ?? '');
		$pField->setType($row['type']);
		$pField->setIsRangeField((bool)($row['rangefield'] ?? false));
		$pField->setRangeFieldTranslations($row['additionalTranslations'] ?? []);
		$pField->setCompoundFields($row['compoundFields'] ?? []);
		$pField->setLabelOnlyValues($row['labelOnlyValues'] ?? []);
		return $pField;
	}
}
