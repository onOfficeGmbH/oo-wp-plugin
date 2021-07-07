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

use Exception;
use onOffice\WPlugin\Model\FormModel;
use onOffice\WPlugin\Model\InputModelBase;
use onOffice\WPlugin\Model\InputModelDB;
use onOffice\WPlugin\Model\InputModelOption;
use onOffice\WPlugin\Utility\__String;
use function __;
use function add_settings_field;
use function add_settings_section;
use function esc_html;
use function esc_html__;
use function register_setting;

/**
 *
 */

class InputModelRenderer
{
	/**
	 * @param FormModel $pFormModel
	 * @throws Exception
	 */
	public function buildForm(FormModel $pFormModel)
	{
		add_settings_section($pFormModel->getGroupSlug(), $pFormModel->getLabel(),
			$pFormModel->getTextCallback(), $pFormModel->getPageSlug());

		foreach ($pFormModel->getInputModel() as $pInputModel) {
			$pInputField = $this->createInputField($pInputModel, $pFormModel);
			add_settings_field($pInputModel->getIdentifier(), $pInputModel->getLabel(),
				[$pInputField, 'render'], $pFormModel->getPageSlug(), $pFormModel->getGroupSlug());
		}
	}

	/**
	 * @param FormModel $pFormModel
	 * @return void
	 * @throws Exception
	 */
	public function buildForAjax(FormModel $pFormModel)
	{
		if ($pFormModel->getIsInvisibleForm()) {
			return;
		}

		foreach ($pFormModel->getInputModel() as $pInputModel) {
			$pInputField = $this->createInputField($pInputModel, $pFormModel);
			if ($pInputModel->getHtmlType() !== InputModelBase::HTML_TYPE_LABEL) {
				echo '<p id="" class="wp-clearfix">';
				echo '<label class="howto" for="'.esc_html($pInputField->getGuiId()).'">';
				echo esc_html__($pInputModel->getLabel());
				echo '</label>';
				$pInputField->render();
				echo '</p>';
			} else {
				$pInputField->render();
			}
		}
	}


	/**
	 *
	 * @param FormModel $pFormModel
	 *
	 */

	public function registerFields(FormModel $pFormModel)
	{
		foreach ($pFormModel->getInputModel() as $pInputModel) {
			if ($pInputModel instanceof InputModelOption) {
				register_setting($pFormModel->getPageSlug(), $pInputModel->getIdentifier(), [
					'type' => $pInputModel->getType(),
					'description' => $pInputModel->getDescription(),
					'sanitize_callback' => $pInputModel->getSanitizeCallback(),
					'show_in_rest' => $pInputModel->getShowInRest(),
					'default' => $pInputModel->getDefault(),
				]);
			}
		}
	}

	/**
	 *
	 * @param InputModelBase $pInputModel
	 * @param FormModel $pFormModel
	 * @return InputFieldLabelRenderer
	 * @throws Exception
	 */

