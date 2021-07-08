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

use DI\Container;
use onOffice\SDK\onOfficeSDK;
use onOffice\WPlugin\DataFormConfiguration\DataFormConfiguration;
use onOffice\WPlugin\DataFormConfiguration\DataFormConfigurationInterest;
use onOffice\WPlugin\Field\Collection\FieldsCollectionBuilderShort;
use onOffice\WPlugin\Form\FormAddressCreator;
use onOffice\WPlugin\FormData;
use onOffice\WPlugin\Types\Field;
use onOffice\WPlugin\Types\FieldsCollection;
use onOffice\WPlugin\Types\FieldTypes;
use WP_UnitTestCase;


/**
 *
 */

class TestClassFormAddressCreator
	extends WP_UnitTestCase
{
	/** @var FormAddressCreator */
	private $_pSubject = null;

	/** @var FieldsCollectionBuilderShort */
	private $_pFieldsCollectionBuilderShort = null;

	/** @var SDKWrapperMocker */
	private $_pSDKWrapper = null;

	/** @var array */
	private $_postValues = [
		'testaddressfield1varchar' => 'testValue',
		'testaddressfield1multiselect' => ['hut', 'tut'],
		'testestatefield1multiselect' => 'test',
 	];


	/**
	 *
	 * @before
	 *
	 */

	public function prepare()
	{
		$this->_pFieldsCollectionBuilderShort = $this->getMockBuilder(FieldsCollectionBuilderShort::class)
			->setMethods(['addFieldsAddressEstate'])
			->setConstructorArgs([new Container])
			->getMock();
		$this->_pSDKWrapper = new SDKWrapperMocker();

		$this->_pSubject = new FormAddressCreator($this->_pSDKWrapper, $this->_pFieldsCollectionBuilderShort);
		$this->_pFieldsCollectionBuilderShort->method('addFieldsAddressEstate')
			->with($this->anything())
			->will($this->returnCallback(function(FieldsCollection $pFieldsCollection): FieldsCollectionBuilderShort {
				$pField1 = new Field('testaddressfield1varchar', onOfficeSDK::MODULE_ADDRESS);
				$pField1->setLabel('Test Address Field1 Varchar');
				$pField1->setType(FieldTypes::FIELD_TYPE_VARCHAR);
				$pFieldsCollection->addField($pField1);

				$pField2 = new Field('testaddressfield1multiselect', onOfficeSDK::MODULE_ADDRESS);
				$pField2->setLabel('Test Address Field1 Multiselect');
				$pField2->setType(FieldTypes::FIELD_TYPE_MULTISELECT);
				$pField2->setPermittedvalues(['hut' => 'HUT', 'tut' => 'TUT']);
				$pFieldsCollection->addField($pField2);
				$pFieldsCollection->addField(new Field('testestatefield1multiselect', onOfficeSDK::MODULE_ESTATE));

				return $this->_pFieldsCollectionBuilderShort;
			}));
	}


	/**
	 *
	 */

	public function testCreateOrCompleteAddressSuccess()
	{
		$this->configureSDKWrapperMockerForAddressCreation(3039);
		$pFormData = $this->createFormData();
		$this->assertEquals(3039, $this->_pSubject->createOrCompleteAddress($pFormData));
	}


	/**
	 *
	 * @expectedException \onOffice\WPlugin\API\ApiClientException
	 *
	 */

	public function testCreateOrCompleteAddressFailure()
	{
		$this->configureSDKWrapperMockerForAddressCreation(0);
		$pFormData = $this->createFormData();
		$this->_pSubject->createOrCompleteAddress($pFormData);
	}

	/**
	 * @throws \DI\DependencyException
	 * @throws \DI\NotFoundException
	 * @throws \onOffice\WPlugin\Field\UnknownFieldException
	 * @covers \onOffice\WPlugin\Form\FormAddressCreator::getAddressDataForEmail
	 *
	 */

	public function testGetAddressDataForEmail()
	{
		$pFormData = $this->getMockBuilder(FormData::class)
			->setMethods(['getAddressData'])
			->disableOriginalConstructor()
			->getMock();

		$pFieldsCollection = new FieldsCollection();
		$this->_pFieldsCollectionBuilderShort->addFieldsAddressEstate($pFieldsCollection);

		$pFormData->method('getAddressData')
			->with($pFieldsCollection)
			->willReturn([
			'testaddressfield1varchar' => 'asd',
			'testaddressfield1multiselect' => ['hut', 'tut']
		]);

		$expectedValues = [
			'Test Address Field1 Varchar' => 'asd',
			'Test Address Field1 Multiselect' => 'HUT, TUT'
		];

		$this->assertEquals($expectedValues, $this->_pSubject->getAddressDataForEmail($pFormData));
	}


	/**
	 *
	 * @return FormData
	 *
	 */

	private function createFormData(): FormData
	{
		$pDataFormConfiguration = new DataFormConfiguration();
		$pDataFormConfiguration->addInput('testaddressfield1varchar', onOfficeSDK::MODULE_ADDRESS);
		$pDataFormConfiguration->addInput('testaddressfield1multiselect', onOfficeSDK::MODULE_ADDRESS);
		$pDataFormConfiguration->addInput('testestatefield1multiselect', onOfficeSDK::MODULE_ESTATE);
		$pFormData = new FormData($pDataFormConfiguration, 1);
		$pFormData->setValues($this->_postValues);
		return $pFormData;
	}


	/**
	 *
	 */

	private function configureSDKWrapperMockerForAddressCreation(int $id)
	{
		$response = [
			'actionid' => 'urn:onoffice-de-ns:smart:2.5:smartml:action:create',
			'resourceid' => '',
			'resourcetype' => 'address',
			'cacheable' => false,
			'identifier' => '',
			'data' => [
				'meta' => ['cntabsolute' => null],
				'records' => [
					['id' => $id, 'type' => 'address', 'elements' => []],
				],
			],
			'status' => ['errorcode' => 0, 'message' => 'OK'],
		];

		$this->addCreateAddressResponseToSKDWrapper($response);
	}


	/**
	 *
	 * @param array $response
	 *
	 */

	private function addCreateAddressResponseToSKDWrapper(array $response)
	{
		$this->_pSDKWrapper->addResponseByParameters(onOfficeSDK::ACTION_ID_CREATE, 'address', '', [
			'testaddressfield1varchar' => 'testValue',
			'testaddressfield1multiselect' => ['hut','tut'],
			'checkDuplicate' => false,
		], null, $response);
	}
}
