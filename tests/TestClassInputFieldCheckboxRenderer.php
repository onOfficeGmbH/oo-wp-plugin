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

namespace onOffice\tests;

use DI\Container;
use DI\ContainerBuilder;
use onOffice\SDK\onOfficeSDK;
use onOffice\WPlugin\Field\Collection\FieldsCollectionBuilderShort;
use onOffice\WPlugin\Renderer\InputFieldCheckboxRenderer;
use onOffice\WPlugin\SDKWrapper;
use onOffice\WPlugin\Types\Field;
use onOffice\WPlugin\Types\FieldsCollection;
use onOffice\WPlugin\Types\FieldTypes;
use onOffice\WPlugin\Utility\Logger;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class TestClassInputFieldCheckboxRenderer
	extends \WP_UnitTestCase
{

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
		(__DIR__ . '/resources/ApiResponseGetFields.json');
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

		$this->_pFieldsCollectionBuilderShort = $this->getMockBuilder(FieldsCollectionBuilderShort::class)
			->setMethods(['addFieldsAddressEstate', 'addFieldsSearchCriteria'])
			->setConstructorArgs([new Container])
			->getMock();

		$this->_pFieldsCollectionBuilderShort->method('addFieldsSearchCriteria')
			->with($this->anything())
			->will($this->returnCallback(function (FieldsCollection $pFieldsCollection): FieldsCollectionBuilderShort {
				return $this->_pFieldsCollectionBuilderShort;
			}));

		$this->_pFieldsCollectionBuilderShort->method('addFieldsAddressEstate')
			->with($this->anything())
			->will($this->returnCallback(function (FieldsCollection $pFieldsCollection): FieldsCollectionBuilderShort {
				$pFieldMultiParkingLot = new Field('multiParkingLot', onOfficeSDK::MODULE_ESTATE);
				$pFieldMultiParkingLot->setType(FieldTypes::FIELD_TYPE_INTEGER);
				$pFieldMultiParkingLot->setLabel('Parking');
				$pFieldsCollection->addField($pFieldMultiParkingLot);

				$pFieldTest= new Field('test', onOfficeSDK::MODULE_ESTATE);
				$pFieldTest->setType(FieldTypes::FIELD_TYPE_INTEGER);
				$pFieldTest->setLabel('Test');
				$pFieldsCollection->addField($pFieldTest);

				return $this->_pFieldsCollectionBuilderShort;
			}));

		$this->_pContainer->set(FieldsCollectionBuilderShort::class, $this->_pFieldsCollectionBuilderShort);
	}

	/**
	 *
	 */
	public function testRenderRegularField()
	{
		$pSubject = new InputFieldCheckboxRenderer('testRenderer', '', $this->_pContainer);
		$pSubject->setValue(['multiParkingLot' => 'Parking', 'test' => 'Test']);
		ob_start();
		$pSubject->render();
		$output = ob_get_clean();
		$this->assertEquals('<input type="checkbox" name="testRenderer" value="multiParkingLot" onoffice-multipleSelectType="0" '
			. 'id="labelcheckbox_1bmultiParkingLot input-multiParkingLot" disabled class="input-multiParkingLot">'
			. '<label class="label-multiParkingLot" for="labelcheckbox_1bmultiParkingLot">Parking<span class="hint">(Can not be displayed)</span></label><br>'
			. '<input type="checkbox" name="testRenderer" value="test" onoffice-multipleSelectType="0" id="labelcheckbox_1btest">'
			. '<labelfor="labelcheckbox_1btest">Test</label><br>', $output);
	}

}