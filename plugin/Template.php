<?php

/**
 *
 *    Copyright (C) 2019 onOffice GmbH
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

use DI\Container;
use DI\ContainerBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use onOffice\WPlugin\Controller\EstateListBase;
use onOffice\WPlugin\Template\TemplateCallbackBuilder;
use const WP_PLUGIN_DIR;

/**
 *
 */

class Template
{
	/** */
	const KEY_ESTATELIST = 'estatelist';

	/** */
	const KEY_FORM = 'form';

	/** */
	const KEY_ADDRESSLIST = 'addresslist';

	/** @var string */
	private $_templateName = '';

	/** @var EstateListBase|null */
	private $_pEstateList = null;

	/** @var Form|null */
	private $_pForm = null;

	/** @var AddressList|null */
	private $_pAddressList = null;

	/**
	 * @return string
	 * @throws DependencyException
	 * @throws NotFoundException
	 * @throws Exception
	 */
	public function render(): string
	{
		$templateData = [
			self::KEY_FORM => $this->_pForm,
			self::KEY_ESTATELIST => $this->_pEstateList,
			self::KEY_ADDRESSLIST => $this->_pAddressList,
		];
		$filename = $this->buildFilePath();
		$result = '';

		if (file_exists($filename)) {
			$pDIContainerBuilder = new ContainerBuilder;
			$pDIContainerBuilder->addDefinitions(ONOFFICE_DI_CONFIG_PATH);
			$pContainer = $pDIContainerBuilder->build();
			$result = self::getIncludeContents($templateData, $filename, $pContainer);
		}

		return $result;
	}


	/** @return string */
	protected function getTemplateName(): string
		{ return $this->_templateName; }

	/**
	 *
	 * Method that provides important variables to template
	 * Must not expose $this
	 *
	 * @param array $templateData
	 * @param string $templatePath
	 * @param Container $pContainer
	 * @return string
	 * @throws DependencyException
	 * @throws NotFoundException
	 */
	private static function getIncludeContents(array $templateData, $templatePath, Container $pContainer): string
	{
		// vars which might be used in template
		$pEstates = $templateData[self::KEY_ESTATELIST];
		$pForm = $templateData[self::KEY_FORM];
		$pAddressList = $templateData[self::KEY_ADDRESSLIST];
		/** @var TemplateCallbackBuilder $pTemplateCallback */
		$pTemplateCallback = $pContainer->get(TemplateCallbackBuilder::class);
		$generateSortDropDown = $pTemplateCallback->buildCallbackListSortDropDown($pEstates);
		$getListName = $pTemplateCallback->buildCallbackEstateListName($pEstates);
		unset($templateData, $pTemplateCallback, $pContainer);

		ob_start();
		include $templatePath;
		return ob_get_clean();
	}

	/**
	 * @return string
	 */
	protected function buildFilePath(): string
	{
		if(strpos($this->_templateName, 'onoffice-theme') !== false) {
			return get_template_directory().'/'.$this->_templateName;
		}
		return WP_PLUGIN_DIR.'/'.$this->_templateName;
	}


	/**
	 * @param string $templateName
	 * @return self
	 */
	public function withTemplateName(string $templateName): self
	{
		$pNewTemplate = clone $this;
		$pNewTemplate->_templateName = $templateName;
		return $pNewTemplate;
	}

	/**
	 * @param Form $pForm
	 * @return $this
	 */
	public function withForm(Form $pForm): self
	{
		$pClonedThis = clone $this;
		$pClonedThis->_pForm = $pForm;
		return $pClonedThis;
	}

	/**
	 * @param AddressList $pAddressList
	 * @return $this
	 */
	public function withAddressList(AddressList $pAddressList): self
	{
		$pClonedThis = clone $this;
		$pClonedThis->_pAddressList = $pAddressList;
		return $pClonedThis;
	}

	/**
	 * @param EstateListBase $pEstateList
	 * @return $this
	 */
	public function withEstateList(EstateListBase $pEstateList): self
	{
		$pClonedThis = clone $this;
		$pClonedThis->_pEstateList = $pEstateList;
		return $pClonedThis;
	}
}