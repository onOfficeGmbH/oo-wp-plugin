<?php

/**
 *
 *    Copyright (C) 2016-2020 onOffice GmbH
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

namespace onOffice\WPlugin;

use DI\ContainerBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use onOffice\SDK\Exception\HttpFetchNoResultException;
use onOffice\SDK\onOfficeSDK;
use onOffice\WPlugin\API\APIClientActionGeneric;
use onOffice\WPlugin\Controller\EstateDetailUrl;
use onOffice\WPlugin\Controller\EstateListBase;
use onOffice\WPlugin\Controller\EstateListEnvironment;
use onOffice\WPlugin\Controller\EstateListEnvironmentDefault;
use onOffice\WPlugin\Controller\GeoPositionFieldHandler;
use onOffice\WPlugin\DataView\DataListView;
use onOffice\WPlugin\DataView\DataView;
use onOffice\WPlugin\DataView\DataViewFilterableFields;
use onOffice\WPlugin\DataView\UnknownViewException;
use onOffice\WPlugin\Field\Collection\FieldsCollectionFieldDuplicatorForGeoEstate;
use onOffice\WPlugin\Field\DistinctFieldsHandler;
use onOffice\WPlugin\Field\DistinctFieldsHandlerModel;
use onOffice\WPlugin\Field\DistinctFieldsHandlerModelBuilder;
use onOffice\WPlugin\Field\FieldModuleCollectionDecoratorGeoPositionFrontend;
use onOffice\WPlugin\Field\OutputFields;
use onOffice\WPlugin\Field\UnknownFieldException;
use onOffice\WPlugin\Filter\DefaultFilterBuilder;
use onOffice\WPlugin\Filter\GeoSearchBuilder;
use onOffice\WPlugin\Types\FieldsCollection;
use onOffice\WPlugin\ViewFieldModifier\EstateViewFieldModifierTypes;
use onOffice\WPlugin\ViewFieldModifier\ViewFieldModifierHandler;
use onOffice\WPlugin\WP\WPQueryWrapper;
use function esc_url;
use function get_page_link;
use function home_url;

class EstateList
	implements EstateListBase
{
	/** @var array */
	private $_records = [];

	/** @var array */
	private $_recordsRaw = [];

	/** @var EstateFiles */
	private $_pEstateFiles = null;

	/** @var array */
	private $_currentEstate = [];

	/** @var array */
	private $_estateContacts = [];

	/** @var int */
	private $_currentEstatePage = 1;

	/** @var int */
	private $_numEstatePages = null;

	/** @var DataView */
	private $_pDataView = null;

	/** @var string */
	private $_unitsViewName = null;

	/** @var bool */
	private $_formatOutput = true;

	/** @var EstateListEnvironment */
	private $_pEnvironment =  null;

	/** @var APIClientActionGeneric */
	private $_pApiClientAction = null;

	/** @var GeoSearchBuilder */
	private $_pGeoSearchBuilder = null;

	/** @var EstateDetailUrl */
	private $_pLanguageSwitcher;

	/**
	 * @param DataView $pDataView
	 * @param EstateListEnvironment $pEnvironment
	 * @throws DependencyException
	 * @throws NotFoundException
	 */
	public function __construct(DataView $pDataView, EstateListEnvironment $pEnvironment = null)
	{
		$pContainerBuilder = new ContainerBuilder;
		$pContainerBuilder->addDefinitions(ONOFFICE_DI_CONFIG_PATH);
		$pContainer = $pContainerBuilder->build();
		$this->_pEnvironment = $pEnvironment ?? new EstateListEnvironmentDefault($pContainer);
		$this->_pDataView = $pDataView;
		$pSDKWrapper = $this->_pEnvironment->getSDKWrapper();
		$this->_pApiClientAction = new APIClientActionGeneric
			($pSDKWrapper, onOfficeSDK::ACTION_ID_READ, 'estate');
		$this->_pGeoSearchBuilder = $this->_pEnvironment->getGeoSearchBuilder();
		$this->_pLanguageSwitcher = $pContainer->get(EstateDetailUrl::class);
	}

	/**
	 * @return int
	 * @throws API\ApiClientException
	 */
	protected function getNumEstatePages()
	{
		$recordNumOverAll = $this->getEstateOverallCount();
		$recordsPerPageView = $this->_pDataView->getRecordsPerPage();
		// 20 is the default of API in case recordsPerPage <= 0
		$recordsPerPage = $recordsPerPageView <= 0 ? 20 : $recordsPerPageView;
		$numEstatePages = (int)ceil($recordNumOverAll / $recordsPerPage);

		return $numEstatePages;
	}


	/**
	 * @return int
	 */
	protected function getRecordsPerPage()
	{
		return $this->_pDataView->getRecordsPerPage();
	}

	/**
	 * @return array
	 */
	protected function getPreloadEstateFileCategories()
	{
		return $this->_pDataView->getPictureTypes();
	}

	/**
	 * @param int $currentPage
	 * @param DataView $pDataListView
	 * @throws API\APIEmptyResultException
	 * @throws DependencyException
	 * @throws NotFoundException
	 * @throws UnknownViewException
	 * @throws HttpFetchNoResultException
	 * @throws API\ApiClientException
	 */
	public function loadEstates(int $currentPage = 1, DataView $pDataListView = null)
	{
		if ($pDataListView === null)
		{
			$pDataListView = $this->_pDataView;
		}

		$this->_pEnvironment->getFieldnames()->loadLanguage();
		$this->loadRecords($currentPage);

		$fileCategories = $this->getPreloadEstateFileCategories();

		$estateIds = $this->getEstateIdToForeignMapping($this->_records);

		if ($estateIds !== []) {
			$this->getEstateContactPerson($estateIds);

			$this->_pEstateFiles = $this->_pEnvironment->getEstateFiles();
			$this->_pEstateFiles->getAllFiles($fileCategories, $estateIds, $this->_pEnvironment->getSDKWrapper());
		}

		if ($pDataListView->getRandom()) {
			$this->_pEnvironment->shuffle($this->_records);
		}

		$this->_numEstatePages = $this->getNumEstatePages();
		$this->resetEstateIterator();
	}

	/**
	 * @param int $currentPage
	 * @throws UnknownViewException
	 * @throws API\ApiClientException
	 */
	private function loadRecords(int $currentPage)
	{
		$estateParameters = $this->getEstateParameters($currentPage, $this->_formatOutput);
		$this->_pApiClientAction->setParameters($estateParameters);
		$this->_pApiClientAction->addRequestToQueue();

		$estateParametersRaw = $this->getEstateParameters($currentPage, false);
		$estateParametersRaw['data'] = $this->_pEnvironment->getEstateStatusLabel()->getFieldsByPrio();
		$estateParametersRaw['data'] []= 'vermarktungsart';
		$pApiClientActionRawValues = clone $this->_pApiClientAction;
		$pApiClientActionRawValues->setParameters($estateParametersRaw);
		$pApiClientActionRawValues->addRequestToQueue()->sendRequests();

		$this->_records = $this->_pApiClientAction->getResultRecords();
		$recordsRaw = $pApiClientActionRawValues->getResultRecords();
		$this->_recordsRaw = array_combine(array_column($recordsRaw, 'id'), $recordsRaw);
	}

	/**
	 * @param array $estateIds
	 * @throws DependencyException
	 * @throws NotFoundException
	 * @throws API\ApiClientException
	 */
	private function getEstateContactPerson(array $estateIds)
	{
		$pSDKWrapper = $this->_pEnvironment->getSDKWrapper();

		$parameters = [
			'parentids' => array_keys($estateIds),
			'relationtype' => onOfficeSDK::RELATION_TYPE_CONTACT_BROKER,
		];
		$pAPIClientAction = new APIClientActionGeneric
			($pSDKWrapper, onOfficeSDK::ACTION_ID_GET, 'idsfromrelation');
		$pAPIClientAction->setParameters($parameters);
		$pAPIClientAction->addRequestToQueue()->sendRequests();
		$this->collectEstateContactPerson($pAPIClientAction->getResultRecords(), $estateIds);
	}

	/**
	 * @param int $currentPage
	 * @param bool $formatOutput
	 * @return array
	 * @throws UnknownViewException
	 */
	private function getEstateParameters(int $currentPage, bool $formatOutput)
	{
		$language = Language::getDefault();
		$pListView = $this->_pDataView;
		$filter = $this->_pEnvironment->getDefaultFilterBuilder()->buildFilter();

		$numRecordsPerPage = $this->getRecordsPerPage();

		$pFieldModifierHandler = new ViewFieldModifierHandler($pListView->getFields(),
			onOfficeSDK::MODULE_ESTATE);

		$requestParams = [
			'data' => $pFieldModifierHandler->getAllAPIFields(),
			'filter' => $filter,
			'estatelanguage' => $language,
			'outputlanguage' => $language,
			'listlimit' => $numRecordsPerPage,
			'formatoutput' => $formatOutput,
			'addMainLangId' => true,
		];

		if (!$pListView->getRandom()) {
			$offset = ( $currentPage - 1 ) * $numRecordsPerPage;
			$this->_currentEstatePage = $currentPage;
			$requestParams += [
				'listoffset' => $offset
			];
		}

		$requestParams += $this->addExtraParams();

		return $requestParams;
	}

	/**
	 * @return array
	 */
	protected function addExtraParams(): array
	{
		$pListView = $this->_pDataView;
		$requestParams = [];

		if ($pListView->getSortby() !== '' && !$this->_pDataView->getRandom()) {
			$requestParams['sortby'] =  $pListView->getSortBy();
		}

		if ($pListView->getSortorder() !== '') {
			$requestParams['sortorder'] =$pListView->getSortorder();
		}

		if ($pListView->getFilterId() !== 0) {
			$requestParams['filterid'] = $pListView->getFilterId();
		}

		// only do georange search if requested in listview configuration
		if ($pListView instanceof DataViewFilterableFields &&
			in_array(GeoPosition::FIELD_GEO_POSITION, $pListView->getFilterableFields(), true)) {
			$geoRangeSearchParameters = $this->getGeoSearchBuilder()->buildParameters();

			if ($geoRangeSearchParameters !== []) {
				$requestParams['georangesearch'] = $geoRangeSearchParameters;
			}
		}

		return $requestParams;
	}

	/**
	 * @param array $estateResponseArray
	 * @return array Mapping: mainEstateId => multiLangId
	 */
	private function getEstateIdToForeignMapping($estateResponseArray): array
	{
		$estateIds = [];

		foreach ($estateResponseArray as $estate) {
			$elements = $estate['elements'];
			$estateMainId = $elements['mainLangId'] ?? $estate['id'];
			$estateIds[$estateMainId] = $estate['id'];
		}

		return $estateIds;
	}

	/**
	 * @param array $responseArrayContacts
	 * @param array $estateIds
	 * @throws DependencyException
	 * @throws NotFoundException
	 * @throws API\ApiClientException
	 */
	private function collectEstateContactPerson($responseArrayContacts, array $estateIds)
	{
		$records = $responseArrayContacts[0]['elements'] ?? [];
		$allAddressIds = [];

		foreach ($records as $estateId => $addressIds) {
			$subjectEstateId = $estateIds[$estateId];
			$this->_estateContacts[$subjectEstateId] = $addressIds;
			$allAddressIds = array_unique(array_merge($allAddressIds, $addressIds));
		}

		$fields = $this->_pDataView->getAddressFields();

		if ($fields !== [] && $allAddressIds !== []) {
			$this->_pEnvironment->getAddressList()->loadAdressesById($allAddressIds, $fields);
		}
	}

	/**
	 * @param string $modifier
	 * @return ArrayContainerEscape
	 * @throws \Exception
	 */
	public function estateIterator($modifier = EstateViewFieldModifierTypes::MODIFIER_TYPE_DEFAULT)
	{
		global $numpages, $multipage, $more, $paged;

		if (null !== $this->_numEstatePages &&
			!$this->_pDataView->getRandom()) {
			$multipage = true;

			$paged = $this->_currentEstatePage;
			$more = true;
			$numpages = $this->_numEstatePages;
		}

		$pEstateFieldModifierHandler = $this->_pEnvironment->getViewFieldModifierHandler
			($this->_pDataView->getFields(), $modifier);

		$currentRecord = current($this->_records);
		next($this->_records);

		if (false === $currentRecord) {
			return false;
		}
		$this->_currentEstate['id'] = $currentRecord['id'];
		$recordElements = $currentRecord['elements'];
		$this->_currentEstate['mainId'] = $recordElements['mainLangId'] ??
			$this->_currentEstate['id'];
		$this->_currentEstate['title'] = $currentRecord['elements']['objekttitel'] ?? '';

		$recordModified = $pEstateFieldModifierHandler->processRecord($currentRecord['elements']);
		$recordRaw = $this->_recordsRaw[$this->_currentEstate['id']]['elements'];

		if ($this->getShowEstateMarketingStatus()) {
			$pEstateStatusLabel = $this->_pEnvironment->getEstateStatusLabel();
			$recordModified['vermarktungsstatus'] = $pEstateStatusLabel->getLabel($recordRaw);
		}

		$pArrayContainer = new ArrayContainerEscape($recordModified);

		return $pArrayContainer;
	}

	/**
	 * @return int
	 * @throws API\ApiClientException
	 */
	public function getEstateOverallCount()
	{
		return $this->_pApiClientAction->getResultMeta()['cntabsolute'];
	}

	/**
	 * @param string $field
	 * @return string
	 */
	public function getFieldLabel($field): string
	{
		$recordType = onOfficeSDK::MODULE_ESTATE;
		$fieldNewName = $this->_pEnvironment->getFieldnames()->getFieldLabel($field, $recordType);

		return $fieldNewName;
	}

	/**
	 * @return string
	 * @throws DependencyException
	 * @throws NotFoundException
	 */
	public function getEstateLink(): string
	{
		$pageId = $this->_pEnvironment->getDataDetailViewHandler()
			->getDetailView()->getPageId();

		$fullLink = '#';
		if ($pageId !== 0) {
			$estate = $this->_currentEstate['mainId'];
			$title = $this->_currentEstate['title'] ?? '';
			$url = get_page_link($pageId);
			$fullLink = $this->_pLanguageSwitcher->createEstateDetailLink($url, $estate, $title);
		}

		return $fullLink;
	}

	/**
	 * @param array $types
	 * @return array
	 */
	public function	getEstatePictures(array $types = null)
	{
		$estateId = $this->_currentEstate['id'];
		$estateFiles = [];
		$estateImages = $this->_pEstateFiles->getEstatePictures($estateId);

		foreach ($estateImages as $image) {
			if (null !== $types && !in_array($image['type'], $types, true)) {
				continue;
			}
			$estateFiles []= $image['id'];
		}

		return $estateFiles;
	}

	/**
	 * Not supported in list view
	 * @return array Returns an array if Movie Links are active and displayed as Link
	 */
	public function getEstateMovieLinks(): array
	{
		return [];
	}

	/**
	 * Not supported in list view
	 * @param array $options
	 * @return array
	 */
	public function getMovieEmbedPlayers(array $options = []): array
	{
		return [];
	}

	/**
	 * @param int $imageId
	 * @param array $options
	 * @return string
	 */
	public function getEstatePictureUrl($imageId, array $options = null)
	{
		$currentEstate = $this->_currentEstate['id'];
		return $this->_pEstateFiles->getEstateFileUrl($imageId, $currentEstate, $options);
	}

	/**
	 * @param int $imageId
	 * @return string
	 */
	public function getEstatePictureTitle($imageId)
	{
		$currentEstate = $this->_currentEstate['id'];
		return $this->_pEstateFiles->getEstatePictureTitle($imageId, $currentEstate);
	}

	/**
	 * @param int $imageId
	 * @return string
	 */
	public function getEstatePictureText($imageId)
	{
		$currentEstate = $this->_currentEstate['id'];
		return $this->_pEstateFiles->getEstatePictureText($imageId, $currentEstate);
	}

	/**
	 * @param int $imageId
	 * @return array
	 */
	public function getEstatePictureValues($imageId)
	{
		$currentEstate = $this->_currentEstate['id'];
		return $this->_pEstateFiles->getEstatePictureValues($imageId, $currentEstate);
	}

	/**
	 * @return array
	 */
	public function getEstateContactIds(): array
	{
		$recordId = $this->_currentEstate['id'];
		return $this->_estateContacts[$recordId] ?? [];
	}

	/**
	 * @return array
	 */
	public function getEstateContacts()
	{
		$addressIds = $this->getEstateContactIds();
		$result = [];

		foreach ($addressIds as $addressId) {
			$currentAddressData = $this->_pEnvironment->getAddressList()->getAddressById($addressId);
			$pArrayContainerCurrentAddress = new ArrayContainerEscape($currentAddressData);
			$result []= $pArrayContainerCurrentAddress;
		}

		return $result;
	}

	/**
	 * @return int
	 */
	public function getCurrentEstateId(): int
	{
		return $this->_currentEstate['id'];
	}

	/**
	 * @return int
	 */
	public function getCurrentMultiLangEstateMainId()
	{
		return $this->_currentEstate['mainId'];
	}

	/**
	 * @return string
	 * @throws API\APIEmptyResultException
	 * @throws DependencyException
	 * @throws NotFoundException
	 * @throws HttpFetchNoResultException
	 * @throws UnknownViewException
	 */
	public function getEstateUnits()
	{
		$estateId = $this->getCurrentMultiLangEstateMainId();
		$htmlOutput = '';

		if ($this->_unitsViewName != null) {
			$pEstateUnits = $this->_pEnvironment->getEstateUnitsByName($this->_unitsViewName);
			$pEstateUnits->loadByMainEstates($this);
			$unitCount = $pEstateUnits->getSubEstateCount($estateId);

			if ($unitCount > 0) {
				$htmlOutput = $pEstateUnits->generateHtmlOutput($estateId);
			}
		}

		return $htmlOutput;
	}

	/**
	 * @return string
	 */
	public function getDocument()
	{
		$document = '';
		if ($this->_pDataView->getExpose() !== '') {
			$documentlink = home_url('document-pdf/'.$this->_pDataView->getName()
				.'/'.$this->getCurrentMultiLangEstateMainId());
			$document = esc_url($documentlink);
		}
		return $document;
	}

	/**
	 * @return string[] An array of visible fields
	 * @throws DependencyException
	 * @throws NotFoundException
	 * @throws UnknownFieldException
	 */
	public function getVisibleFilterableFields(): array
	{
		$pContainer = $this->_pEnvironment->getContainer();
		$pFieldsCollection = new FieldsCollection();
		$pFieldsCollectionBuilderShort = $this->_pEnvironment->getFieldsCollectionBuilderShort();
		$pFieldsCollectionBuilderShort->addFieldsAddressEstate($pFieldsCollection);
		$pFieldsCollection->merge
			(new FieldModuleCollectionDecoratorGeoPositionFrontend(new FieldsCollection));
		$pFieldsCollectionFieldDuplicatorForGeoEstate =
			$pContainer->get(FieldsCollectionFieldDuplicatorForGeoEstate::class);
		$pFieldsCollectionFieldDuplicatorForGeoEstate->duplicateFields($pFieldsCollection);
		/** @var DistinctFieldsHandler $pDistinctFieldsHandler */
		$pDistinctFieldsHandler = $pContainer->get(DistinctFieldsHandler::class);
		$pFieldsCollection = $pDistinctFieldsHandler->modifyFieldsCollectionForEstate($this->_pDataView, $pFieldsCollection);

		$fieldsValues = $pContainer->get(OutputFields::class)
			->getVisibleFilterableFields($this->_pDataView,
				$pFieldsCollection, new GeoPositionFieldHandler);
		$result = [];
		foreach ($fieldsValues as $field => $value) {
			$result[$field] = $pFieldsCollection->getFieldByKeyUnsafe($field)
				->getAsRow();
			$result[$field]['name'] = $field;
			$result[$field]['value'] = $value;
		}
		return $result;
	}

	/**
	 *
	 */
	public function resetEstateIterator()
	{
		reset($this->_records);
	}

	/**
	 * @return bool
	 */
	public function getShowEstateMarketingStatus(): bool
	{
		return $this->_pDataView instanceof DataListView &&
			$this->_pDataView->getShowStatus();
	}

	/**
	 * @return array
	 */
	public function getEstateIds(): array
	{
		return array_column($this->_records, 'id');
	}

	/** @return EstateFiles */
	protected function getEstateFiles()
		{ return $this->_pEstateFiles; }

	/** @return DataView */
	public function getDataView(): DataView
		{ return $this->_pDataView; }

	/**
	 * @return DefaultFilterBuilder
	 * @throws UnknownViewException
	 */
	public function getDefaultFilterBuilder(): DefaultFilterBuilder
		{ return $this->_pEnvironment->getDefaultFilterBuilder(); }

	/** @param DefaultFilterBuilder $pDefaultFilterBuilder */
	public function setDefaultFilterBuilder(DefaultFilterBuilder $pDefaultFilterBuilder)
		{ $this->_pEnvironment->setDefaultFilterBuilder($pDefaultFilterBuilder); }

	/** @return string */
	public function getUnitsViewName()
		{ return $this->_unitsViewName; }

	/** @param string $unitsViewName */
	public function setUnitsViewName($unitsViewName)
		{ $this->_unitsViewName = $unitsViewName; }

	/** @return GeoSearchBuilder */
	public function getGeoSearchBuilder(): GeoSearchBuilder
		{ return $this->_pGeoSearchBuilder; }

	/** @param GeoSearchBuilder $pGeoSearchBuilder */
	public function setGeoSearchBuilder(GeoSearchBuilder $pGeoSearchBuilder)
		{ $this->_pGeoSearchBuilder = $pGeoSearchBuilder; }

	/** @return bool */
	public function getFormatOutput(): bool
		{ return $this->_formatOutput; }

	/** @param bool $formatOutput */
	public function setFormatOutput(bool $formatOutput)
		{ $this->_formatOutput = $formatOutput; }

	/** @return EstateListEnvironment */
	public function getEnvironment(): EstateListEnvironment
		{ return $this->_pEnvironment; }
}
