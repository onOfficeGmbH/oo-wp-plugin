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

namespace onOffice\tests;

use onOffice\WPlugin\Installer\Installer;
use WP_UnitTestCase;

class TestClassInstaller
	extends WP_UnitTestCase
{
	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */

	public function testInstall()
	{
		global $wp_rewrite;
		$wp_rewrite->permalink_structure = '%postname%';
		$wp_rewrite->rewritecode = [];
		$wp_rewrite->rewritereplace = [];
		$wp_rewrite->queryreplace = [];
		$wp_rewrite->extra_rules_top = [];
		$this->assertArrayNotHasKey('^onoffice-estate-types.json$', $wp_rewrite->rewrite_rules());
		$this->assertArrayNotHasKey('^document-pdf/([^\/]+)/([0-9]+)/?$', $wp_rewrite->rewrite_rules());
		Installer::install();
		$this->assertNotEmpty($wp_rewrite->rewritecode);
		$this->assertNotEmpty($wp_rewrite->rewritereplace);
		$this->assertNotEmpty($wp_rewrite->queryreplace);
		$this->assertNotEmpty($wp_rewrite->extra_rules_top);
		$this->assertNotEmpty($wp_rewrite->rewrite_rules());
		$this->assertArrayHasKey('^onoffice-estate-types.json$', $wp_rewrite->rewrite_rules());
		$this->assertArrayHasKey('^document-pdf/([^\/]+)/([0-9]+)/?$', $wp_rewrite->rewrite_rules());
	}

	/**
	 * @depends testInstall
	 * @preserveGlobalState disabled
	 */
	public function testDeactivate()
	{
		global $wp_rewrite;
		$wp_rewrite->permalink_structure = '%postname%';
		Installer::install();

		// make rewrite rules outdated
		$wp_rewrite->extra_rules_top = [];
		$wp_rewrite->extra_rules = [];

		$rewriteRulesBefore = get_option('rewrite_rules');

		// should refresh rewrite rules
		Installer::deactivate();
		$rewriteRulesAfter = get_option('rewrite_rules');
		$this->assertNotSame($rewriteRulesBefore, $rewriteRulesAfter);
	}

	/**
	 * @throws \DI\DependencyException
	 * @throws \DI\NotFoundException
	 */
	public function testDeinstall()
	{
		Installer::deinstall();
		$this->assertFalse(get_option('onoffice-default-view'));
		$this->assertFalse(get_option('onoffice-favorization-enableFav'));
		$this->assertFalse(get_option('onoffice-favorization-favButtonLabelFav'));
		$this->assertFalse(get_option('onoffice-pagination-paginationbyonoffice'));
		$this->assertFalse(get_option('onoffice-settings-apisecret'));
		$this->assertFalse(get_option('onoffice-settings-apikey'));
	}
}