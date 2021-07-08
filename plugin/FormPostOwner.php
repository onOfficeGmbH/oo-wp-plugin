<?php
/**
 *
 *    Copyright (C) 2016 onOffice Software AG
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

/**
 *
 * @url http://www.onoffice.de
 * @copyright 2003-2016, onOffice(R) Software AG
 *
 */

namespace onOffice\WPlugin;

use DI\DependencyException;
use DI\NotFoundException;
use onOffice\SDK\onOfficeSDK;
use onOffice\WPlugin\API\APIClientActionGeneric;
use onOffice\WPlugin\API\ApiClientException;
use onOffice\WPlugin\DataFormConfiguration\DataFormConfigurationOwner;
use onOffice\WPlugin\Field\Collection\FieldsCollectionConfiguratorForm;
use onOffice\WPlugin\Field\SearchcriteriaFields;
use onOffice\WPlugin\Form\FormPostConfiguration;
use onOffice\WPlugin\Form\FormPostOwnerConfiguration;
use onOffice\WPlugin\Types\FieldTypes;
use onOffice\WPlugin\Utility\__String;

/**
 *
 */

class FormPostOwner
	extends FormPost
{
	/** @var FormData */
	private $_pFormData = null;

	/** @var FormPostOwnerConfiguration */
	private $_pFormPostOwnerConfiguration = null;

	/**
	 * @param FormPostConfiguration $pFormPostConfiguration
	 * @param FormPostOwnerConfiguration $pFormPostOwnerConfiguration
	 * @param SearchcriteriaFields $pSearchcriteriaFields
	 * @param FieldsCollectionConfiguratorForm $pFieldsCollectionConfiguratorForm
	 */
	public function __construct(
		FormPostConfiguration $pFormPostConfiguration,
		FormPostOwnerConfiguration $pFormPostOwnerConfiguration,
		SearchcriteriaFields $pSearchcriteriaFields,
		FieldsCollectionConfiguratorForm $pFieldsCollectionConfiguratorForm)
	{
		$this->_pFormPostOwnerConfiguration = $pFormPostOwnerConfiguration;
		parent::__construct($pFormPostConfiguration, $pSearchcriteriaFields, $pFieldsCollectionConfiguratorForm);
	}

	/**
	 *
	 * @param FormData $pFormData
	 * @throws API\APIEmptyResultException
	 * @throws ApiClientException
	 * @throws DependencyException
	 * @throws Field\UnknownFieldException
	 * @throws NotFoundException
	 */

	protected function analyseFormContentByPrefix(FormData $pFormData)
	{
		/* @var $pDataFormConfiguration DataFormConfigurationOwner */
		$pDataFormConfiguration = $pFormData->getDataFormConfiguration();
		$this->_pFormData = $pFormData;

		$recipient = $pDataFormConfiguration->getRecipient();
		$subject = $pDataFormConfiguration->getSubject();
		$checkduplicate = $pDataFormConfiguration->getCheckDuplicateOnCreateAddress();
		$contactType = $pDataFormConfiguration->getContactType();

		$addressId = $this->_pFormPostOwnerConfiguration->getFormAddressCreator()
			->createOrCompleteAddress($pFormData, $checkduplicate, $contactType);
		$estateData = $this->getEstateData();
		$estateId = $this->createEstate($estateData);
		$this->createOwnerRelation($estateId, $addressId);

		if (null != $recipient) {
			$this->sendContactRequest($recipient, $estateId, $estateData, $subject);
		}
	}

	/**
	 *
	 * @param array $estateData
	 * @return int
	 * @throws API\APIEmptyResultException
	 * @throws ApiClientException
	 */

	private function createEstate(array $estateData): int
	{
		$requestParams = ['data' => $estateData];
		$pSDKWrapper = $this->_pFormPostOwnerConfiguration->getSDKWrapper();

		$pApiClientAction = new APIClientActionGeneric($pSDKWrapper, onOfficeSDK::ACTION_ID_CREATE,
			'estate');
		$pApiClientAction->setParameters($requestParams);
		$pApiClientAction->addRequestToQueue()->sendRequests();

		$records = $pApiClientAction->getResultRecords();
		$estateId = (int)$records[0]['id'];

		if ($estateId > 0) {
			return $estateId;
		}

		throw new ApiClientException($pApiClientAction);
	}


	/**
	 *
	 * @return array
	 *
	 */

	public function getEstateData(): array
	{
		$pFormData = $this->_pFormData;
		$pInputVariableReader = $this->_pFormPostOwnerConfiguration->getEstateListInputVariableReader();
		$configFields = $pFormData->getDataFormConfiguration()->getInputs();
		$submitFields = array_keys($pFormData->getValues());

		$estateFields = array_filter($submitFields, function($key) use ($configFields): bool {
			return onOfficeSDK::MODULE_ESTATE === $configFields[$key];
		});

		$estateData = [];

		foreach ($estateFields as $input) {
			$value = $pInputVariableReader->getFieldValue($input);

			if ($pInputVariableReader->getFieldType($input) === FieldTypes::FIELD_TYPE_MULTISELECT &&
				!is_array($value)) {
				$estateData[$input] = [$value];
			} else {
				$estateData[$input] = $value;
			}
		}

		return $estateData;
	}


	/**
	 *
	 * @param int $estateId
	 * @param int $addressId
	 * @throws ApiClientException
	 *
	 */

	private function createOwnerRelation(int $estateId, int $addressId)
	{
		$pSDKWrapper = $this->_pFormPostOwnerConfiguration->getSDKWrapper();

		$pApiClientAction = new APIClientActionGeneric
			($pSDKWrapper, onOfficeSDK::ACTION_ID_CREATE, 'relation');
		$pApiClientAction->setParameters([
			'relationtype' => onOfficeSDK::RELATION_TYPE_ESTATE_ADDRESS_OWNER,
			'parentid' => $estateId,
			'childid' => $addressId,
		]);

		$pApiClientAction->addRequestToQueue();
		$pSDKWrapper->sendRequests();

		if (!$pApiClientAction->getResultStatus()) {
			throw new ApiClientException($pApiClientAction);
		}
	}

	/**
	 *
	 * @param string $recipient
	 * @param int $estateId
	 * @param array $estateValues
	 * @param string $subject
	 * @throws API\APIEmptyResultException
	 * @throws ApiClientException
	 * @throws DependencyException
	 * @throws NotFoundException
	 */

	private function sendContactRequest(string $recipient, int $estateId, array $estateValues, $subject=null)
	{
		$addressData = $this->_pFormData->getAddressData($this->getFieldsCollection());
		$values = $this->_pFormData->getValues();
		$estateData = array_keys($estateValues);

		$requestParams = [
			'addressdata' => $addressData,
			'estateid' => $estateId,
			'message' => $values['message'] ?? null,
			'subject' => $subject,
			'referrer' => $this->_pFormPostOwnerConfiguration->getReferrer(),
			'formtype' => $this->_pFormData->getFormtype(),
		];

		if ($estateData != []) {
			$requestParams['estatedata'] = $estateData;
		}

		if ($recipient !== '') {
			$requestParams['recipient'] = $recipient;
		}

		$pSDKWrapper = $this->_pFormPostOwnerConfiguration->getSDKWrapper();
		$pApiClientAction = new APIClientActionGeneric($pSDKWrapper, onOfficeSDK::ACTION_ID_DO,
			'contactaddress');
		$pApiClientAction->setParameters($requestParams);
		$pApiClientAction->addRequestToQueue()->sendRequests();

		$resultRecords = $pApiClientAction->getResultRecords();
		$result = ($resultRecords[0]['elements']['success'] ?? '') === 'success';

		if (!$result) {
			throw new ApiClientException($pApiClientAction);
		}
	}
}