<?php

/**
 *
 *    Copyright (C) 2020 onOffice GmbH
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

use onOffice\SDK\onOfficeSDK;
use onOffice\WPlugin\Field\Collection\FieldsCollectionConfiguratorForm;
use onOffice\WPlugin\Field\UnknownFieldException;
use onOffice\WPlugin\Form;
use onOffice\WPlugin\Types\Field;
use onOffice\WPlugin\Types\FieldsCollection;
use onOffice\WPlugin\Types\FieldTypes;

class TestClassFieldsCollectionConfiguratorForm
	extends \WP_UnitTestCase
{
	/**
	 * @throws UnknownFieldException
	 */
	public function testConfigureForApplicantSearchForm()
	{
		$pFieldsCollection = $this->buildFieldsCollectionRange();
		$pSubject = new FieldsCollectionConfiguratorForm;
		$pFieldsCollectionNew = $pSubject->configureForApplicantSearchForm($pFieldsCollection);

		$this->assertCount(2, $pFieldsCollectionNew->getAllFields());

		foreach ($pFieldsCollectionNew->getAllFields() as $pField) {
			$pFieldOriginal = $pFieldsCollection->getFieldByModuleAndName
				($pField->getModule(), $pField->getName());
			$this->assertNotSame($pFieldOriginal, $pField);
			$this->assertFalse($pField->getIsRangeField());
		}
	}

	/**
	 * @throws UnknownFieldException
	 */
	public function testConfigureForApplicantSearchFormWithPassingKritBemerkungOeffentlichField()
	{
		$pFieldsCollection = $this->buildFieldsCollectionWithKritBemerkungOeffentlichField();
		$pSubject = new FieldsCollectionConfiguratorForm;
		$pFieldsCollectionNew = $pSubject->configureForApplicantSearchForm($pFieldsCollection);
		$this->assertCount(1, $pFieldsCollectionNew->getAllFields());
		foreach ($pFieldsCollectionNew->getAllFields() as $pField) {
			$pFieldOriginal = $pFieldsCollection->getFieldByModuleAndName
			($pField->getModule(), $pField->getName());
			$this->assertNotSame($pFieldOriginal, $pField);
			$this->assertThat($pField, $this->callback(function(Field $pField) {
				return
					($pField->getName() === 'objekttyp' && $pField->getType() === FieldTypes::FIELD_TYPE_MULTISELECT) ||
					($pField->getName() !== 'krit_bemerkung_oeffentlich' && $pField->getType() === FieldTypes::FIELD_TYPE_SINGLESELECT);
			}));
		}
	}


	/**
	 * @throws UnknownFieldException
	 */
	public function testConfigureForInterestForm()
	{
		$pFieldsCollection = $this->buildFieldsCollectionSelect();
		$pSubject = new FieldsCollectionConfiguratorForm;
		$pFieldsCollectionNew = $pSubject->configureForInterestForm($pFieldsCollection);
		$this->assertCount(2, $pFieldsCollectionNew->getAllFields());
		foreach ($pFieldsCollectionNew->getAllFields() as $pField) {
			$pFieldOriginal = $pFieldsCollection->getFieldByModuleAndName
				($pField->getModule(), $pField->getName());
			$this->assertNotSame($pFieldOriginal, $pField);
			$this->assertThat($pField, $this->callback(function(Field $pField) {
				return
					($pField->getName() === 'objekttyp' && $pField->getType() === FieldTypes::FIELD_TYPE_MULTISELECT) ||
					($pField->getName() !== 'objekttyp' && $pField->getType() === FieldTypes::FIELD_TYPE_SINGLESELECT);
			}));
		}
	}

	/**
	 * @throws UnknownFieldException
	 */
	public function testConfigureForInterestFormWithPassingMessageField()
	{
		$pFieldsCollection = $this->buildFieldsCollectionWithMessageField();
		$pSubject = new FieldsCollectionConfiguratorForm;
		$pFieldsCollectionNew = $pSubject->configureForInterestForm($pFieldsCollection);
		$this->assertCount(1, $pFieldsCollectionNew->getAllFields());
		foreach ($pFieldsCollectionNew->getAllFields() as $pField) {
			$pFieldOriginal = $pFieldsCollection->getFieldByModuleAndName
			($pField->getModule(), $pField->getName());
			$this->assertNotSame($pFieldOriginal, $pField);
			$this->assertThat($pField, $this->callback(function (Field $pField) {
				return
					($pField->getName() === 'objekttyp' && $pField->getType() === FieldTypes::FIELD_TYPE_MULTISELECT) ||
					($pField->getName() !== 'message' && $pField->getType() === FieldTypes::FIELD_TYPE_TEXT);
			}));
		}
	}

	/**
	 * @throws UnknownFieldException
	 */
	public function testConfigureForOwnerForm()
	{
		$pFieldsCollection = $this->buildFieldsCollectionDifferentModules();
		$pSubject = new FieldsCollectionConfiguratorForm;
		$pFieldsCollectionNew = $pSubject->configureForOwnerForm($pFieldsCollection);
		$this->assertCount(2, $pFieldsCollectionNew->getAllFields());
		$this->assertNotSame($pFieldsCollectionNew, $pFieldsCollection);
		$pFieldsCollection->removeFieldByModuleAndName
			(onOfficeSDK::MODULE_SEARCHCRITERIA, 'testModuleSearchCriteria');
		$this->assertEquals($pFieldsCollection, $pFieldsCollectionNew);
	}

	/**
	 * @throws UnknownFieldException
	 */
	public function testBuildForFormType()
	{
		$pSubject = new FieldsCollectionConfiguratorForm;
		$pFieldsCollection = new FieldsCollection;
		$pFieldRange = new Field('a', 'a');
		$pFieldRange->setIsRangeField(true);
		$pFieldSingleSelect = new Field('objekttyp', onOfficeSDK::MODULE_SEARCHCRITERIA);
		$pFieldSingleSelect->setType(FieldTypes::FIELD_TYPE_SINGLESELECT);
		$pFieldsCollection->addField($pFieldRange);
		$pFieldsCollection->addField($pFieldSingleSelect);
		$pCollectionApplicantSearch = $pSubject->buildForFormType($pFieldsCollection, Form::TYPE_APPLICANT_SEARCH);
		$this->assertFalse($pCollectionApplicantSearch
			->getFieldByKeyUnsafe('a')->getIsRangeField());
		$pCollectionInterest = $pSubject->buildForFormType($pFieldsCollection, Form::TYPE_INTEREST);
		$this->assertEquals(FieldTypes::FIELD_TYPE_MULTISELECT,
			$pCollectionInterest->getFieldByKeyUnsafe('objekttyp')->getType());
		$pCollectionOwner = $pSubject->buildForFormType($pFieldsCollection, Form::TYPE_OWNER);
		$this->assertEmpty($pCollectionOwner->getAllFields());
	}

	/**
	 * @return FieldsCollection
	 */
	private function buildFieldsCollectionRange(): FieldsCollection
	{
		$pFieldsCollection = new FieldsCollection;
		$pField1 = new Field('testField', 'testModule');
		$pField1->setType(FieldTypes::FIELD_TYPE_INTEGER);
		$pField1->setIsRangeField(true);
		$pField2 = new Field('testField2', 'testModule2');
		$pField2->setType(FieldTypes::FIELD_TYPE_INTEGER);
		$pField2->setIsRangeField(true);
		$pFieldsCollection->addField($pField1);
		$pFieldsCollection->addField($pField2);
		return $pFieldsCollection;
	}

	/**
	 * @return FieldsCollection
	 */
	private function buildFieldsCollectionSelect(): FieldsCollection
	{
		$pFieldsCollection = new FieldsCollection;
		$pField3 = new Field('testFieldSingleselect', onOfficeSDK::MODULE_SEARCHCRITERIA);
		$pField3->setType(FieldTypes::FIELD_TYPE_SINGLESELECT);
		$pField4 = new Field('objekttyp', onOfficeSDK::MODULE_SEARCHCRITERIA);
		$pField4->setType(FieldTypes::FIELD_TYPE_SINGLESELECT);
		$pFieldsCollection->addField($pField3);
		$pFieldsCollection->addField($pField4);
		return $pFieldsCollection;
	}

	/**
	 * @return FieldsCollection
	 */
	private function buildFieldsCollectionDifferentModules(): FieldsCollection
	{
		$pFieldsCollection = new FieldsCollection;
		$pField1 = new Field('testModuleAddress', onOfficeSDK::MODULE_ADDRESS);
		$pField2 = new Field('testModuleEstate', onOfficeSDK::MODULE_ESTATE);
		$pField3 = new Field('testModuleSearchCriteria', onOfficeSDK::MODULE_SEARCHCRITERIA);
		$pFieldsCollection->addField($pField1);
		$pFieldsCollection->addField($pField2);
		$pFieldsCollection->addField($pField3);
		return $pFieldsCollection;
	}

	/**
	 * @return FieldsCollection
	 */
	private function buildFieldsCollectionWithKritBemerkungOeffentlichField(): FieldsCollection
	{
		$pFieldsCollection = new FieldsCollection;
		$pField1 = new Field('krit_bemerkung_oeffentlich', onOfficeSDK::MODULE_SEARCHCRITERIA);
		$pField1->setType(FieldTypes::FIELD_TYPE_SINGLESELECT);
		$pField2 = new Field('objekttyp', onOfficeSDK::MODULE_SEARCHCRITERIA);
		$pField2->setType(FieldTypes::FIELD_TYPE_SINGLESELECT);
		$pFieldsCollection->addField($pField1);
		$pFieldsCollection->addField($pField2);
		return $pFieldsCollection;
	}

	/**
	 * @return FieldsCollection
	 */
	private function buildFieldsCollectionWithMessageField(): FieldsCollection
	{
		$pFieldsCollection = new FieldsCollection;
		$pField1 = new Field('message', '');
		$pField1->setType(FieldTypes::FIELD_TYPE_TEXT);
		$pField2 = new Field('objekttyp', onOfficeSDK::MODULE_SEARCHCRITERIA);
		$pField2->setType(FieldTypes::FIELD_TYPE_SINGLESELECT);
		$pFieldsCollection->addField($pField1);
		$pFieldsCollection->addField($pField2);
		return $pFieldsCollection;
	}
}