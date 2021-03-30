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
use Generator;
use onOffice\SDK\onOfficeSDK;
use onOffice\WPlugin\Field\CustomLabel\CustomLabelRead;
use onOffice\WPlugin\Field\FieldModuleCollection;
use onOffice\WPlugin\Field\FieldModuleCollectionDecoratorCustomLabelForm;
use onOffice\WPlugin\Form;
use onOffice\WPlugin\Record\RecordManagerReadForm;
use onOffice\WPlugin\Types\Field;
use onOffice\WPlugin\Types\FieldsCollection;
use WP_UnitTestCase;
use wpdb;

/**
 *
 * @url http://www.onoffice.de
 * @copyright 2003-2018, onOffice(R) GmbH
 *
 */
class TestClassFieldModuleCollectionDecoratorCustomLabel
	extends WP_UnitTestCase
{
	/** @var RecordManagerReadForm */
	private $_pRecordManagerReadForm = null;

	/** @var CustomLabelRead */
	private $_pCustomLabelRead = null;

	/** @var FieldModuleCollection */
	private $_pFieldModuleCollection = null;

	/** @var Container */
	private $_pContainer = null;

	/** @var wpdb */
	private $_pWPDBMock = null;

	/**
	 * @before
	 */

	public function prepare()
	{
		$rows = [
			(object)[
				'customs_labels_id' => 33,
				'locale' => 'de_DE',
				'value' => 'Deutschland',
			]
		];
		$this->_pWPDBMock = $this->getMockBuilder(\wpdb::class)
			->disableOriginalConstructor()
			->setMethods(['get_results'])
			->getMock();
		$pContainerBuilder = new ContainerBuilder;
		$pContainerBuilder->addDefinitions(ONOFFICE_DI_CONFIG_PATH);
		$this->_pContainer = $pContainerBuilder->build();

		$this->_pRecordManagerReadForm = $this->getMockBuilder(RecordManagerReadForm::class)
			->getMock();
		$this->_pRecordManagerReadForm->method('readFieldsByFormId')->will($this->returnValueMap([
			[1, $this->getBasicFieldsArray(1, Form::TYPE_CONTACT)]
		]));

		$this->_pRecordManagerReadForm->method('getRowByName')->will($this->returnValueMap([
			['testForm1', $this->getBaseRow(1, Form::TYPE_CONTACT)]
		]));

		$this->_pContainer->set(RecordManagerReadForm::class, $this->_pRecordManagerReadForm);
		$pFieldsCollectionByFormIds = $this->_pContainer->get(RecordManagerReadForm::class)->readFieldsByFormId(1);

		$this->_pFieldModuleCollection = new FieldsCollection();
		foreach ($pFieldsCollectionByFormIds as $pFieldsCollectionByFormId) {
			$this->_pFieldModuleCollection->addField(new Field($pFieldsCollectionByFormId['fieldname'],
				onOfficeSDK::MODULE_ADDRESS));
			$this->_pFieldModuleCollection->addField(new Field($pFieldsCollectionByFormId['fieldname'],
				onOfficeSDK::MODULE_SEARCHCRITERIA));
			$this->_pFieldModuleCollection->addField(new Field($pFieldsCollectionByFormId['fieldname'],
				onOfficeSDK::MODULE_ESTATE));
			$this->_pFieldModuleCollection->addField(new Field($pFieldsCollectionByFormId['fieldname'],
				''));
			$this->_pWPDBMock->method('get_results')->will($this->returnValue($rows));
			$this->_pCustomLabelRead = new CustomLabelRead($this->_pWPDBMock);
			$this->_pCustomLabelRead->readCustomLabelByFormIdAndFieldName(1, $pFieldsCollectionByFormId['fieldname'],
				'de_DE');
		}
		$this->_pContainer->set(CustomLabelRead::class, $this->_pCustomLabelRead);
	}


	/**
	 *
	 */

	public function testGetAllFields()
	{
		$pDecoratorCustomLabel = new FieldModuleCollectionDecoratorCustomLabelForm($this->_pFieldModuleCollection,
			'testForm1', $this->_pContainer);
		$fieldCustomLabels = $pDecoratorCustomLabel->getFieldCustomLabels();
		$cloneFields = array();
		foreach ($pDecoratorCustomLabel->getAllFields() as $key => $field) {
			$cloneFields[$key] = clone $field;
			$module = $cloneFields[$key]->getModule();
			$name = $cloneFields[$key]->getName();
			$label = $fieldCustomLabels[$module][$name] ?? null;

			if ($label !== null) {
				$this->assertEquals($label, $cloneFields[$key]->getLabel());
			}
		}
	}

	/**
	 *
	 * @param int $formId
	 * @param string $formType
	 * @return array
	 *
	 */

	private function getBasicFieldsArray(int $formId, string $formType): array
	{
		$fields = [
			[
				'form_fieldconfig_id' => '1',
				'form_id' => $formId,
				'order' => '1',
				'fieldname' => 'Test 1',
				'fieldlabel' => 'Field label 1',
				'module' => onOfficeSDK::MODULE_ADDRESS,
				'individual_fieldname' => '0',
				'availableOptions' => '0',
				'required' => '1',
			],
			[
				'form_fieldconfig_id' => '1',
				'form_id' => $formId,
				'order' => '1',
				'fieldname' => 'Test 3',
				'fieldlabel' => 'Field label 3',
				'module' => onOfficeSDK::MODULE_SEARCHCRITERIA,
				'individual_fieldname' => '0',
				'availableOptions' => '0',
				'required' => '1',
			],
			[
				'form_fieldconfig_id' => '1',
				'form_id' => $formId,
				'order' => '1',
				'fieldname' => 'Test 3',
				'fieldlabel' => 'Field label 3',
				'module' => onOfficeSDK::MODULE_ESTATE,
				'individual_fieldname' => '0',
				'availableOptions' => '0',
				'required' => '1',
			],
			[
				'form_fieldconfig_id' => '1',
				'form_id' => $formId,
				'order' => '1',
				'fieldname' => 'Test 4',
				'fieldlabel' => 'Field label 4',
				'module' => '',
				'individual_fieldname' => '0',
				'availableOptions' => '0',
				'required' => '1',
			],
		];

		return $fields;
	}

	/**
	 *
	 * @param int $formId
	 * @param string $formType
	 * @return array
	 *
	 */

	private function getBaseRow(int $formId, string $formType): array
	{
		return [
			'form_id' => $formId,
			'name' => 'testForm' . $formId,
			'form_type' => $formType,
			'template' => 'testtemplate.php',
			'recipient' => 'test@my-onoffice.com',
			'subject' => 'A Subject',
			'createaddress' => '1',
			'limitresults' => '30',
			'checkduplicates' => '1',
			'pages' => '3',
			'captcha' => '1',
			'newsletter' => '1',
			'availableOptions' => '1',
			'show_estate_context' => '0',
		];
	}
}
