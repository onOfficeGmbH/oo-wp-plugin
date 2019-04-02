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

namespace onOffice\tests;

use onOffice\SDK\onOfficeSDK;
use onOffice\WPlugin\Controller\GeoPositionFieldHandler;
use onOffice\WPlugin\Model\InputModel\InputModelDBFactoryConfigGeoFields;
use onOffice\WPlugin\Record\RecordManagerRead;
use stdClass;
use WP_UnitTestCase;


/**
 *
 * @url http://www.onoffice.de
 * @copyright 2003-2019, onOffice(R) GmbH
 *
 */

class TestClassGeoPositionFieldHandler
	extends WP_UnitTestCase
{
	/** @var stdClass */
	private $_pRecord = null;


	/**
	 *
	 * @before
	 *
	 */

	public function prepare()
	{
		$pInputModelDBFactoryConfigGeoFields = new InputModelDBFactoryConfigGeoFields(onOfficeSDK::MODULE_ESTATE);
		$fields = $pInputModelDBFactoryConfigGeoFields->getBooleanFields();
		$values = array_fill(0, count($fields), '1');
		$valuesFull = array_combine($fields, $values);
		$pRecord = (object)$valuesFull;
		$this->_pRecord = [$pRecord];
	}


	/**
	 *
	 */

	public function testGetActiveFields()
	{
		$pRecordManager = $this->getRecordManagerMock();
		$pGeoPositionFieldHandler = new GeoPositionFieldHandler(3, $pRecordManager);
		$pGeoPositionFieldHandler->readValues();
		$this->assertCount(4, $pGeoPositionFieldHandler->getActiveFields());
	}


	/**
	 *
	 */

	public function testGetActiveFieldsWithValue()
	{
		$pRecordManager = $this->getRecordManagerMock();
		$pGeoPositionFieldHandler = new GeoPositionFieldHandler(3, $pRecordManager);
		$pGeoPositionFieldHandler->readValues();
		$this->assertCount(4, $pGeoPositionFieldHandler->getActiveFieldsWithValue());
		$this->assertEquals([
			'country' => null,
			'zip' => null,
			'street' => null,
			'radius' => null,
		], $pGeoPositionFieldHandler->getActiveFieldsWithValue());
	}


	/**
	 *
	 * @return RecordManagerRead
	 *
	 */

	private function getRecordManagerMock(): RecordManagerRead
	{
		$pRecordManager = $this->getMock(RecordManagerRead::class,
			['getMainTable', 'getIdColumnMain', 'getRecords', 'getRowByName', 'addWhere']);
		$pRecordManager->method('getMainTable')->will($this->returnValue('oo_plugin_listviews'));
		$pRecordManager->method('getIdColumnMain')->will($this->returnValue('listview_id'));
		$pRecordManager->method('getRecords')->will($this->returnValue($this->_pRecord));
		$pRecordManager->expects($this->once())->method('addWhere')->with('`listview_id` = "3"');

		return $pRecordManager;
	}
}