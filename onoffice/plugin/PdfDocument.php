<?php

/**
 *
 * @url http://www.onoffice.de
 * @copyright 2003-2016, onOffice(R) Software AG
 *
 */

namespace onOffice\WPlugin;

use onOffice\SDK\onOfficeSDK;

/**
 *
 */

class PdfDocument {

	/** @var int */
	private $_estateId = null;

	/** @var int */
	private $_addressId = null;

	/** @var string */
	private $_language = null;

	/** @var string */
	private $_documentBinary = null;

	/** @var string */
	private $_mimeType = null;

	/** @var string */
	private $_template = null;


	/**
	 *
	 * @param int $estateId
	 *
	 */

	public function __construct( $estateId, $language, $template ) {
		$this->_estateId = $estateId;
		$this->_language = $language;
		$this->_template = $template;
	}


	/**
	 *
	 * @return bool
	 *
	 */

	public function fetch() {
		$parameters = array(
			'estateid' => $this->_estateId,
			'language' => $this->_language,
			'gzcompress' => true,
			'template' => $this->_template,
		);

		$pSdkWrapper = new SDKWrapper();
		$handlePdf = $pSdkWrapper->addRequest( onOfficeSDK::ACTION_ID_GET, 'pdf', $parameters );
		$pSdkWrapper->sendRequests();

		$response = $pSdkWrapper->getRequestResponse( $handlePdf );

		if ( isset( $response['data']['records'][0]['elements'] ) ) {
			$documentApiPath = $response['data']['records'][0]['elements'];
			$documentBase64 = $documentApiPath['document'];
			$documentGzip = base64_decode( $documentBase64 );
			$document = gzuncompress( $documentGzip );

			if ( $document === false ) {
				return false;
			}
			$this->_documentBinary = $document;
		} else {
			return false;
		}

		if ( isset( $response['data']['records'][0]['elements'] ) ) {
			$this->_mimeType = $response['data']['records'][0]['elements']['type'];
		}

		return true;
	}


	/**
	 *
	 * @param int $addressId
	 *
	 */

	public function setAddressId( $addressId ) {
		$this->_addressId = $addressId;
	}


	/**
	 *
	 * @return int
	 *
	 */

	public function getAddressId() {
		return $this->_addressId;
	}


	/**
	 *
	 * @param int $estateId
	 *
	 */

	public function setEstateId( $estateId ) {
		$this->_estateId = $estateId;
	}


	/**
	 *
	 * @return int
	 *
	 */

	public function getEstateId() {
		return $this->_estateId;
	}


	/**
	 *
	 * @param string $language
	 *
	 */

	public function setLanguage( $language ) {
		$this->_language = $language;
	}


	/**
	 *
	 * @return string
	 *
	 */

	public function getLanguage() {
		return $this->_language;
	}


	/**
	 *
	 * @return string binary
	 *
	 */

	public function getDocumentBinary() {
		return $this->_documentBinary;
	}


	/**
	 *
	 * @return string
	 *
	 */

	public function getMimeType() {
		return $this->_mimeType;
	}
}