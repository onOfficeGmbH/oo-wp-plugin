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

namespace onOffice\WPlugin\Field\Collection;

use Generator;
use onOffice\SDK\onOfficeSDK;
use onOffice\WPlugin\API\APIClientActionGeneric;
use onOffice\WPlugin\API\APIEmptyResultException;
use onOffice\WPlugin\Language;
use onOffice\WPlugin\Region\Region;
use onOffice\WPlugin\Region\RegionController;
use onOffice\WPlugin\Region\RegionFilter;
use onOffice\WPlugin\SDKWrapper;
use onOffice\WPlugin\Types\FieldTypes;

/**
 *
 */

class FieldLoaderGeneric
	implements FieldLoader
{
	/** @var SDKWrapper */
	private $_pSDKWrapper;

	/** @var RegionController */
	private $_pRegionController;

	/** @var RegionFilter */
	private $_pRegionFilter;

	/**
	 * @param SDKWrapper $pSDKWrapper
	 * @param RegionController $pRegionController
	 * @param RegionFilter $pRegionFilter
	 */
	public function __construct(
		SDKWrapper $pSDKWrapper,
		RegionController $pRegionController,
		RegionFilter $pRegionFilter)
	{
		$this->_pSDKWrapper = $pSDKWrapper;
		$this->_pRegionController = $pRegionController;
		$this->_pRegionFilter = $pRegionFilter;
	}

	/**
	 * @return Generator
	 * @throws APIEmptyResultException
	 */
	public function load(): Generator
	{
		$result = $this->sendRequest();

		foreach ($result as $moduleProperties) {
			$module = $moduleProperties['id'];
			$fieldArray = $moduleProperties['elements'];

			if (isset($fieldArray['label'])) {
				unset($fieldArray['label']);
			}
			$listTypeUnSupported = ['user', 'datei', 'redhint', 'blackhint', 'dividingline'];
			foreach ($fieldArray as $fieldName => $fieldProperties) {
				if ($module == onOfficeSDK::MODULE_ADDRESS && in_array($fieldProperties['type'], $listTypeUnSupported)) {
					continue;
				}
				if ($module === onOfficeSDK::MODULE_ESTATE && $fieldName === 'regionaler_zusatz') {
					$fieldProperties['type'] = FieldTypes::FIELD_TYPE_SINGLESELECT;
					$this->_pRegionController->fetchRegions();
					$regions = $this->_pRegionController->getRegions();
					$fieldProperties['permittedvalues'] = $this->_pRegionFilter
						->buildRegions($regions);
					$fieldProperties['labelOnlyValues'] = $this->_pRegionFilter
						->collectLabelOnlyValues($regions);
				}

				if ($module === onOfficeSDK::MODULE_ADDRESS && $fieldName == 'ArtDaten') {
					$permittedValues = $fieldProperties['permittedvalues'];
					unset($permittedValues['Systembenutzer']);
					$fieldProperties['permittedvalues'] = $permittedValues;
				}

				$fieldProperties['module'] = $module;
				yield $fieldName => $fieldProperties;
			}
		}
	}

	/**
	 * @return array
	 * @throws APIEmptyResultException
	 */
	private function sendRequest(): array
	{
		$parametersGetFieldList = [
			'labels' => true,
			'showContent' => true,
			'showTable' => true,
			'language' => Language::getDefault(),
			'modules' => [onOfficeSDK::MODULE_ADDRESS, onOfficeSDK::MODULE_ESTATE],
			'realDataTypes' => true
		];

		$pApiClientActionFields = new APIClientActionGeneric
			($this->_pSDKWrapper, onOfficeSDK::ACTION_ID_GET, 'fields');
		$pApiClientActionFields->setParameters($parametersGetFieldList);
		$pApiClientActionFields->addRequestToQueue()->sendRequests();

		return $pApiClientActionFields->getResultRecords();
	}
}
