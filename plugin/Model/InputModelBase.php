<?php

/**
 *
 *    Copyright (C) 2017 onOffice GmbH
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

namespace onOffice\WPlugin\Model;

/**
 *
 * @url http://www.onoffice.de
 * @copyright 2003-2017, onOffice(R) GmbH
 *
 */

abstract class InputModelBase
{
	/** */
	const HTML_TYPE_SELECT = 'select';

	/** */
	const HTML_TYPE_CHECKBOX = 'checkbox';

	/** */
	const HTML_TYPE_RADIO = 'radio';

	/** */
	const HTML_TYPE_HIDDEN = 'hidden';

	/** */
	const HTML_TYPE_TEXT = 'text';

	/** */
	const HTML_TYPE_LABEL = 'label';

	/** */
	const HTML_TYPE_CHECKBOX_BUTTON = 'checkboxWithSubmitButton';

	/** */
	const HTML_TYPE_COMPLEX_SORTABLE_CHECKBOX_LIST = 'complexSortableCheckboxList';

	/** */
	const HTML_TYPE_COMPLEX_SORTABLE_DETAIL_LIST = 'complexSortableDetailList';

	/** */
	const HTML_TYPE_CHOSEN = 'chosen';

	/** @var string */
	private $_name = null;

	/** @var mixed */
	private $_value = null;

	/** @var string */
	private $_label = null;

	/** @var string */
	private $_htmlType = self::HTML_TYPE_TEXT;

	/** @var bool */
	private $_isPassword = false;

	/** @var array */
	private $_valuesAvailable = array();

	/** @var bool */
	private $_isMulti = false;

	/** @var string */
	private $_placeholder = null;

	/** @var string */
	private $_id = null;

	/** @var array For referenced input models only */
	private $_valueCallback = null;

	/** @var InputModelBase[] */
	private $_referencedInputModels = array();

	/** @var string */
	private $_specialDivId = null;

	/** @var string */
	private $_oOModule = '';

	/** @var array */
	private $_labelOnlyValues = [];

    /** @var string */
    private $_descriptionText = [];

	/**
	 *
	 * @return string
	 *
	 */

	abstract public function getIdentifier(): string;

	/** @param string $htmlType */
	public function setHtmlType($htmlType)
		{ $this->_htmlType = $htmlType; }

	/** @return string */
	public function getHtmlType()
		{ return $this->_htmlType; }

	/** @return string */
	public function getLabel()
		{ return $this->_label; }

	/** @return string */
	public function getName()
		{ return $this->_name; }

	/** @param string $name */
	public function setName($name)
		{ $this->_name = $name; }

	/** @param string $label */
	public function setLabel($label)
		{ $this->_label = $label; }

	/** @return bool */
	public function getIsPassword()
		{ return $this->_isPassword; }

	/** @param bool $isPassword */
	public function setIsPassword($isPassword)
		{ $this->_isPassword = $isPassword; }

	/** @return mixed */
	public function getValue()
		{ return $this->_value; }

	/** @param mixed $value */
	public function setValue($value)
		{ $this->_value = $value; }

	/** @return array */
	public function getValuesAvailable()
		{ return $this->_valuesAvailable; }

	/** @param array $valuesAvailable */
	public function setValuesAvailable($valuesAvailable)
		{ $this->_valuesAvailable = $valuesAvailable; }

	/** @param bool $isMulti */
	public function setIsMulti($isMulti)
		{ $this->_isMulti = $isMulti; }

	/** @return bool */
	public function getIsMulti()
		{ return $this->_isMulti; }

	/** @return bool */
	public function getPlaceholder()
		{ return $this->_placeholder; }

	/** @param string $placeholder */
	public function setPlaceholder($placeholder)
		{ $this->_placeholder = $placeholder; }

	/** @param string $id */
	public function setId($id)
		{ $this->_id = $id; }

	/** @return string */
	public function getId()
		{ return $this->_id; }

	/** @return array */
	public function getValueCallback(): callable
		{ return $this->_valueCallback; }

	/** @param callable $valueCallback */
	public function setValueCallback(callable $valueCallback)
		{ $this->_valueCallback = $valueCallback; }

	/** @param InputModelBase $pReferencedInputModel */
	public function addReferencedInputModel(InputModelBase $pReferencedInputModel)
		{ $this->_referencedInputModels []= $pReferencedInputModel; }

	/** @return InputModelBase[] */
	public function getReferencedInputModels()
		{ return $this->_referencedInputModels; }

	/** @param string $specialDivId */
	public function setSpecialDivId($specialDivId)
		{ $this->_specialDivId = $specialDivId; }

	/** @return string */
	public function getSpecialDivId()
		{ return $this->_specialDivId; }

	/** @param string $module */
	public function setOoModule(string $module)
		{ $this->_oOModule = $module; }

	/** @return string */
	public function getOoModule(): string
		{ return $this->_oOModule; }

	/** @return array */
	public function getLabelOnlyValues(): array
		{ return $this->_labelOnlyValues; }

	/** @param array $labelOnlyValues */
	public function setLabelOnlyValues(array $labelOnlyValues)
		{ $this->_labelOnlyValues = $labelOnlyValues; }

    /** @return string */
    public function getDescriptionText()
    	{ return $this->_descriptionText; }

    /** @param string $descriptionText */
    public function setDescriptionText(string $descriptionText)
    	{ $this->_descriptionText = $descriptionText; }

}
