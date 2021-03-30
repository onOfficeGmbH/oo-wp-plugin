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

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use onOffice\SDK\onOfficeSDK;
use onOffice\WPlugin\DataFormConfiguration\UnknownFormException;
use onOffice\WPlugin\Field\CustomLabel\CustomLabelRead;
use onOffice\WPlugin\Field\FieldModuleCollectionDecoratorCustomLabelForm;
use onOffice\WPlugin\Field\FieldModuleCollectionDecoratorFormContact;
use onOffice\WPlugin\Field\FieldModuleCollectionDecoratorInternalAnnotations;
use onOffice\WPlugin\Field\FieldModuleCollectionDecoratorSearchcriteria;
use onOffice\WPlugin\Language;
use onOffice\WPlugin\Record\RecordManagerReadForm;
use onOffice\WPlugin\Types\FieldsCollection;
use function __;


/**
 *
 */

class FieldsCollectionBuilderShort
{
	/** @var Container */
	private $_pContainer;


	/**
	 *
	 * @param Container $pContainer
	 *
	 */

	public function __construct(Container $pContainer)
	{
		$this->_pContainer = $pContainer;
	}

	/**
	 *
	 * @param FieldsCollection $pFieldsCollection
	 * @return $this
	 * @throws DependencyException
	 * @throws NotFoundException
	 */

	public function addFieldsAddressEstate(FieldsCollection $pFieldsCollection): self
	{
		$pFieldLoader = $this->_pContainer->get(FieldLoaderGeneric::class);
		$pFieldCollectionAddressEstate = $this->_pContainer->get(FieldsCollectionBuilder::class)
			->buildFieldsCollection($pFieldLoader);
		$pFieldsCollection->merge($pFieldCollectionAddressEstate);
		return $this;
	}

	/**
	 *
	 * @param FieldsCollection $pFieldsCollection
	 * @return $this
	 * @throws DependencyException
	 * @throws NotFoundException
	 */

	public function addFieldsSearchCriteria(FieldsCollection $pFieldsCollection): self
	{
		$pFieldLoaderNoGeo = $this->_pContainer->get(FieldCategoryToFieldConverterSearchCriteriaBackendNoGeo::class);
		$pFieldsCollectionSearchCriteria = $this->buildSearchcriteriaFieldsCollectionByFieldLoader($pFieldLoaderNoGeo);
		$pFieldsCollection->merge($pFieldsCollectionSearchCriteria);
		return $this;
	}

	/**
	 *
	 * @param FieldsCollection $pFieldsCollection
	 * @return $this
	 * @throws DependencyException
	 * @throws NotFoundException
	 */

	public function addFieldsFormBackend(FieldsCollection $pFieldsCollection): self
	{
		$pFieldsCollectionTmp = new FieldModuleCollectionDecoratorInternalAnnotations
			(new FieldModuleCollectionDecoratorSearchcriteria
				(new FieldModuleCollectionDecoratorFormContact(new FieldsCollection)));
		$pFieldsCollection->merge($pFieldsCollectionTmp);
		$pFieldCategoryConverterGeoPos = $this->_pContainer->get(FieldCategoryToFieldConverterSearchCriteriaGeoBackend::class);
		$pFieldsCollectionGeo = $this->buildSearchcriteriaFieldsCollectionByFieldLoader($pFieldCategoryConverterGeoPos);
		$pFieldsCollection->merge($pFieldsCollectionGeo);
		return $this;
	}

	/**
	 *
	 * @param FieldsCollection $pFieldsCollection
	 * @return $this
	 * @throws DependencyException
	 * @throws NotFoundException
	 */

	public function addFieldsFormFrontend(FieldsCollection $pFieldsCollection): self
	{
		$pFieldsCollectionTmp = new FieldModuleCollectionDecoratorFormContact
			(new FieldModuleCollectionDecoratorSearchcriteria(new FieldsCollection));
		$pFieldsCollection->merge($pFieldsCollectionTmp);
		$pFieldCategoryConverterGeoPos = $this->_pContainer->get(FieldCategoryToFieldConverterSearchCriteriaGeoFrontend::class);
		$pFieldsCollectionGeo = $this->buildSearchcriteriaFieldsCollectionByFieldLoader($pFieldCategoryConverterGeoPos);
		$pFieldsCollection->merge($pFieldsCollectionGeo);
		return $this;
	}

	/**
	 *
	 * @param FieldsCollection $pFieldsCollection
	 * @param string $formName
	 * @return $this
	 * @throws DependencyException
	 * @throws NotFoundException
	 * @throws UnknownFormException
	 */

	public function addCustomLabelFieldsFormFrontend(FieldsCollection $pFieldsCollection, $formName): self
	{
		$pFieldsCollectionTmp = new FieldModuleCollectionDecoratorCustomLabelForm($pFieldsCollection, $formName);
		$pFieldsCollection->merge($pFieldsCollectionTmp);
		$pFieldCategoryConverterGeoPos = $this->_pContainer->get(FieldCategoryToFieldConverterSearchCriteriaGeoFrontend::class);
		$pFieldsCollectionGeo = $this->buildSearchcriteriaFieldsCollectionByFieldLoader($pFieldCategoryConverterGeoPos);
		$pFieldsCollection->merge($pFieldsCollectionGeo);
		return $this;
	}

	/**
	 *
	 * @param FieldCategoryToFieldConverter $pConverter
	 * @return FieldsCollection
	 * @throws DependencyException
	 * @throws NotFoundException
	 */

	private function buildSearchcriteriaFieldsCollectionByFieldLoader(FieldCategoryToFieldConverter $pConverter): FieldsCollection
	{
		$pFieldLoader = $this->_pContainer->make(FieldLoaderSearchCriteria::class, ['pFieldCategoryConverter' => $pConverter]);
		return $this->_pContainer->get(FieldsCollectionBuilder::class)
			->buildFieldsCollection($pFieldLoader);
	}

	/**
	 *
	 * @param FieldsCollection $pFieldsCollection
	 * @return $this
	 * @throws DependencyException
	 * @throws NotFoundException
	 */

	public function addFieldsSearchCriteriaSpecificBackend(FieldsCollection $pFieldsCollection): self
	{
		$pFieldsCollectionTmp = new FieldModuleCollectionDecoratorInternalAnnotations
			(new FieldModuleCollectionDecoratorSearchcriteria(new FieldsCollection));
		$pFieldsCollection->merge($pFieldsCollectionTmp, __('Form Specific Fields', 'onoffice-for-wp-websites'));
		$pFieldCategoryConverterGeoPos = $this->_pContainer->get
			(FieldCategoryToFieldConverterSearchCriteriaGeoBackend::class);
		$pFieldsCollectionGeo = $this->buildSearchcriteriaFieldsCollectionByFieldLoader($pFieldCategoryConverterGeoPos);
		$pFieldsCollection->merge($pFieldsCollectionGeo);
		return $this;
	}
}
