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

namespace onOffice\WPlugin\DataFormConfiguration;

use onOffice\WPlugin\Controller\ViewProperty;

/**
 *
 * @url http://www.onoffice.de
 * @copyright 2003-2017, onOffice(R) GmbH
 *
 */

class DataFormConfiguration
	implements ViewProperty
{
	/** */
	const FIELDS = 'fields';


	/** @var string */
	private $_formType = '';

	/** @var string */
	private $_formName = '';

	/** @var array */
	private $_requiredFields = [];

	/** @var string */
	private $_language = '';

	/** @var array */
	private $_inputs = [];

	/** @var string */
	private $_template = '';

	/** @var bool */
	private $_captcha = false;

	/** @var int The Form ID */
	private $_id = 0;

	/** @var array */
	private $_availableOptionsFields = [];

	/** @var bool */
	private $_showEstateContext = false;

    /** @var bool */
    private $_contactType = '';

	/**
	 *
	 * Override to set default fields for new, empty forms
	 *
	 */

	public function setDefaultFields()
		{}

	/** @return string */
	public function getFormType(): string
		{ return $this->_formType; }

	/** @return array */
	public function getRequiredFields(): array
		{ return $this->_requiredFields; }

	/** @return array */
	public function getAvailableOptionsFields(): array
		{ return $this->_availableOptionsFields; }

	/** @return string */
	public function getLanguage(): string
		{ return $this->_language; }

	/** @return array */
	public function getInputs(): array
		{ return $this->_inputs; }

	/** @param string $formType */
	public function setFormType(string $formType)
		{ $this->_formType = $formType; }

	/** @param array $requiredFields */
	public function setRequiredFields(array $requiredFields)
		{ $this->_requiredFields = $requiredFields; }

	/** @param array $availableOptionsFields */
	public function setAvailableOptionsFields(array $availableOptionsFields)
		{ $this->_availableOptionsFields = $availableOptionsFields; }

	/** @param string $language */
	public function setLanguage(string $language)
		{ $this->_language = $language; }

	/** @param array $inputs */
	public function setInputs(array $inputs)
		{ $this->_inputs = $inputs; }

	/** @param string $requiredField */
	public function addRequiredField(string $requiredField)
		{ $this->_requiredFields []= $requiredField; }

	/** @param string $availableOptionsField */
	public function addAvailableOptionsField(string $availableOptionsField)
		{ $this->_availableOptionsFields []= $availableOptionsField; }

	/** @param bool $showEstateContext */
	public function setShowEstateContext(bool $showEstateContext)
		{ $this->_showEstateContext = $showEstateContext; }

    /** @param string $contactTypeField */
    public function setContactTypeField(string $contactTypeField)
		{ $this->_contactType = $contactTypeField; }
	/**
	 *
	 * @param string $input
	 * @param string $module null if wp-only input
	 *
	 */

	public function addInput(string $input, $module = null)
		{ $this->_inputs[$input] = $module; }

	/** @return string */
	public function getTemplate(): string
		{ return $this->_template; }

	/** @param string $template */
	public function setTemplate(string $template)
		{ $this->_template = $template; }

	/** @return string */
	public function getFormName(): string
		{ return $this->_formName; }

	/** @param string $formName */
	public function setFormName(string $formName)
		{ $this->_formName = $formName; }

	/** @return bool */
	public function getCaptcha(): bool
		{ return $this->_captcha; }

	/** @param bool $captcha */
	public function setCaptcha(bool $captcha)
		{ $this->_captcha = $captcha; }

	/** @return int */
	public function getId(): int
		{ return $this->_id; }

	/** @return string */
	public function getModule(): string
		{ return 'form'; }

	/** @return string */
	public function getViewType(): string
		{ return $this->_formType; }

	/** @param int $id */
	public function setId(int $id)
		{ $this->_id = $id; }

	/** @return bool */
	public function getShowEstateContext(): bool
		{ return $this->_showEstateContext; }

    /** @return string */
    public function getContactType(): string
		{ return $this->_contactType; }
}
