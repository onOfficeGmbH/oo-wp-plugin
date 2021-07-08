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
		$contactType = $pFormConfiguration->getContactType();
		$addressId = $this->_pFormPostInterestConfiguration->getFormAddressCreator()
			->createOrCompleteAddress($pFormData, $checkduplicate, $contactType);
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
		$filledAddressData = $this->_pFormPostInterestConfiguration->getFormAddressCreator()
			->getAddressDataForEmail($pFormData);

		$filledSearchcriteriaData = $this->_pFormPostInterestConfiguration
			->getSearchcriteriaFields()
			->getFieldLabelsOfInputs($pFormData->getSearchcriteriaData());


		$addressData = $pFormData->getAddressData($this->getFieldsCollection());

		$name = $addressData['Name'] ?? null;
		$firstName = $addressData['Vorname'] ?? null;
		$mailInteressent = $addressData['Email'] ?? null;

		$body = 'Sehr geehrte Damen und Herren,'."\n\n"
				.'ein neuer Interessent hat sich über das Kontaktformular auf Ihrer Webseite '
				.'eingetragen. Die Adresse ('.sanitize_text_field($firstName).' '.sanitize_text_field($name).') wurde bereits in Ihrem System '
				.'eingetragen.'."\n\n"
				."Kontaktdaten des Interessenten:"."\n"
				.$this->createStringFromInputData($filledAddressData)."\n\n"
				."Suchkriterien des Interessenten:"."\n"
				.$this->createStringFromInputData($filledSearchcriteriaData)."\n\n"
				.'Herzliche Grüße'."\n"
				.'Ihr onOffice Team';

		$requestParams = [
			'anonymousEmailidentity' => true,
			'body' => $body,
			'subject' => sanitize_text_field($subject),
			'replyto' => sanitize_email($mailInteressent),
			'receiver' => [sanitize_email($recipient)],
			'X-Original-From' => sanitize_email($mailInteressent),
			'saveToAgentsLog' => false,
		];

		$pSDKWrapper = $this->_pFormPostInterestConfiguration->getSDKWrapper();
		$pApiClientAction = new APIClientActionGeneric
			($pSDKWrapper, onOfficeSDK::ACTION_ID_DO, 'sendmail');
		$pApiClientAction->setParameters($requestParams);
		$pApiClientAction->addRequestToQueue()->sendRequests();

		if (!$pApiClientAction->getResultStatus()) {
			throw new ApiClientException($pApiClientAction);
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