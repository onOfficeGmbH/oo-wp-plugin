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

namespace onOffice\WPlugin\Model\InputModel;


/**
 *
 * @url http://www.onoffice.de
 * @copyright 2003-2017, onOffice(R) GmbH
 *
 */

class InputModelDBFactoryConfigForm
	implements InputModelDBFactoryConfigBase
{
	/** */
	const INPUT_FORM_NAME = 'formName';

	/** */
	const INPUT_FORM_TYPE = 'formType';

	/** */
	const INPUT_FORM_RECIPIENT = 'formRecipient';

	/** */
	const INPUT_FORM_SUBJECT = 'formSubject';

	/** */
	const INPUT_FORM_CREATEADDRESS = 'formCreateAddress';

	/** */
	const INPUT_FORM_CHECKDUPLICATES = 'formCheckDuplicates';

	/** */
	const INPUT_FORM_REQUIRES_CAPTCHA = 'formRequiresCaptcha';

	/** */
	const INPUT_FORM_REQUIRED = 'formRequired';

	/** */
	const INPUT_FORM_AVAILABLE_OPTIONS = 'formAvailableOptions';

	/** */
	const INPUT_FORM_LIMIT_RESULTS = 'formLimitResults';

	/** */
	const INPUT_FORM_PAGES = 'formPages';

	/** */
	const INPUT_FORM_MODULE = 'formModule';

	/** */
	const INPUT_FORM_NEWSLETTER = 'newsletter';

	/** */
	const INPUT_FORM_DEFAULT_VALUE = 'defaultValue';

	/** */
	const INPUT_FORM_ESTATE_CONTEXT_AS_HEADING = 'show_estate_context';

    /** */
    const INPUT_FORM_CONTACT_TYPE = 'contactType';


	/** @var array */
	private $_inputConfig = [
		self::INPUT_FORM_NAME => [
			self::KEY_TABLE => 'oo_plugin_forms',
			self::KEY_FIELD => 'name',
		],
		InputModelDBFactory::INPUT_TEMPLATE => [
			self::KEY_TABLE => 'oo_plugin_forms',
			self::KEY_FIELD => 'template',
		],
		self::INPUT_FORM_TYPE => [
			self::KEY_TABLE => 'oo_plugin_forms',
			self::KEY_FIELD => 'form_type',
		],
		self::INPUT_FORM_RECIPIENT => [
			self::KEY_TABLE => 'oo_plugin_forms',
			self::KEY_FIELD => 'recipient',
		],
		self::INPUT_FORM_SUBJECT => [
			self::KEY_TABLE => 'oo_plugin_forms',
			self::KEY_FIELD => 'subject',
		],
		self::INPUT_FORM_CREATEADDRESS => [
			self::KEY_TABLE => 'oo_plugin_forms',
			self::KEY_FIELD => 'createaddress',
		],
		self::INPUT_FORM_LIMIT_RESULTS => [
			self::KEY_TABLE => 'oo_plugin_forms',
			self::KEY_FIELD => 'limitresults',
		],
		self::INPUT_FORM_CHECKDUPLICATES => [
			self::KEY_TABLE => 'oo_plugin_forms',
			self::KEY_FIELD => 'checkduplicates',
		],
		self::INPUT_FORM_REQUIRES_CAPTCHA => [
			self::KEY_TABLE => 'oo_plugin_forms',
			self::KEY_FIELD => 'captcha',
		],
		self::INPUT_FORM_PAGES => [
			self::KEY_TABLE => 'oo_plugin_forms',
			self::KEY_FIELD => 'pages',
		],
		self::INPUT_FORM_NEWSLETTER => [
			self::KEY_TABLE => 'oo_plugin_forms',
			self::KEY_FIELD => 'newsletter',
		],
		self::INPUT_FORM_ESTATE_CONTEXT_AS_HEADING => [
			self::KEY_TABLE => 'oo_plugin_forms',
			self::KEY_FIELD => 'show_estate_context',
		],
		self::INPUT_FORM_CONTACT_TYPE => [
			self::KEY_TABLE => 'oo_plugin_forms',
			self::KEY_FIELD => 'contact_type',
		],

		InputModelDBFactory::INPUT_FIELD_CONFIG => [
			self::KEY_TABLE => 'oo_plugin_form_fieldconfig',
			self::KEY_FIELD => 'fieldname',
		],
		self::INPUT_FORM_REQUIRED => [
			self::KEY_TABLE => 'oo_plugin_form_fieldconfig',
			self::KEY_FIELD => 'required',
		],
		self::INPUT_FORM_MODULE => [
			self::KEY_TABLE => 'oo_plugin_form_fieldconfig',
			self::KEY_FIELD => 'module',
		],
		self::INPUT_FORM_AVAILABLE_OPTIONS => [
			self::KEY_TABLE => 'oo_plugin_form_fieldconfig',
			self::KEY_FIELD => 'availableOptions',
		],
		self::INPUT_FORM_DEFAULT_VALUE => [
			self::KEY_TABLE => 'oo_plugin_fieldconfig_form_defaults_values',
			self::KEY_FIELD => 'value',
		],
	];


	/**
	 *
	 * @return array
	 *
	 */

	public function getConfig(): array
	{
		return $this->_inputConfig;
	}
}
