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

namespace onOffice\WPlugin\Gui;

use onOffice\WPlugin\DataFormConfiguration\DataFormConfigurationFactory;
use onOffice\WPlugin\Form;
use UnexpectedValueException;

/**
 *
 * Facade class for AdminPageForm*-classes
 *
 */

class AdminPageFormSettingsMain
	extends AdminPageAjax
{
	/** */
	const GET_PARAM_TYPE = 'type';

	/** */
	const PARAM_FORMID = 'id';

	/** @var array */
	private $_mappingTypeClass = [
		Form::TYPE_CONTACT => AdminPageFormSettingsContact::class,
		Form::TYPE_APPLICANT_SEARCH => AdminPageFormSettingsApplicantSearch::class,
		Form::TYPE_INTEREST => AdminPageFormSettingsInterestOwner::class,
		Form::TYPE_OWNER => AdminPageFormSettingsInterestOwner::class,
	];

	/** @var AdminPageFormSettingsBase */
	private $_pInstance = null;


	/**
	 *
	 * @param string $type
	 * @param int $id
	 *
	 */

	private function initSubClass(string $type = null, int $id = null)
	{
		if ($this->_pInstance !== null) {
			return;
		}

		if (!empty($id)) {
			$pDataFormConfigFactory = new DataFormConfigurationFactory();
			$pDataFormConfigFactory->setIsAdminInterface(true);
			$pFormConfiguration = $pDataFormConfigFactory->loadByFormId($id);
			$type = $pFormConfiguration->getFormType();
		}

		$className = $this->getClassNameByType($type);
		$this->_pInstance = new $className($this->getPageSlug());
		$this->configureAdminPage($this->_pInstance, $type);
	}


	/**
	 *
	 */

	private function initSubclassForAjax()
	{
		$type = filter_input(INPUT_POST, self::GET_PARAM_TYPE, FILTER_SANITIZE_STRING);
		$id = filter_input(INPUT_POST, 'record_id', FILTER_VALIDATE_INT);
		$this->initSubClass($type, $id);
	}


	/**
	 *
	 */

	public function initSubClassForGet()
	{
		$type = filter_input(INPUT_GET, self::GET_PARAM_TYPE, FILTER_SANITIZE_STRING);
		$id = filter_input(INPUT_GET, self::PARAM_FORMID, FILTER_VALIDATE_INT);
		$this->initSubClass($type, $id);
	}


	/**
	 *
	 * @param AdminPageFormSettingsBase $pAdminPage
	 * @param string $type
	 *
	 */

	private function configureAdminPage(AdminPageFormSettingsBase $pAdminPage, string $type)
	{
		$pAdminPage->setType($type);

		switch ($type) {
			case Form::TYPE_INTEREST:
				/* @var $pAdminPage AdminPageFormSettingsInterestOwner */
				$pAdminPage->setShowSearchCriteriaFields(true);
				$pAdminPage->setShowAddressFields(true);
				$pAdminPage->setShowCheckDuplicates(true); // address will be created anyway
				$pAdminPage->setShowGeoPositionSettings(true);
				$pAdminPage->setShowContactTypeSelect(true);
				break;
			case Form::TYPE_OWNER:
				/* @var $pAdminPage AdminPageFormSettingsInterestOwner */
				$pAdminPage->setShowEstateFields(true);
				$pAdminPage->setShowAddressFields(true);
				$pAdminPage->setShowCheckDuplicates(true); // address will be created anyway
				$pAdminPage->setShowMessageInput(true);
				$pAdminPage->setShowContactTypeSelect(true);
				break;
			case Form::TYPE_CONTACT:
				/* @var $pAdminPage AdminPageFormSettingsContact */
				$pAdminPage->setShowCreateAddress(true);
				$pAdminPage->setShowCheckDuplicates(true);
				$pAdminPage->setShowAddressFields(true);
				$pAdminPage->setShowMessageInput(true);
				$pAdminPage->setShowNewsletterCheckbox(true);
				$pAdminPage->setShowEstateContextCheckbox(true);
				$pAdminPage->setShowContactTypeSelect(true);
				break;
			case Form::TYPE_APPLICANT_SEARCH:
				/* @var $pAdminPage AdminPageFormSettingsApplicantSearch */
				$pAdminPage->setShowSearchCriteriaFields(true);
				break;
		}
	}


	/**
	 *
	 */

	public function renderContent()
	{
		$this->_pInstance->renderContent();
	}


	/**
	 *
	 */

	public function handleAdminNotices()
	{
		$this->_pInstance->handleAdminNotices();
	}


	/**
	 *
	 * @param string $type
	 * @return string
	 * @throws UnexpectedValueException
	 *
	 */

	private function getClassNameByType(string $type): string
	{
		$result = $this->_mappingTypeClass[$type] ?? null;

		if ($result === null) {
			throw new UnexpectedValueException('Unknown class');
		}

		return $result;
	}


	/**
	 *
	 */

	protected function buildForms()
	{
		$this->_pInstance->buildForms();
	}


	/**
	 *
	 */

	public function ajax_action()
	{
		$this->initSubclassForAjax();
		$this->_pInstance->ajax_action();
	}


	/**
	 *
	 */

	public function doExtraEnqueues()
	{
		$this->_pInstance->doExtraEnqueues();
	}


	/**
	 *
	 * @return array
	 *
	 */

	public function getEnqueueData(): array
	{
		return $this->_pInstance->getEnqueueData();
	}
}
