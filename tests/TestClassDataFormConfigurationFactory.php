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

namespace onOffice\tests;

use onOffice\SDK\onOfficeSDK;
use onOffice\WPlugin\Controller\GeoPositionFieldHandler;
use onOffice\WPlugin\DataFormConfiguration\DataFormConfiguration;
use onOffice\WPlugin\DataFormConfiguration\DataFormConfigurationApplicantSearch;
use onOffice\WPlugin\DataFormConfiguration\DataFormConfigurationContact;
use onOffice\WPlugin\DataFormConfiguration\DataFormConfigurationFactory;
use onOffice\WPlugin\DataFormConfiguration\DataFormConfigurationInterest;
use onOffice\WPlugin\DataFormConfiguration\DataFormConfigurationOwner;
use onOffice\WPlugin\Form;
use onOffice\WPlugin\GeoPosition;
use onOffice\WPlugin\Record\RecordManagerReadForm;
use WP_UnitTestCase;

/**
 *
 * @url http://www.onoffice.de
 * @copyright 2003-2018, onOffice(R) GmbH
 *
 */

class TestClassDataFormConfigurationFactory
	extends WP_UnitTestCase
{
	/** @var DataFormConfigurationFactory */
	private $_pDataFormConfigurationFactory = null;

	/** @var array */
	private $_formTypes = [
		Form::TYPE_CONTACT => DataFormConfigurationContact::class,
		Form::TYPE_OWNER => DataFormConfigurationOwner::class,
		Form::TYPE_INTEREST => DataFormConfigurationInterest::class,
		Form::TYPE_APPLICANT_SEARCH => DataFormConfigurationApplicantSearch::class,
	];

	/** @var RecordManagerReadForm */
	private $_pRecordManagerReadForm = null;

	/** @var GeoPositionFieldHandler */
	private $_pGeoPositionFieldHandler = null;


	/**
	 *
	 * @before
	 *
	 */

	public function prepare()
	{
		$this->_pRecordManagerReadForm = $this->getMockBuilder(RecordManagerReadForm::class)
			->getMock();
		$this->_pGeoPositionFieldHandler = $this->getMockBuilder(GeoPositionFieldHandler::class)
			->getMock();

		$this->_pRecordManagerReadForm->method('getRowById')->will($this->returnValueMap([
			[1, $this->getBaseRow(1, Form::TYPE_CONTACT)],
			[2, $this->getBaseRow(2, Form::TYPE_OWNER)],
			[3, $this->getBaseRow(3, Form::TYPE_INTEREST)],
			[4, $this->getBaseRow(4, Form::TYPE_APPLICANT_SEARCH)],
			[5, $this->getBaseRow(5, Form::TYPE_APPLICANT_SEARCH)],
			[6, $this->getBaseRow(6, Form::TYPE_INTEREST)],
		]));
		$this->_pRecordManagerReadForm->method('readFieldsByFormId')->will($this->returnValueMap([
			[1, $this->getBasicFieldsArray(1, Form::TYPE_CONTACT)],
			[2, $this->getBasicFieldsArray(2, Form::TYPE_OWNER)],
			[3, $this->getBasicFieldsArray(3, Form::TYPE_INTEREST)],
			[4, $this->getBasicFieldsArray(4, Form::TYPE_APPLICANT_SEARCH)],
			[5, $this->getBasicFieldsArrayWithGeo(5, Form::TYPE_APPLICANT_SEARCH)],
			[6, $this->getBasicFieldsArrayWithGeo(6, Form::TYPE_INTEREST)],
		]));
		$this->_pRecordManagerReadForm->method('getRowByName')->will($this->returnValueMap([
			['testForm1', $this->getBaseRow(1, Form::TYPE_CONTACT)],
			['testForm2', $this->getBaseRow(2, Form::TYPE_OWNER)],
			['testForm3', $this->getBaseRow(3, Form::TYPE_INTEREST)],
			['testForm4', $this->getBaseRow(4, Form::TYPE_APPLICANT_SEARCH)],
		]));

		$this->_pGeoPositionFieldHandler->method('getActiveFields')->will($this->returnValue([
			GeoPosition::ESTATE_LIST_SEARCH_COUNTRY,
			GeoPosition::ESTATE_LIST_SEARCH_RADIUS,
			GeoPosition::ESTATE_LIST_SEARCH_ZIP,
		]));

		$this->_pDataFormConfigurationFactory = new DataFormConfigurationFactory
			(null, $this->_pRecordManagerReadForm, $this->_pGeoPositionFieldHandler);
	}


	/**
	 *
	 */

	public function testConstruct()
	{
		$pDataFormConfigurationFactory = new DataFormConfigurationFactory('testType');
		$this->assertInstanceOf(DataFormConfigurationFactory::class, $pDataFormConfigurationFactory);
	}


	/**
	 *
	 * @expectedException onOffice\WPlugin\DataFormConfiguration\UnknownFormException
	 *
	 */

	public function testCreateEmptyNoType()
	{
		$this->_pDataFormConfigurationFactory->createEmpty();
	}


	/**
	 *
	 */

	public function testCreateEmptyNoDefaults()
	{
		foreach ($this->_formTypes as $formType => $class) {
			$pDataFormConfigurationFactory = $this->_pDataFormConfigurationFactory->withType($formType);
			$pDataFormConfiguration = $pDataFormConfigurationFactory->createEmpty(false);

			$this->assertInstanceOf($class, $pDataFormConfiguration);
			$this->assertEquals($formType, $pDataFormConfiguration->getFormType());
			$this->assertEmpty($pDataFormConfiguration->getFormName());
			$this->assertEmpty($pDataFormConfiguration->getLanguage());
			$this->assertEmpty($pDataFormConfiguration->getInputs());
			$this->assertEmpty($pDataFormConfiguration->getRequiredFields());
			$this->assertEmpty($pDataFormConfiguration->getTemplate());

			switch ($formType) {
				case Form::TYPE_CONTACT:
					/* @var $pDataFormConfiguration DataFormConfigurationContact */
					$this->assertEmpty($pDataFormConfiguration->getRecipient());
					$this->assertEmpty($pDataFormConfiguration->getSubject());
					$this->assertFalse($pDataFormConfiguration->getCheckDuplicateOnCreateAddress());
					$this->assertFalse($pDataFormConfiguration->getCreateAddress());
					$this->assertFalse($pDataFormConfiguration->getNewsletterCheckbox());
					break;
				case Form::TYPE_OWNER:
					/* @var $pDataFormConfiguration DataFormConfigurationOwner */
					$this->assertEquals(1, $pDataFormConfiguration->getPages());
					$this->assertFalse($pDataFormConfiguration->getCheckDuplicateOnCreateAddress());
					$this->assertEmpty($pDataFormConfiguration->getSubject());
					$this->assertEmpty($pDataFormConfiguration->getRecipient());
					break;
				case Form::TYPE_INTEREST:
					/* @var $pDataFormConfiguration DataFormConfigurationInterest */
					$this->assertFalse($pDataFormConfiguration->getCheckDuplicateOnCreateAddress());
					$this->assertEmpty($pDataFormConfiguration->getSubject());
					$this->assertEmpty($pDataFormConfiguration->getRecipient());
					break;
				case Form::TYPE_APPLICANT_SEARCH:
					/* @var $pDataFormConfiguration DataFormConfigurationApplicantSearch */
					$this->assertEquals(100, $pDataFormConfiguration->getLimitResults());
					break;
			}
		}
	}


	/**
	 *
	 */

	public function testCreateEmptyWithDefaults()
	{
		foreach ($this->_formTypes as $formType => $class) {
			$pDataFormConfigurationFactory = $this->_pDataFormConfigurationFactory->withType($formType);
			$pDataFormConfiguration = $pDataFormConfigurationFactory->createEmpty();

			$this->assertInstanceOf($class, $pDataFormConfiguration);
			$this->assertEquals($formType, $pDataFormConfiguration->getFormType());
			$this->assertEmpty($pDataFormConfiguration->getFormName());
			$this->assertEmpty($pDataFormConfiguration->getLanguage());
			$this->assertEmpty($pDataFormConfiguration->getTemplate());

			if ($formType !== Form::TYPE_APPLICANT_SEARCH) {
				$this->assertNotEmpty($pDataFormConfiguration->getInputs());
			}
		}
	}


	/**
	 *
	 */

	public function testIsAdminInterface()
	{
		$pDataFormConfigurationFactory = $this->_pDataFormConfigurationFactory;
		$this->assertFalse($pDataFormConfigurationFactory->getIsAdminInterface());
		$pDataFormConfigurationFactory->setIsAdminInterface(true);
		$this->assertTrue($pDataFormConfigurationFactory->getIsAdminInterface());
	}


	/**
	 *
	 */

	public function testLoadByFormId()
	{
		$formId = 1;
		foreach ($this->_formTypes as $formType => $class) {
			$fieldsArray = $this->getBasicFieldsArray($formId, $formType);
			$fieldsArrayFlat = array_combine
				(array_column($fieldsArray, 'fieldname'), array_column($fieldsArray, 'module'));
			$pDataFormConfiguration = $this->_pDataFormConfigurationFactory
				->loadByFormId($formId);
			$this->assertFactoryOutput($class, $formType, $pDataFormConfiguration, $formId,
				$fieldsArrayFlat);

			$formId++;
		}
	}


	/**
	 *
	 */

	public function testLoadByFormName()
	{
		$ids = range(1, count($this->_formTypes));
		$idToFormType = array_combine($ids, array_keys($this->_formTypes));

		foreach ($idToFormType as $formId => $formType) {
			$class = $this->_formTypes[$formType];
			$fieldsArray = $this->getBasicFieldsArray($formId, $formType);
			$fieldsArrayFlat = array_combine
				(array_column($fieldsArray, 'fieldname'), array_column($fieldsArray, 'module'));

			$pDataFormConfiguration = $this->_pDataFormConfigurationFactory->loadByFormName
				('testForm'.$formId);
			$this->assertFactoryOutput($class, $formType, $pDataFormConfiguration, $formId,
				$fieldsArrayFlat);
		}
	}


	/**
	 *
	 */

	public function testGeoPositionSetting()
	{
		$this->_pDataFormConfigurationFactory->setIsAdminInterface(false);
		$pDataFormConfigurationApplicant = $this->_pDataFormConfigurationFactory->loadByFormId(5);
		$expectedFields = [
			'Vorname' => 'address',
			'Name' => 'address',
			'applicantsearchSpecialField1' => 'address',
			'range_land' => 'address',
			'range_plz' => 'address',
		];
		$this->assertEquals($expectedFields, $pDataFormConfigurationApplicant->getInputs());

		$pDataFormConfiguration = $this->_pDataFormConfigurationFactory->loadByFormId(6);
		$expectedFields['range'] = 'address';
		$expectedFields['interestSpecialField1'] = 'address';
		unset($expectedFields['applicantsearchSpecialField1']);
		$this->assertEquals($expectedFields, $pDataFormConfiguration->getInputs());
	}


	/**
	 *
	 * @param string $class
	 * @param string $formType
	 * @param DataFormConfigurationInterest $pDataFormConfiguration
	 * @param int $formId
	 * @param array $fieldsArrayFlat
	 *
	 */

	private function assertFactoryOutput(string $class, string $formType,
		DataFormConfiguration $pDataFormConfiguration, int $formId, array $fieldsArrayFlat)
	{
			$this->assertInstanceOf($class, $pDataFormConfiguration);
			$this->assertEquals($formType, $pDataFormConfiguration->getFormType());
			$this->assertEquals('testForm'.$formId, $pDataFormConfiguration->getFormName());
			$this->assertEquals('', $pDataFormConfiguration->getLanguage());
			$this->assertEquals($fieldsArrayFlat, $pDataFormConfiguration->getInputs());
			$this->assertEquals(['Vorname', 'Name'], $pDataFormConfiguration->getRequiredFields());
			$this->assertEquals('testtemplate.php', $pDataFormConfiguration->getTemplate());
			$this->assertTrue($pDataFormConfiguration->getCaptcha());

			switch ($formType) {
				case Form::TYPE_CONTACT:
					/* @var $pDataFormConfiguration DataFormConfigurationContact */
					$this->assertEquals('test@my-onoffice.com', $pDataFormConfiguration->getRecipient());
					$this->assertEquals('A Subject', $pDataFormConfiguration->getSubject());
					$this->assertTrue($pDataFormConfiguration->getCheckDuplicateOnCreateAddress());
					$this->assertTrue($pDataFormConfiguration->getCreateAddress());
					$this->assertEquals(['contactSpecialField1'], $pDataFormConfiguration->getAvailableOptionsFields());
					break;
				case Form::TYPE_OWNER:
					/* @var $pDataFormConfiguration DataFormConfigurationOwner */
					$this->assertEquals(3, $pDataFormConfiguration->getPages());
					$this->assertTrue($pDataFormConfiguration->getCheckDuplicateOnCreateAddress());
					$this->assertEquals('A Subject', $pDataFormConfiguration->getSubject());
					$this->assertEquals('test@my-onoffice.com', $pDataFormConfiguration->getRecipient());
					$this->assertEquals(['ownerSpecialField1'], $pDataFormConfiguration->getAvailableOptionsFields());
					break;
				case Form::TYPE_INTEREST:
					/* @var $pDataFormConfiguration DataFormConfigurationInterest */
					$this->assertTrue($pDataFormConfiguration->getCheckDuplicateOnCreateAddress());
					$this->assertEquals('A Subject', $pDataFormConfiguration->getSubject());
					$this->assertEquals('test@my-onoffice.com', $pDataFormConfiguration->getRecipient());
					break;
				case Form::TYPE_APPLICANT_SEARCH:
					/* @var $pDataFormConfiguration DataFormConfigurationApplicantSearch */
					$this->assertEquals(30, $pDataFormConfiguration->getLimitResults());
					break;
			}
	}


	/**
	 *
	 * @param int $formId
	 * @param string $formType
	 * @return array
	 *
	 */

	private function getBasicFieldsArray(int $formId, string $formType): array
	{
		$fields = [
			[
				'form_fieldconfig_id' => '1',
				'form_id' => $formId,
				'order' => '1',
				'fieldname' => 'Vorname',
				'fieldlabel' => 'First Name',
				'module' => onOfficeSDK::MODULE_ADDRESS,
				'individual_fieldname' => '0',
				'availableOptions' => '0',
				'required' => '1',
				'contact_type' => '',
			],
			[
				'form_fieldconfig_id' => '2',
				'form_id' => $formId,
				'order' => '2',
				'fieldname' => 'Name',
				'fieldlabel' => 'Name',
				'module' => onOfficeSDK::MODULE_ADDRESS,
				'individual_fieldname' => '0',
				'availableOptions' => '0',
				'required' => '1',
				'contact_type' => '',
			],
			[
				'form_fieldconfig_id' => '3',
				'form_id' => $formId,
				'order' => '3',
				'fieldname' => $formType.'SpecialField1',
				'fieldlabel' => $formType.' Special Field 1',
				'module' => onOfficeSDK::MODULE_ADDRESS,
				'individual_fieldname' => '0',
				'availableOptions' => '1',
				'required' => '0',
				'contact_type' => '',
			],
		];

		return $fields;
	}


	/**
	 *
	 * @param int $formId
	 * @param string $formType
	 * @return array
	 *
	 */

	private function getBasicFieldsArrayWithGeo(int $formId, string $formType): array
	{
		$fields = $this->getBasicFieldsArray($formId, $formType);
		$geoField = $fields[0];
		$geoField['form_fieldconfig_id'] = count($fields) + 1;
		$geoField['fieldname'] = GeoPosition::FIELD_GEO_POSITION;
		$geoField['fieldlabel'] = 'Geo Position';
		$geoField['required'] = '0';
		$fields []= $geoField;
		return $fields;
	}

	/**
	 *
	 * @param int $formId
	 * @param string $formType
	 * @return array
	 *
	 */

	private function getBaseRow(int $formId, string $formType): array
	{
		return [
			'form_id' => $formId,
			'name' => 'testForm'.$formId,
			'form_type' => $formType,
			'template' => 'testtemplate.php',
			'recipient' => 'test@my-onoffice.com',
			'subject' => 'A Subject',
			'createaddress' => '1',
			'limitresults' => '30',
			'checkduplicates' => '1',
			'pages' => '3',
			'captcha' => '1',
			'newsletter' => '1',
			'availableOptions' => '1',
			'show_estate_context' => '0',
			'contact_type' => '',
		];
	}
}