	private function createInputField(InputModelBase $pInputModel, FormModel $pFormModel)
	{
		$pInstance = null;
		$onOfficeInputFields = true;
		$elementName = $this->getHtmlElementName($pInputModel);

		switch ($pInputModel->getHtmlType())
		{
			case InputModelOption::HTML_TYPE_SELECT:
				$pInstance = new InputFieldSelectRenderer($elementName,
					$pInputModel->getValuesAvailable());
				$pInstance->setSelectedValue($pInputModel->getValue());
				$pInstance->setLabelOnlyValues($pInputModel->getLabelOnlyValues());
				break;

			case InputModelOption::HTML_TYPE_CHECKBOX:
				$pInstance = new InputFieldCheckboxRenderer($elementName,
					$pInputModel->getValuesAvailable(),  $pInputModel->getDescriptionText());
				$pInstance->setCheckedValues($pInputModel->getValue());
				break;

			case InputModelOption::HTML_TYPE_COMPLEX_SORTABLE_CHECKBOX_LIST:
				$pInstance = new InputFieldComplexSortableListRenderer($elementName,
				$pInputModel->getValuesAvailable());
				$pInstance->setCheckedValues($pInputModel->getValue());
				break;

			case InputModelOption::HTML_TYPE_COMPLEX_SORTABLE_DETAIL_LIST:
				$pContent = new InputFieldComplexSortableDetailListContentDefault();
				$pInstance = new InputFieldComplexSortableDetailListRenderer($elementName,
					[$pInputModel->getValue()]);
				$pInstance->setExtraInputModels($pInputModel->getReferencedInputModels());
				$pInstance->setContentRenderer($pContent);
				$pInstance->setAllFields($pInputModel->getValuesAvailable());
				break;

			case InputModelOption::HTML_TYPE_CHECKBOX_BUTTON:
				$onOfficeInputFields = false;
				$pInstance = new InputFieldCheckboxButtonRenderer($elementName,
					$pInputModel->getValuesAvailable());
				$pInstance->setCheckedValues($pInputModel->getValue());
				$pInstance->setId($pInputModel->getId());
				$pInstance->setLabel($pInputModel->getLabel());
				$pInstance->setOoModule($pFormModel->getOoModule());
				$pInstance->addAdditionalAttribute('class', 'onoffice-possible-input');
				if ($pInputModel->getSpecialDivId() != null) {
					$pInstance->addAdditionalAttribute('data-action-div', $pInputModel->getSpecialDivId());
				}
				break;

			case InputModelOption::HTML_TYPE_RADIO:
				$pInstance = new InputFieldRadioRenderer($elementName,
					$pInputModel->getValuesAvailable());
				$pInstance->setCheckedValue($pInputModel->getValue());
				break;

			case InputModelOption::HTML_TYPE_TEXT:
				$pInstance = new InputFieldTextRenderer('text', $elementName);
				$pInstance->addAdditionalAttribute('size', '50');
				if ($pInputModel->getIsPassword()) {
					$pInstance->addAdditionalAttribute('placeholder',
						__('(Remains unchanged)', 'onoffice-for-wp-websites'));
				} else {
					$placeholder = $pInputModel->getPlaceholder();
					if (!__String::getNew($placeholder)->isEmpty()) {
						$pInstance->addAdditionalAttribute('placeholder', $placeholder);
					}
					$pInstance->setValue($pInputModel->getValue());
				}

				break;
			case InputModelOption::HTML_TYPE_HIDDEN:
				$pInstance = new InputFieldTextRenderer('hidden', $elementName);
				$pInstance->setValue($pInputModel->getValue());

				break;

			case InputModelBase::HTML_TYPE_LABEL:
				$pInstance = new InputFieldLabelRenderer
					(null, $elementName, $pInputModel->getValue());
				$pInstance->setLabel($pInputModel->getLabel());
				$pInstance->setValueEnclosure($pInputModel->getValueEnclosure());

				break;

			case InputModelBase::HTML_TYPE_CHOSEN:
				$pInstance = new InputFieldChosenRenderer(
					$pInputModel->getIdentifier(),
					$pInputModel->getValuesAvailable());
				$pInstance->addAdditionalAttribute('class', 'chosen-select');
				$pInstance->setSelectedValue($pInputModel->getValue());
				break;
		}

		if ($pInstance !== null) {
			if ($onOfficeInputFields) {
				$pInstance->addAdditionalAttribute('class', 'onoffice-input');
			}

			if ($pInputModel instanceof InputModelDB) {
				if (!__String::getNew($pInputModel->getModule())->isEmpty()) {
					$module = $pInputModel->getModule();
					$pInstance->addAdditionalAttribute('data-onoffice-module', $module);
				}

				if ($pInputModel->getIgnore()) {
					$pInstance->addAdditionalAttribute('data-onoffice-ignore', 'true');
				}
			}
		}

		return $pInstance;
	}

	/**
	 * @param InputModelBase $pInputModel
	 * @return string New name of HTML element, with brackets if multi == true
	 */
	private function getHtmlElementName(InputModelBase $pInputModel): string
	{
		$name = $pInputModel->getIdentifier();
		switch ($pInputModel->getHtmlType())
		{
			case InputModelOption::HTML_TYPE_SELECT:
			case InputModelOption::HTML_TYPE_CHECKBOX:
			case InputModelOption::HTML_TYPE_CHECKBOX_BUTTON:
			case InputModelOption::HTML_TYPE_TEXT:
			case InputModelOption::HTML_TYPE_HIDDEN:
				if ($pInputModel->getIsMulti()) {
					$name .= '[]';
				}
				break;
		}

		return $name;
	}
}
