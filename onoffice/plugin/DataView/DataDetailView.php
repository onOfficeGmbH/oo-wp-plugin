<?php

/**
 *
 *    Copyright (C) 2017 onOffice GmbH
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

namespace onOffice\WPlugin\DataView;

/**
 *
 * @url http://www.onoffice.de
 * @copyright 2003-2017, onOffice(R) GmbH
 *
 * DO NOT MOVE OR RENAME - NAME AND/OR NAMESPACE MAY BE USED IN SERIALIZED DATA
 *
 */

class DataDetailView
{
	/** */
	const PICTURES = 'pictures';

	/** */
	const FIELDS = 'fields';

	/** @var string[] */
	private $_fields = array();

	/** @var string[] */
	private $_pictureTypes = array();

	/** @var string */
	private $_template = '';

	/** @var string */
	private $_expose = null;


	/** @return array */
	public function getFields()
		{ return $this->_fields; }

	/** @return array */
	public function getPictureTypes()
		{ return $this->_pictureTypes; }

	/** @return string */
	public function getTemplate()
		{ return $this->_template; }

	/** @return string */
	public function getExpose()
		{ return $this->_expose; }

	/** @param array $fields */
	public function setFields(array $fields)
		{ $this->_fields = $fields; }

	/** @param array $pictureTypes */
	public function setPictureTypes(array $pictureTypes)
		{ $this->_pictureTypes = $pictureTypes; }

	/** @param string $template */
	public function setTemplate($template)
		{ $this->_template = $template; }

	/** @param string $expose */
	public function setExpose($expose)
		{ $this->_expose = $expose; }
}
