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

declare (strict_types=1);

namespace onOffice\tests;

use DI\Container;
use DI\ContainerBuilder;
use Exception;
use onOffice\WPlugin\AddressList;
use onOffice\WPlugin\Controller\ContentFilter\ContentFilterShortCodeAddress;
use onOffice\WPlugin\DataView\DataListViewAddress;
use onOffice\WPlugin\DataView\DataListViewFactoryAddress;
use onOffice\WPlugin\Factory\AddressListFactory;
use onOffice\WPlugin\Filter\SearchParameters\SearchParametersModel;
use onOffice\WPlugin\Filter\SearchParameters\SearchParametersModelBuilder;
use onOffice\WPlugin\Template;
use onOffice\WPlugin\Utility\Logger;
use onOffice\WPlugin\WP\WPQueryWrapper;
use WP_Query;
use WP_UnitTestCase;

/**
 *
 * test class for ContentFilterShortCodeAddress
 *
 */

class TestClassContentFilterShortCodeAddress
	extends WP_UnitTestCase
{
	/** @var Container */
	private $_pContainer;

	/**
	 * @before
	 */
	public function prepare()
	{
		add_option('onoffice-pagination-paginationbyonoffice', 1);
		$pContainerBuilder = new ContainerBuilder;
		$pContainerBuilder->addDefinitions(ONOFFICE_DI_CONFIG_PATH);
		$this->_pContainer = $pContainerBuilder->build();

		$pTemplateMock = $this->getMockBuilder(Template::class)
			->setMethods(['render', 'withAddressList', 'withTemplateName'])
			->getMock();
		$pTemplateMock->method('withTemplateName')->will($this->returnSelf()); // should be a clone!

		$pLogger = $this->getMockBuilder(Logger::class)
			->setMethods(['logErrorAndDisplayMessage'])
			->getMock();

		$pDataListViewAddress = new DataListViewAddress(1, 'test');
		$pDataListViewAddress->setTemplate('adressList-01');
		$pDataListViewAddress->setFilterableFields(['Ort']);

		$pMockDataListViewFactoryAddress = $this->getMockBuilder(DataListViewFactoryAddress::class)
			->setMethods(['getListViewByName'])
			->disableOriginalConstructor()
			->getMock();
		$pMockDataListViewFactoryAddress->method('getListViewByName')->will
			($this->returnValue($pDataListViewAddress));

		$pWPQueryWrapperMock = $this->getMockBuilder(WPQueryWrapper::class)
				->getMock();
		$pWPQueryMock = $this->getMockBuilder(WP_Query::class)->getMock();
		$pWPQueryMock->method('get')
			->with('paged', 1)->will($this->returnValue(2));
		$pWPQueryWrapperMock->method('getWPQuery')->will($this->returnValue($pWPQueryMock));

		$pSearchParametersModelBuilder = $this->getMockBuilder(SearchParametersModelBuilder::class)
			->setMethods(['build'])
			->disableOriginalConstructor()
			->getMock();

		$pSearchParametersModel = new SearchParametersModel();
		$pSearchParametersModelBuilder->method('build')->with($this->anything())->willReturn($pSearchParametersModel);

		$pAddressListFactory = $this->getMockBuilder(AddressListFactory::class)
			->setMethods(['create'])
			->disableOriginalConstructor()
			->getMock();

		$pAddressListFactory->method('create')
			->willReturn($this->getMockBuilder(AddressList::class)
			->getMock());
		$this->_pContainer->set(Template::class, $pTemplateMock);
		$this->_pContainer->set(Logger::class, $pLogger);
		$this->_pContainer->set(DataListViewFactoryAddress::class, $pMockDataListViewFactoryAddress);
		$this->_pContainer->set(WPQueryWrapper::class, $pWPQueryWrapperMock);
		$this->_pContainer->set(SearchParametersModelBuilder::class, $pSearchParametersModelBuilder);
		$this->_pContainer->set(AddressListFactory::class, $pAddressListFactory);

	}


	/**
	 *
	 */

	public function testReplaceShortCodes()
	{
		$pTemplateMock = $this->_pContainer->get(Template::class);
		$pTemplateMock->method('withTemplateName')->will($this->returnSelf()); // should be a clone!
		$pTemplateMock->method('render')->will($this->returnValue('I am the returned text.'));

		$pTemplateMock->expects($this->once())->method('withAddressList')
			->with($this->anything())
			->will($this->returnSelf());
		$pTemplateMock->expects($this->once())->method('withTemplateName')->with('adressList-01')
			->will($this->returnSelf());

		$pConfigFilterShortCodeAddress = $this->_pContainer->get(ContentFilterShortCodeAddress::class);
		$result = $pConfigFilterShortCodeAddress->replaceShortCodes(['view' => 'adressList-01']);
		$this->assertEquals('I am the returned text.', $result);
	}


	/**
	 *
	 */

	public function testReplaceShortCodesException()
	{
		$pTemplateMock = $this->_pContainer->get(Template::class);
		$pTemplateMock->expects($this->once())
			->method('withAddressList')
			->will($this->returnSelf());
		$pException = new Exception(__CLASS__);
		$pTemplateMock->expects($this->once())->method('render')->will($this->throwException($pException));
		$this->_pContainer->get(Logger::class)
			->expects($this->once())->method('logErrorAndDisplayMessage')
			->with($pException)->will($this->returnValue('Exception caught'));

		$pConfigFilterShortCodeAddress = $this->_pContainer->get(ContentFilterShortCodeAddress::class);
		$result = $pConfigFilterShortCodeAddress->replaceShortCodes(['view' => 'testException']);
		$this->assertEquals('Exception caught', $result);
	}


	/**
	 *
	 */

	public function testGetTag()
	{
		$pConfigFilterShortCodeAddress = $this->_pContainer->get(ContentFilterShortCodeAddress::class);
		$this->assertEquals('oo_address', $pConfigFilterShortCodeAddress->getTag());
	}
}