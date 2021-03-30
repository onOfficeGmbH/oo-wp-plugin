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

namespace onOffice\WPlugin\Field\CustomLabel;

use onOffice\WPlugin\Record\RecordManagerFactory;
use onOffice\WPlugin\Record\RecordManagerInsertException;
use onOffice\WPlugin\Record\RecordManagerInsertGeneric;


/**
 *
 */
class CustomLabelCreate
{
	/** */
	const TABLE_CUSTOMS_LABELS = 'oo_plugin_fieldconfig_form_customs_labels';

	/** */
	const TABLE_TRANSLATED_LABELS = 'oo_plugin_fieldconfig_form_translated_labels';


	/** @var RecordManagerFactory */
	private $_pRecordManagerFactory;


	/**
	 *
	 * @param RecordManagerFactory $pRecordManagerFactory
	 *
	 */

	public function __construct(RecordManagerFactory $pRecordManagerFactory)
	{
		$this->_pRecordManagerFactory = $pRecordManagerFactory;
	}


	/**
	 *
	 * @param CustomLabelModelField $pDataModel
	 * @return int
	 * @throws RecordManagerInsertException
	 */

	public function createForField(CustomLabelModelField $pDataModel): int
	{
		$field = $pDataModel->getField()->getName();
		$customsLabelsId = $this->writeDatabaseGeneral($pDataModel->getFormId(), $field);
		foreach ($pDataModel->getValuesByLocale() as $locale => $value) {
			$this->writeDatabaseValueSingle($customsLabelsId, $value, $locale);
		}
		return $customsLabelsId;
	}

	/**
	 *
	 * Step one: write oo_fieldconfig_form_customs_labels
	 *
	 * @param int $formId
	 * @param string $field
	 * @return int
	 * @throws RecordManagerInsertException
	 *
	 */

	private function writeDatabaseGeneral(int $formId, string $field): int
	{
		$pRecordManager = $this->createRecordManagerDefaults();
		$values = [
			'form_id' => $formId,
			'fieldname' => $field,
		];
		return $pRecordManager->insertByRow([self::TABLE_CUSTOMS_LABELS => $values]);
	}


	/**
	 *
	 * step two: write to oo_fieldconfig_form_translated_labels
	 *
	 * @param int $customsLabelsId
	 * @param string $value
	 * @param string $locale
	 *
	 * @throws RecordManagerInsertException
	 *
	 */

	private function writeDatabaseValueSingle(int $customsLabelsId, string $value, string $locale = '')
	{
		$pRecordManager = $this->createRecordManagerCustomsLabels();
		$values = [
			'input_id' => $customsLabelsId,
			'locale' => $locale,
			'value' => $value,
		];
		$pRecordManager->insertByRow([self::TABLE_TRANSLATED_LABELS => $values]);
	}


	/**
	 *
	 * @return RecordManagerInsertGeneric
	 *
	 */

	private function createRecordManagerDefaults(): RecordManagerInsertGeneric
	{
		return $this->_pRecordManagerFactory->createRecordManagerInsertGeneric(self::TABLE_CUSTOMS_LABELS);
	}


	/**
	 *
	 * @return RecordManagerInsertGeneric
	 *
	 */

	private function createRecordManagerCustomsLabels(): RecordManagerInsertGeneric
	{
		return $this->_pRecordManagerFactory->createRecordManagerInsertGeneric(self::TABLE_TRANSLATED_LABELS);
	}
}
