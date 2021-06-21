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


namespace onOffice\WPlugin;

use DI\DependencyException;
use DI\NotFoundException;
use onOffice\SDK\onOfficeSDK;
use onOffice\WPlugin\API\APIClientActionGeneric;
use onOffice\WPlugin\API\ApiClientException;
use onOffice\WPlugin\DataFormConfiguration\DataFormConfiguration;
use onOffice\WPlugin\DataFormConfiguration\DataFormConfigurationInterest;
use onOffice\WPlugin\Field\Collection\FieldsCollectionConfiguratorForm;
use onOffice\WPlugin\Field\SearchcriteriaFields;
use onOffice\WPlugin\Form\FormPostConfiguration;
use onOffice\WPlugin\Form\FormPostInterestConfiguration;
use function sanitize_email;
use function sanitize_text_field;

/**
 *
 * Applicant form. A person registers itself and leaves his/her search criteria
 *
 */

class FormPostInterest
	extends FormPost
{
	/** @var FormPostInterestConfiguration */
	private $_pFormPostInterestConfiguration = null;

	/**
	 *
	 * @param FormPostConfiguration $pFormPostConfiguration
	 * @param FormPostInterestConfiguration $pFormPostInterestConfiguration
	 * @param SearchcriteriaFields $pSearchcriteriaFields
	 * @param FieldsCollectionConfiguratorForm $pFieldsCollectionConfiguratorForm
	 */

	public function __construct(
		FormPostConfiguration $pFormPostConfiguration,
		FormPostInterestConfiguration $pFormPostInterestConfiguration,
		SearchcriteriaFields $pSearchcriteriaFields,
		FieldsCollectionConfiguratorForm $pFieldsCollectionConfiguratorForm)
	{
		parent::__construct($pFormPostConfiguration, $pSearchcriteriaFields, $pFieldsCollectionConfiguratorForm);
		$this->_pFormPostInterestConfiguration = $pFormPostInterestConfiguration;
	}

	/**
	 * @param FormData $pFormData
	 * @throws ApiClientException
	 * @throws DependencyException
	 * @throws Field\UnknownFieldException
	 * @throws NotFoundException
	 */
	protected function analyseFormContentByPrefix(FormData $pFormData)
	{
		/* @var $pFormConfiguration DataFormConfigurationInterest */
		$pFormConfiguration = $pFormData->getDataFormConfiguration();
		$recipient = $pFormConfiguration->getRecipient();
		$subject = $pFormConfiguration->getSubject();
		$checkduplicate = $pFormConfiguration->getCheckDuplicateOnCreateAddress();

		$addressId = $this->_pFormPostInterestConfiguration->getFormAddressCreator()
			->createOrCompleteAddress($pFormData, $checkduplicate);
		$this->createSearchcriteria($pFormData, $addressId);

		if ($recipient != null) {
			$this->sendEmail($pFormData, $recipient, $subject);
		}
	}

	/**
	 *
	 * @param DataFormConfiguration $pFormConfig
	 * @return array
	 * @throws DependencyException
	 * @throws NotFoundException
	 */

	protected function getAllowedPostVars(DataFormConfiguration $pFormConfig): array
	{
		$formFields = parent::getAllowedPostVars($pFormConfig);
		$postvars = $this->_pFormPostInterestConfiguration->getSearchcriteriaFields()
			->getFormFieldsWithRangeFields($formFields);
		return $postvars;
	}

	/**
	 * @param FormData $pFormData
	 * @param string $recipient
	 * @param string $subject
	 * @throws ApiClientException
	 * @throws DependencyException
	 * @throws NotFoundException
	 * @throws Field\UnknownFieldException
	 */
	private function sendEmail(FormData $pFormData, string $recipient, $subject = null)
	{
		$values = $pFormData->getValues();
		$filledSearchCriteriaData = $this->_pFormPostInterestConfiguration->getSearchcriteriaFields()
			->getFieldLabelsOfInputs($pFormData->getSearchcriteriaData());
		$searchCriterias = $this->createStringFromInputData($filledSearchCriteriaData);
		$message = $values['message'] ?? '';
		$message .= "\nSuchkriterien des Interessenten:\n".
					"$searchCriterias";
		$requestParams = [
			'addressdata' => $pFormData->getAddressData($this->getFieldsCollection()),
			'message' => $message,
			'subject' => sanitize_text_field($subject),
			'formtype' => $pFormData->getFormtype(),
			'referrer' => filter_input(INPUT_SERVER, 'REQUEST_URI') ?? '',
			'recipient' => $recipient
		];

		if ($recipient !== '') {
			$requestParams['recipient'] = $recipient;
		}
		$pSDKWrapper = $this->_pFormPostInterestConfiguration->getSDKWrapper();
		$pAPIClientAction = new APIClientActionGeneric
		($pSDKWrapper, onOfficeSDK::ACTION_ID_DO, 'contactaddress');
		$pAPIClientAction->setParameters($requestParams);
		$pAPIClientAction->addRequestToQueue()->sendRequests();
		if (!$pAPIClientAction->getResultStatus()) {
			throw new ApiClientException($pAPIClientAction);
		}
	}

	/**
	 * @param array $inputData
	 * @return string
	 */
	private function createStringFromInputData(array $inputData): string
	{
		$data = [];

		foreach ($inputData as $key => $value) {
			$data []= $key.': '.$value;
		}

		return implode("\n", $data);
	}

	/**
	 * @param FormData $pFormData
	 * @param int $addressId
	 * @throws ApiClientException
	 * @throws DependencyException
	 * @throws NotFoundException
	 */
	private function createSearchcriteria(FormData $pFormData, int $addressId)
	{
		$requestParams = [
			'data' => $pFormData->getSearchcriteriaData(),
			'addressid' => $addressId,
		];

		$pSDKWrapper = $this->_pFormPostInterestConfiguration->getSDKWrapper();
		$pApiClientAction = new APIClientActionGeneric
			($pSDKWrapper, onOfficeSDK::ACTION_ID_CREATE, 'searchcriteria');

		$pApiClientAction->setParameters($requestParams);
		$pApiClientAction->addRequestToQueue();
		$pSDKWrapper->sendRequests();

		if (!$pApiClientAction->getResultStatus()) {
			throw new ApiClientException($pApiClientAction);
		}
	}
}