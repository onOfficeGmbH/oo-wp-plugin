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

use Closure;
use DI\Container;
use DI\ContainerBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Exception as ExceptionAlias;
use onOffice\SDK\Exception\HttpFetchNoResultException;
use onOffice\SDK\onOfficeSDK;
use onOffice\WPlugin\API\ApiClientException;
use onOffice\WPlugin\API\APIEmptyResultException;
use onOffice\WPlugin\Controller\EstateListEnvironment;
use onOffice\WPlugin\Controller\EstateListEnvironmentDefault;
use onOffice\WPlugin\Controller\EstateViewSimilarEstates;
use onOffice\WPlugin\Controller\EstateViewSimilarEstatesEnvironment;
use onOffice\WPlugin\Controller\EstateViewSimilarEstatesEnvironmentDefault;
use onOffice\WPlugin\Controller\EstateViewSimilarEstatesEnvironmentTest;
use onOffice\WPlugin\DataView\DataDetailView;
use onOffice\WPlugin\DataView\DataListView;
use onOffice\WPlugin\DataView\DataSimilarEstatesSettingsHandler;
use onOffice\WPlugin\DataView\DataViewSimilarEstates;
use onOffice\WPlugin\DataView\UnknownViewException;
use onOffice\WPlugin\EstateDetail;
use onOffice\WPlugin\EstateList;
use onOffice\WPlugin\Field\Collection\FieldsCollectionBuilderShort;
use onOffice\WPlugin\Filter\DefaultFilterBuilderDetailView;
use onOffice\WPlugin\Filter\DefaultFilterBuilderListView;
use onOffice\WPlugin\Filter\GeoSearchBuilderEmpty;
use onOffice\WPlugin\GeoPosition;
use onOffice\WPlugin\SDKWrapper;
use onOffice\WPlugin\Types\EstateStatusLabel;
use onOffice\WPlugin\WP\WPOptionWrapperTest;
use WP_Rewrite;
use WP_UnitTestCase;
use function json_decode;

/**
 *
 * @url http://www.onoffice.de
 * @copyright 2003-2019, onOffice(R) GmbH
 *
 */

