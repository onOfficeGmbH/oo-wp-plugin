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

use DI\Container;
use DI\ContainerBuilder;
use onOffice\SDK\onOfficeSDK;
use onOffice\WPlugin\DataFormConfiguration\DataFormConfigurationInterest;
use onOffice\WPlugin\Field\Collection\FieldsCollectionBuilderShort;
use onOffice\WPlugin\Field\SearchcriteriaFields;
use onOffice\WPlugin\Form;
use onOffice\WPlugin\Form\FormPostConfigurationTest;
use onOffice\WPlugin\Form\FormPostInterestConfigurationTest;
use onOffice\WPlugin\FormPost;
use onOffice\WPlugin\FormPostInterest;
use onOffice\WPlugin\SDKWrapper;
use onOffice\WPlugin\Types\Field;
use onOffice\WPlugin\Types\FieldsCollection;
use onOffice\WPlugin\Types\FieldTypes;
use onOffice\WPlugin\Utility\Logger;
use WP_UnitTestCase;
use function DI\autowire;
use function json_decode;

/**
 *
 * @url http://www.onoffice.de
 * @copyright 2003-2018, onOffice(R) GmbH
 *
 */

class TestClassFormPostInterest
	extends WP_UnitTestCase
{
	/** @var FormPostInterest */
	private $_pFormPostInterest = null;

	/** @var SDKWrapperMocker */
	private $_pSDKWrapperMocker = null;

	/** @var FieldsCollectionBuilderShort */
	private $_pFieldsCollectionBuilderShort = null;

	/** @var Container */
	private $_pContainer = null;

	/**
	 *
	 * @before
	 *
	 */

	public function prepare()
	{

		$pContainerBuilder = new ContainerBuilder;
		$pContainerBuilder->addDefinitions(ONOFFICE_DI_CONFIG_PATH);
		$this->_pContainer = $pContainerBuilder->build();

		$fieldsResponse = file_get_contents
			(__DIR__.'/resources/ApiResponseGetFields.json');
		$responseArrayFields = json_decode($fieldsResponse, true);
		$this->_pSDKWrapperMocker = new SDKWrapperMocker();
		$this->_pSDKWrapperMocker->addResponseByParameters
			(onOfficeSDK::ACTION_ID_GET, 'fields', '', [
			'labels' => true,
					'showContent' => true,
					'showTable' => true,
					'language' => 'ENG',
					'modules' => ['address', 'estate'],
			], null, $responseArrayFields);

		$this->_pContainer->set(SDKWrapper::class, $this->_pSDKWrapperMocker);
		$this->_pContainer->set(Logger::class, $this->getMockBuilder(Logger::class)->getMock());

		$pSearchcriteriaFields = $this->getMockBuilder(SearchcriteriaFields::class)
			->setMethods(['getFormFieldsWithRangeFields'])
			->setConstructorArgs([new FieldsCollectionBuilderShort(new Container())])
			->getMock();
		$pSearchcriteriaFields->method('getFormFieldsWithRangeFields')->will($this->returnValue([
			'Vorname' => onOfficeSDK::MODULE_ADDRESS,
			'Name' => onOfficeSDK::MODULE_ADDRESS,
			'Email' => onOfficeSDK::MODULE_ADDRESS,
			'vermarktungsart' => onOfficeSDK::MODULE_SEARCHCRITERIA,
			'kaufpreis__von' => onOfficeSDK::MODULE_SEARCHCRITERIA,
			'kaufpreis__bis' => onOfficeSDK::MODULE_SEARCHCRITERIA,
		]));

		$this->_pFieldsCollectionBuilderShort = $this->getMockBuilder(FieldsCollectionBuilderShort::class)
			->setMethods(['addFieldsAddressEstate', 'addFieldsSearchCriteria', 'addFieldsFormFrontend'])
			->setConstructorArgs([new Container])
			->getMock();

		$this->_pFieldsCollectionBuilderShort->method('addFieldsSearchCriteria')
			->with($this->anything())
			->will($this->returnCallback(function(FieldsCollection $pFieldsCollection): FieldsCollectionBuilderShort {

				$pField1 = new Field('vermarktungsart', onOfficeSDK::MODULE_SEARCHCRITERIA);
				$pField1->setPermittedvalues(['kauf' => 'Kauf', 'miete' => 'Miete']);
				$pField1->setType(FieldTypes::FIELD_TYPE_MULTISELECT);
				$pFieldsCollection->addField($pField1);

				$pField2 = new Field('kaufpreis', onOfficeSDK::MODULE_SEARCHCRITERIA);
				$pField2->setType(FieldTypes::FIELD_TYPE_FLOAT);
				$pFieldsCollection->addField($pField2);

				$pField3 = new Field('objekttyp', onOfficeSDK::MODULE_SEARCHCRITERIA);
				$pField3->setPermittedvalues(['reihenendhaus' => 'Reihenendhaus', 'reihenhaus' => 'Reihenhaus']);
				$pField3->setType(FieldTypes::FIELD_TYPE_SINGLESELECT);
				$pFieldsCollection->addField($pField3);

				return $this->_pFieldsCollectionBuilderShort;
			}));

			$this->_pFieldsCollectionBuilderShort->method('addFieldsAddressEstate')
			->with($this->anything())
			->will($this->returnCallback(function(FieldsCollection $pFieldsCollection): FieldsCollectionBuilderShort {
				$pFieldVorname = new Field('Vorname', onOfficeSDK::MODULE_ADDRESS);
				$pFieldVorname->setType(FieldTypes::FIELD_TYPE_VARCHAR);
				$pFieldVorname->setLabel('Vorname');
				$pFieldsCollection->addField($pFieldVorname);

				$pFieldName = new Field('Name', onOfficeSDK::MODULE_ADDRESS);
				$pFieldName->setType(FieldTypes::FIELD_TYPE_VARCHAR);
				$pFieldName->setLabel('Name');
				$pFieldsCollection->addField($pFieldName);

				$pFieldEmail = new Field('Email', onOfficeSDK::MODULE_ADDRESS);
				$pFieldEmail->setType(FieldTypes::FIELD_TYPE_VARCHAR);
				$pFieldEmail->setLabel('E-Mail');
				$pFieldsCollection->addField($pFieldEmail);

				$pField3 = new Field('objekttyp', onOfficeSDK::MODULE_ESTATE);
				$pField3->setPermittedvalues(['reihenendhaus' => 'Reihenendhaus', 'reihenhaus' => 'Reihenhaus']);
				$pField3->setType(FieldTypes::FIELD_TYPE_SINGLESELECT);
				$pField3->setLabel('Objekttyp');
				$pFieldsCollection->addField($pField3);

				$pField1 = new Field('vermarktungsart', onOfficeSDK::MODULE_ESTATE);
				$pField1->setPermittedvalues(['kauf' => 'Kauf', 'miete' => 'Miete']);
				$pField1->setType(FieldTypes::FIELD_TYPE_MULTISELECT);
				$pField1->setLabel('Vermarktungsart');
				$pFieldsCollection->addField($pField1);

				$pField2 = new Field('kaufpreis', onOfficeSDK::MODULE_ESTATE);
				$pField2->setType(FieldTypes::FIELD_TYPE_FLOAT);
				$pField2->setLabel('Kaufpreis');
				$pFieldsCollection->addField($pField2);

				return $this->_pFieldsCollectionBuilderShort;
			}));

			$this->_pFieldsCollectionBuilderShort->method('addFieldsFormFrontend')
					->with($this->anything())
					->will($this->returnCallback(function(FieldsCollection $pFieldsCollection): FieldsCollectionBuilderShort {
				$pField1 = new Field('region_plz', onOfficeSDK::MODULE_SEARCHCRITERIA);
				$pField1->setType(FieldTypes::FIELD_TYPE_VARCHAR);
				$pFieldsCollection->addField($pField1);

				return $this->_pFieldsCollectionBuilderShort;
			}));

			$pFormAddressCreator = new Form\FormAddressCreator($this->_pSDKWrapperMocker, $this->_pFieldsCollectionBuilderShort);
			$pSearchcriteriaFields = new SearchcriteriaFields($this->_pFieldsCollectionBuilderShort);
			$pFormPostInterestConfiguration = new FormPostInterestConfigurationTest($this->_pSDKWrapperMocker, $pFormAddressCreator, $pSearchcriteriaFields);

		$this->_pContainer->set(FieldsCollectionBuilderShort::class, $this->_pFieldsCollectionBuilderShort);
		$this->_pContainer->set(Form\FormPostConfiguration::class, autowire(FormPostConfigurationTest::class));
		$this->_pContainer->set(Form\FormPostInterestConfiguration::class, $pFormPostInterestConfiguration);
		$this->_pFormPostInterest = $this->_pContainer->get(FormPostInterest::class);
	}


	/**
	 *
	 */

	public function testInitialCheck()
	{
		$_POST = [
			'Vorname' => 'John',
			'Name' => 'Doe',
			'Email' => 'john@doemail.com',
			'vermarktungsart' => 'kauf',
			'kaufpreis__von' => '200000.00',
			'kaufpreis__bis' => '800000.00',
			'objekttyp' => ['reihenendhaus', 'reihenhaus'],
		];

		$pConfig = $this->getNewDataFormConfigurationInterest();

		$this->addApiResponseCreateAddress(true);
		$this->addApiResponseCreateSearchCriteria(true);
		$this->addApiResponseSendMail(true);
		$this->_pFormPostInterest->initialCheck($pConfig, 2);
		$pFormData = $this->_pFormPostInterest->getFormDataInstance('interestform', 2);

		$this->assertEquals(FormPost::MESSAGE_SUCCESS, $pFormData->getStatus());
	}


	/**
	 *
	 */

	public function testUnsuccessful()
	{
		$pConfig = $this->getNewDataFormConfigurationInterest();
		$_POST = [
			'Vorname' => 'John',
			'Name' => 'Doe',
			'Email' => 'john@doemail.com',
			'vermarktungsart' => 'kauf',
			'kaufpreis__von' => '200000.00',
			'kaufpreis__bis' => '800000.00',
		];

		$unsuccessfulCombinations = [
			[false, false, false],
			[false, false, true],
			[false, true, false],
			[false, true, true],
			[true, false, false],
			[true, false, true],
			[true, true, false],
		];

		$this->_pContainer->get(Logger::class)
			->expects($this->exactly(count($unsuccessfulCombinations)))->method('logError');

		foreach ($unsuccessfulCombinations as $values) {
			$this->addApiResponseCreateAddress($values[0]);
			$this->addApiResponseCreateSearchCriteria($values[1]);
			$this->addApiResponseSendMail($values[2]);
			$this->_pFormPostInterest->initialCheck($pConfig, 2);
			$pFormData = $this->_pFormPostInterest->getFormDataInstance('interestform', 2);

			$this->assertEquals(FormPost::MESSAGE_ERROR, $pFormData->getStatus());
		}
	}


	/**
	 *
	 */

	public function testMissingFields()
	{
		$pConfig = $this->getNewDataFormConfigurationInterest();
		$_POST = [
			'Vorname' => 'John',
		];

		$this->_pFormPostInterest->initialCheck($pConfig, 3);
		$pFormData = $this->_pFormPostInterest->getFormDataInstance('interestform', 3);

		$this->assertEquals(FormPost::MESSAGE_REQUIRED_FIELDS_MISSING, $pFormData->getStatus());
		$this->assertEquals(['Name', 'vermarktungsart'], $pFormData->getMissingFields());
	}


	/**
	 *
	 * @return DataFormConfigurationInterest
	 *
	 */

	private function getNewDataFormConfigurationInterest(): DataFormConfigurationInterest
	{
		$pConfig = new DataFormConfigurationInterest();
		$pConfig->addInput('Vorname', onOfficeSDK::MODULE_ADDRESS);
		$pConfig->addInput('Name', onOfficeSDK::MODULE_ADDRESS);
		$pConfig->addInput('Email', onOfficeSDK::MODULE_ADDRESS);
		$pConfig->addInput('vermarktungsart', onOfficeSDK::MODULE_SEARCHCRITERIA);
		$pConfig->addInput('kaufpreis', onOfficeSDK::MODULE_SEARCHCRITERIA);
		$pConfig->setFormName('interestform');
		$pConfig->setRecipient('test@my-onoffice.com');
		$pConfig->setRequiredFields(['Vorname', 'Name', 'vermarktungsart']);
		$pConfig->setSubject('Interest');
		$pConfig->setTemplate('testtemplate.php');
		$pConfig->setFormType(Form::TYPE_INTEREST);

		return $pConfig;
	}


	/**
	 *
	 * @param bool $success
	 *
	 */

	private function addApiResponseCreateAddress(bool $success)
	{
		$parameters = [
			'Vorname' => 'John',
			'Name' => 'Doe',
			'email' => 'john@doemail.com',
			'checkDuplicate' => false,
		];

		$response = [
			'actionid' => 'urn:onoffice-de-ns:smart:2.5:smartml:action:create',
			'resourceid' => '',
			'resourcetype' => 'address',
			'cacheable' => false,
			'identifier' => '',
			'data' => [
				'meta' => [
					'cntabsolute' => null,
				],
				'records' => [
					0 => [
						'id' => 294,
						'type' => 'address',
						'elements' => []
					],
				],
			],
			'status' => [
				'errorcode' => 0,
				'message' => 'OK',
			],
		];

		if (!$success) {
			$response = $this->getUnsuccessfulResponse(onOfficeSDK::ACTION_ID_CREATE, 'address');
		}

		$this->_pSDKWrapperMocker->addResponseByParameters
			(onOfficeSDK::ACTION_ID_CREATE, 'address', '', $parameters, null, $response);
	}


	/**
	 *
	 * @param bool $success
	 *
	 */

	private function addApiResponseCreateSearchCriteria(bool $success)
	{
		$parameters = [
			'data' => [
				'vermarktungsart' => ['kauf'],
				'kaufpreis__von' => 200000.00,
				'kaufpreis__bis' => 800000.00,
			],
			'addressid' => 294,
		];

		$response = [
			'actionid' => 'urn:onoffice-de-ns:smart:2.5:smartml:action:create',
			'resourceid' => '',
			'resourcetype' => 'searchcriteria',
			'cacheable' => false,
			'identifier' => '',
			'data' => [
				'meta' => [
					'cntabsolute' => null,
				],
				'records' => [
					0 => [
						'id' => 274,
						'type' => 'searchCriteria',
						'elements' => [],
					],
				],
			],
			'status' => [
				'errorcode' => 0,
				'message' => 'OK',
			],
		];

		if (!$success) {
			$response = $this->getUnsuccessfulResponse
				(onOfficeSDK::ACTION_ID_CREATE, 'searchcriteria');
		}

		$this->_pSDKWrapperMocker->addResponseByParameters
			(onOfficeSDK::ACTION_ID_CREATE, 'searchcriteria', '', $parameters, null, $response);
	}


	/**
	 *
	 * @param bool $success
	 *
	 */

	private function addApiResponseSendMail(bool $success)
	{
		$parameters = [
			'addressdata' => [
				'Vorname' => 'John',
				'Name' => 'Doe',
				'Email' => 'john@doemail.com',
			],
			'message' => "\nSuchkriterien des Interessenten:\nVermarktungsart: Kauf\nKaufpreis (min): 200000\nKaufpreis (max): 800000",
			'subject' => 'Interest',
			'formtype' => Form::TYPE_INTEREST,
			"referrer" => "",
			'recipient' => 'test@my-onoffice.com',
		];

	    $responseJson = file_get_contents(__DIR__.'/resources/ApiResponseDoContactaddress.json');
	    $response = json_decode($responseJson, true);
		if (!$success) {
			$response = $this->getUnsuccessfulResponse(onOfficeSDK::ACTION_ID_DO, 'contactaddress');
		}

		$this->_pSDKWrapperMocker->addResponseByParameters
			(onOfficeSDK::ACTION_ID_DO, 'contactaddress', '', $parameters, null, $response);
	}


	/**
	 *
	 * @param string $actionId
	 * @param string $resourceType
	 * @return array
	 *
	 */

	private function getUnsuccessfulResponse(string $actionId, string $resourceType)
	{
		return [
			'actionid' => $actionId,
			'resourceid' => '',
			'resourcetype' => $resourceType,
			'cacheable' => false,
			'identifier' => '',
			'data' => [],
			'status' => [
				'errorcode' => 2093,
				'message' => 'Unknown error',
			],
		];
	}
}
