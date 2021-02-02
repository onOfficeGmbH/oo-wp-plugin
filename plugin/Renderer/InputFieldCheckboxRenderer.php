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

namespace onOffice\WPlugin\Renderer;

use DI\Container;
use DI\ContainerBuilder;
use Exception;
use onOffice\WPlugin\API\APIClientCredentialsException;
use onOffice\WPlugin\Field\Collection\FieldsCollectionBuilderShort;
use onOffice\WPlugin\Field\UnknownFieldException;
use onOffice\WPlugin\Types\FieldsCollection;
use onOffice\WPlugin\Types\FieldTypes;
use const ONOFFICE_DI_CONFIG_PATH;
use function esc_html;


/**
 *
 */

class InputFieldCheckboxRenderer
	extends InputFieldRenderer
{

	/** @var Container */
	private $_pContainer = null;

	/** @var array */
	private $_checkedValues = [];

	/**
	 *
	 * @param string $name
	 * @param mixed $value
	 * @param Container $_pContainer
	 * @throws Exception
	 */
	public function __construct($name, $value, Container $_pContainer = null)
	{
		if ($_pContainer === null) {
			$pDIContainerBuilder = new ContainerBuilder;
			$pDIContainerBuilder->addDefinitions(ONOFFICE_DI_CONFIG_PATH);
			$_pContainer = $pDIContainerBuilder->build();
		}
		$this->_pContainer = $_pContainer;
		parent::__construct('checkbox', $name, $value);
	}

	/**
	 *
	 * @param string $key
	 * @param FieldsCollection $pFieldsCollection
	 * @return bool
	 */

	private function isMultipleSelect(string $key, FieldsCollection $pFieldsCollection): bool
	{
		$module = $this->getOoModule();

		try {
			$type = $pFieldsCollection->getFieldByModuleAndName($module, $key)->getType();
			return FieldTypes::isMultipleSelectType($type);
		} catch (UnknownFieldException $pEx) {
			return false;
		}
	}

	/**
	 *
	 * @return FieldsCollection
	 *
	 * @throws Exception
	 */

	private function buildFieldsCollection(): FieldsCollection
	{
		$pFieldsCollection = new FieldsCollection();

		/** @var FieldsCollectionBuilderShort $pFieldsCollectionBuilder */
		$pFieldsCollectionBuilder = $this->_pContainer->get(FieldsCollectionBuilderShort::class);

		try {
			$pFieldsCollectionBuilder
				->addFieldsAddressEstate($pFieldsCollection)
				->addFieldsSearchCriteria($pFieldsCollection);
		} catch (APIClientCredentialsException $pEx) {}
		return $pFieldsCollection;
	}

	/**
	 *
	 * @throws Exception
	 */

	public function render()
	{
		$pFieldsCollection = $this->buildFieldsCollection();
		if (is_array($this->getValue())) {
			foreach ($this->getValue() as $key => $label) {
				$inputId = 'label'.$this->getGuiId().'b'.$key;
				$onofficeMultipleSelect = $this->isMultipleSelect($key, $pFieldsCollection) ? '1' : '0';

				echo '<input type="'.esc_html($this->getType()).'" name="'.esc_html($this->getName())
					.'" value="'.esc_html($key).'"'
					.(is_array($this->_checkedValues) && in_array($key, $this->_checkedValues) ? ' checked="checked" ' : '')
					.$this->renderAdditionalAttributes()
					.' onoffice-multipleSelectType="'.$onofficeMultipleSelect.'"'
					.' id="'.esc_html($inputId).(($key === "multiParkingLot") ? ' input-multiParkingLot' : '').'"'
					. (($key === "multiParkingLot") ? ' disabled class="input-multiParkingLot"' : '') . '>'
					.'<label'.(($key === "multiParkingLot") ? ' class="label-multiParkingLot" ' : '').'for="'.esc_html($inputId).'">'.esc_html($label)
					. (($key === "multiParkingLot") ? '<span class="hint">'.__('(Can not be displayed)', 'onoffice-for-wp-websites').'</span>' : '').'</label><br>';
			}
		} else {
			echo '<input type="'.esc_html($this->getType()).'" name="'.esc_html($this->getName())
				.'" value="'.esc_html($this->getValue()).'"'
				.($this->getValue() == $this->_checkedValues ? ' checked="checked" ' : '')
				.$this->renderAdditionalAttributes()
				.' id="'.esc_html($this->getGuiId()).'">';
		}
	}


	/** @param array $checkedValues */
	public function setCheckedValues($checkedValues)
		{ $this->_checkedValues = $checkedValues;}

	/** @return array */
	public function getCheckedValues()
		{ return $this->_checkedValues; }
}