class TestClassEstateDetail
	extends WP_UnitTestCase
{
	/** @var EstateDetail */
	private $_pEstate = null;

	/** @var EstateViewSimilarEstates */
	private $_pEstateViewSimilarEstates = null;

	/** @var Container */
	private $_pContainer;

	/** @var EstateListEnvironment */
	private $_pEnvironment = null;

	/** @var EstateViewSimilarEstatesEnvironment */
	private $_pEstateViewSimilarEstatesEnvironment = null;

	/** @var SDKWrapperMocker */
	private $_pSDKWrapperMocker = null;

	/** @var DataSimilarEstatesSettingsHandler */
	private $_pDataSimilarEstatesSettingsHandler = null;


	const VALUES_BY_ROW = [
		'template' => '/test/template.php',
		'fields' => [
			'Objektnr_extern',
			'wohnflaeche',
			'kaufpreis',
		],
		'similar_estates_template' => '/test/similar/template.php',
		'same_kind' => true,
		'same_maketing_method' => true,
		'show_archived' => true,
		'show_reference' => true,
		'radius' => 35,
		'amount' => 13,
		'enablesimilarestates' => true,
	];

	/** @var array */
	private $_resultSubRecords = [
		1051 => [
			'Id' => 1051,
			'laengengrad' => 50.3333,
			'breitengrad' => 13.777,
			'strasse' => 'Teststreet',
			'plz' => '12345',
			'land' => 'Testcountry',
		],
		1082 => [
			'Id' => 1082,
			'laengengrad' => 50.3273,
			'breitengrad' => 13.2222,
			'strasse' => 'Testotherstreet',
			'plz' => '12347',
			'land' => 'Testcountry',
		],
	];

	/** @var array */
	private $_mainRecords = [
		15 => [
			'Id' => 15,
			'laengengrad' => 50.24584,
			'breitengrad' => 13.3847,
			'strasse' => '',
			'plz' => '',
			'land' => '',
			'vermarktungsart' => 'kauf',
			'objektart' => 'haus',
		]
	];


	/**
	 *
	 */

	public function testGetSimilarEstates()
	{
			$this->_pEstate->getSimilarEstates($this->_pContainer);
	}


	/**
	 *
	 * @before
	 *
	 */

	public function prepareEstateDetail()
	{
		$this->_pSDKWrapperMocker = new SDKWrapperMocker();

		$dataReadEstateFormatted = json_decode
			(file_get_contents(__DIR__.'/resources/ApiResponseReadEstatesDetailPublishedENG.json'), true);
		$responseReadEstate = $dataReadEstateFormatted['response'];
		$parametersReadEstate = $dataReadEstateFormatted['parameters'];
		$dataReadEstateRaw = json_decode
			(file_get_contents(__DIR__.'/resources/ApiResponseReadEstatesDetailPublishedENGRaw.json'), true);
		$responseReadEstateRaw = $dataReadEstateRaw['response'];
		$parametersReadEstateRaw = $dataReadEstateRaw['parameters'];
		$responseGetIdsFromRelation = json_decode
			(file_get_contents(__DIR__.'/resources/ApiResponseGetIdsFromRelation.json'), true);
		$responseGetEstatePictures = json_decode
			(file_get_contents(__DIR__.'/resources/ApiResponseGetEstatePictures.json'), true);

		$this->_pSDKWrapperMocker->addResponseByParameters
			(onOfficeSDK::ACTION_ID_READ, 'estate', '', $parametersReadEstate, null, $responseReadEstate);
		$this->_pSDKWrapperMocker->addResponseByParameters
			(onOfficeSDK::ACTION_ID_READ, 'estate', '', $parametersReadEstateRaw, null, $responseReadEstateRaw);
		$this->_pSDKWrapperMocker->addResponseByParameters
			(onOfficeSDK::ACTION_ID_GET, 'idsfromrelation', '', [
				'parentids' => [15, 1051, 1082, 1193, 1071],
				'relationtype' => 'urn:onoffice-de-ns:smart:2.5:relationTypes:estate:address:contactPerson'
			], null, $responseGetIdsFromRelation);

		$this->_pSDKWrapperMocker->addResponseByParameters
		(onOfficeSDK::ACTION_ID_GET, 'estatepictures', '', [
			'estateids' => [15,1051,1082,1193,5448],
			'categories' => ['Titelbild', "Foto"],
			'language' => 'ENG'
		], null, $responseGetEstatePictures);

		$pContainerBuilder = new ContainerBuilder;
		$pContainerBuilder->addDefinitions(ONOFFICE_DI_CONFIG_PATH);
		$this->_pContainer = $pContainerBuilder->build();
		$this->_pContainer->set(SDKWrapper::class, $this->_pSDKWrapperMocker);
		$this->_pEnvironment = $this->getMockBuilder(EstateListEnvironmentDefault::class)
			->setConstructorArgs([$this->_pContainer])
			->setMethods([
				'getDefaultFilterBuilder',
				'getGeoSearchBuilder',
				'getEstateStatusLabel',
				'setDefaultFilterBuilder',
				'getEstateFiles',
				'getFieldnames',
				'getAddressList',
				'getEstateUnitsByName',
				'getDataDetailViewHandler',
			])
			->getMock();

		$pWPOptionWrapper = new WPOptionWrapperTest();
		$pDataSimilarEstatesSettingsHandler = new DataSimilarEstatesSettingsHandler($pWPOptionWrapper);
		$pDataSimilarView = $pDataSimilarEstatesSettingsHandler->createDataSimilarEstatesSettingsByValues(self::VALUES_BY_ROW);
		$this->_pDataSimilarEstatesSettingsHandler = $this->getMockBuilder(DataSimilarEstatesSettingsHandler::class)
			->setConstructorArgs([$pWPOptionWrapper])
			->setMethods(['getDataSimilarEstatesSettings'])
			->getMock();
		$this->_pDataSimilarEstatesSettingsHandler->method('getDataSimilarEstatesSettings')->willReturn($pDataSimilarView);
		$this->_pContainer->set(DataSimilarEstatesSettingsHandler::class, $this->_pDataSimilarEstatesSettingsHandler);
		$pDataViewSimilarEstates = $pDataSimilarView->getDataViewSimilarEstates();
		$pDataViewSimilarEstates->setTemplate('resources/templates/unitlist.php');
		$this->_pEstateViewSimilarEstatesEnvironment = new EstateViewSimilarEstatesEnvironmentTest($pDataViewSimilarEstates);
		$this->_pEstateViewSimilarEstatesEnvironment->getEstateList()->setEstateData($this->_resultSubRecords);
		$this->_pEstateViewSimilarEstatesEnvironment->getEstateList()->loadEstates();


		$pDataView = new DataListView(1, 'SimilarEstates');
		$pDataView->setFields(['Id', 'laengengrad', 'breitengrad', 'strasse', 'plz', 'land']);
		$pEstateListBase = new EstateListMocker($pDataView);
		$pEstateListBase->setEstateData($this->_mainRecords);
		$pEstateListBase->loadEstates();

		$this->_pEstateViewSimilarEstates = new EstateViewSimilarEstates
		($pDataViewSimilarEstates, $this->_pEstateViewSimilarEstatesEnvironment);
		$this->_pEstateViewSimilarEstates->loadByMainEstates($pEstateListBase);

		$pDataListView = $this->getDataView();
		$pDefaultFilterBuilder = new DefaultFilterBuilderDetailView();
		$pDefaultFilterBuilder->setEstateId(15);
		$this->_pEnvironment->method('getDefaultFilterBuilder')->willReturn($pDefaultFilterBuilder);
		$this->_pEstate = new EstateDetail($pDataListView, $this->_pEnvironment);

		$pEstateStatusLabel = $this->getMockBuilder(EstateStatusLabel::class)
			->setMethods(['getFieldsByPrio', 'getLabel'])
			->getMock();
		$pEstateStatusLabel->method('getFieldsByPrio')->willReturn([
			'referenz',
			'reserviert',
			'verkauft',
			'exclusive',
			'neu',
			'top_angebot',
			'preisreduktion',
			'courtage_frei',
			'objekt_des_tages',
		]);
		$this->_pEnvironment->method('getEstateStatusLabel')->willReturn
			($pEstateStatusLabel);
	}


	/**
	 *
	 * @return DataDetailView
	 *
	 */

	private function getDataView(): DataDetailView
	{
		$pDataView = new DataDetailView();
		$pDataView->setFields(['Id', 'objektart', 'objekttyp']);
		$pDataView->setPictureTypes(['Titelbild', 'Foto']);
		return $pDataView;
	}
}
