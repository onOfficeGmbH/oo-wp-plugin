<?php

/**
 *
 *    Copyright (C) 2016-2019 onOffice GmbH
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

use onOffice\SDK\onOfficeSDK;
use onOffice\WPlugin\API\APIClientActionGeneric;
use onOffice\WPlugin\API\ApiClientException;
use onOffice\WPlugin\DataFormConfiguration\DataFormConfiguration;
use onOffice\WPlugin\Form\FormPostConfiguration;
use onOffice\WPlugin\Form\FormPostContactConfiguration;
use onOffice\WPlugin\FormData;
use onOffice\WPlugin\FormPost;


/**
 *
 * Interest/Contact form
 *
 *  - send "Id" value if in estate-context
 *
 */

class FormPostContact
	extends FormPost
{
	/** @var FormPostContactConfiguration */
	private $_pFormPostContactConfiguration = null;


	/**
	 *
	 * @param FormPostConfiguration $pFormPostConfiguration
	 * @param FormPostContactConfiguration $pFormPostContactConfiguration
	 *
	 */

	public function __construct(FormPostConfiguration $pFormPostConfiguration,
		FormPostContactConfiguration $pFormPostContactConfiguration)
	{
		$this->_pFormPostContactConfiguration = $pFormPostContactConfiguration;

		parent::__construct($pFormPostConfiguration);
	}


	/**
	 *
	 * @param FormData $pFormData
	 *
	 */

	protected function analyseFormContentByPrefix(FormData $pFormData)
	{
		$pFormConfig = $pFormData->getDataFormConfiguration();
		$recipient = $pFormConfig->getRecipient();
		$subject = $pFormConfig->getSubject();

		if ($pFormConfig->getCreateAddress()) {
			$this->createAddress($pFormData);
		}

		$this->sendContactRequest($pFormData, $recipient ?? '', $subject);
	}


	/**
	 *
	 * @param DataFormConfiguration $pFormConfig
	 * @return array
	 *
	 */

	protected function getAllowedPostVars(DataFormConfiguration $pFormConfig): array
	{
		return ['Id' => onOfficeSDK::MODULE_ESTATE] + parent::getAllowedPostVars($pFormConfig);
	}


	/**
	 *
	 * @param FormData $pFormData
	 * @return void
	 *
	 */

	private function createAddress(FormData $pFormData)
	{
		$pFormConfig = $pFormData->getDataFormConfiguration();
		$checkDuplicate = $pFormConfig->getCheckDuplicateOnCreateAddress();
		$addressId = $this->_pFormPostContactConfiguration->getFormAddressCreator()
			->createOrCompleteAddress($pFormData, $checkDuplicate);

		if (!$this->_pFormPostContactConfiguration->getNewsletterAccepted()) {
			// No subscription for newsletter, which is ok
			return;
		}

		$pSDKWrapper = $this->_pFormPostContactConfiguration->getSDKWrapper();
		$pAPIClientAction = new APIClientActionGeneric
			($pSDKWrapper, onOfficeSDK::ACTION_ID_DO, 'registerNewsletter');
		$pAPIClientAction->setParameters(['register' => true]);
		$pAPIClientAction->setResourceId($addressId);
		$pAPIClientAction->addRequestToQueue()->sendRequests();

		if (!$pAPIClientAction->getResultStatus()) {
			throw new ApiClientException($pAPIClientAction);
		}
	}


	/**
	 *
	 * @param FormData $pFormData
	 * @param string $recipient
	 * @param string $subject
	 * @throws ApiClientException
	 *
	 */

	private function sendContactRequest(FormData $pFormData, string $recipient = '', $subject = null)
	{
		$values = $pFormData->getValues();
		$pWPQuery = $this->_pFormPostContactConfiguration->getWPQueryWrapper()->getWPQuery();
		$requestParams = [
			'addressdata' => $pFormData->getAddressData(),
			'estateid' => $values['Id'] ?? $pWPQuery->get('estate_id', null),
			'message' => $values['message'] ?? null,
			'subject' => $subject,
			'referrer' => $this->_pFormPostContactConfiguration->getReferrer(),
			'formtype' => $pFormData->getFormtype(),
		];

		if ($recipient !== '') {
			$requestParams['recipient'] = $recipient;
		}

		$pSDKWrapper = $this->_pFormPostContactConfiguration->getSDKWrapper();
		$pAPIClientAction = new APIClientActionGeneric
			($pSDKWrapper, onOfficeSDK::ACTION_ID_DO, 'contactaddress');
		$pAPIClientAction->setParameters($requestParams);
		$pAPIClientAction->addRequestToQueue()->sendRequests();

		if (!$pAPIClientAction->getResultStatus()) {
			throw new ApiClientException($pAPIClientAction);
		}
	}
}