<?php

/**
 *
 *    Copyright (C) 2020 onOffice GmbH
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

declare(strict_types=1);

namespace onOffice\WPlugin\Installer;

use DI\Container;
use DI\ContainerBuilder;
use onOffice\WPlugin\Controller\RewriteRuleBuilder;
use WP_Rewrite;
use function delete_option;
use const ABSPATH;

/**
 *
 * Creates tables and sets options
 * Also removes them
 *
 */

class Installer
{
	/**
	 * Callback for plugin activation hook
	 */
	static public function install()
	{
		// If you are modifying this, please also make sure to edit the test
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		$pContainer = self::buildDI();
		$pRewriteRuleBuilder = $pContainer->get(RewriteRuleBuilder::class);
		$pRewriteRuleBuilder->addCustomRewriteTags();
		$pRewriteRuleBuilder->addStaticRewriteRules();
		$pRewriteRuleBuilder->addDynamicRewriteRules();
		self::flushRules();
	}

	/**
	 * @global WP_Rewrite $wp_rewrite
	 */
	static private function flushRules()
	{
		global $wp_rewrite;
		$wp_rewrite->flush_rules(false);
	}

	/**
	 *
	 */
	static public function deactivate()
	{
		self::flushRules();
	}

	/**
	 * @return Container
	 * @throws \Exception
	 */
	private static function buildDI():Container
	{
		$pDIBuilder = new ContainerBuilder();
		$pDIBuilder->addDefinitions(ONOFFICE_DI_CONFIG_PATH);
		$pDI = $pDIBuilder->build();

		return $pDI;
	}

	/**
	 * @throws \DI\DependencyException
	 * @throws \DI\NotFoundException
	 */
	static public function deinstall()
	{
		$pDI = self::buildDI();
		$pDbChanges = $pDI->get(DatabaseChangesInterface::class);
		$pDbChanges->deinstall();

		delete_option('onoffice-default-view');
		delete_option('onoffice-favorization-enableFav');
		delete_option('onoffice-favorization-favButtonLabelFav');
		delete_option('onoffice-maps-mapprovider');
		delete_option('onoffice-settings-apisecret');
		delete_option('onoffice-settings-apikey');
		delete_option('onoffice-detail-view-showTitleUrl');

		self::flushRules();
	}
}
