<?php

/**
 *
 *    Copyright (C) 2017-2019 onOffice GmbH
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

/*
Plugin Name: onOffice for WP-Websites (dev)
Plugin URI: https://wpplugindoc.onoffice.de
Author: onOffice GmbH
Author URI: https://en.onoffice.com/
Description: Your connection to onOffice: This plugin enables you to have quick access to estates and forms – no additional sync with the software is needed. Consult support@onoffice.de for source code.
Version: 2.0
License: AGPL 3+
License URI: https://www.gnu.org/licenses/agpl-3.0
Text Domain: onoffice-for-wp-websites
Domain Path: /languages
*/

defined( 'ABSPATH' ) or die();

require 'vendor/autoload.php';

define('ONOFFICE_PLUGIN_DIR', __DIR__);

use DI\ContainerBuilder;
use onOffice\WPlugin\Cache\CachedOutput;
use onOffice\WPlugin\Cache\CacheHandler;
use onOffice\WPlugin\Controller\AdminViewController;
use onOffice\WPlugin\Controller\ContentFilter\ContentFilterShortCodeRegistrator;
use onOffice\WPlugin\Controller\DetailViewPostSaveController;
use onOffice\WPlugin\Controller\EstateViewDocumentTitleBuilder;
use onOffice\WPlugin\Controller\RewriteRuleBuilder;
use onOffice\WPlugin\Field\EstateKindTypeReader;
use onOffice\WPlugin\Form\CaptchaDataChecker;
use onOffice\WPlugin\Form\Preview\FormPreviewApplicantSearch;
use onOffice\WPlugin\Form\Preview\FormPreviewEstate;
use onOffice\WPlugin\FormPostHandler;
use onOffice\WPlugin\Installer\DatabaseChangesInterface;
use onOffice\WPlugin\Installer\Installer;
use onOffice\WPlugin\PDF\PdfDocumentModel;
use onOffice\WPlugin\PDF\PdfDocumentModelValidationException;
use onOffice\WPlugin\PDF\PdfDownload;
use onOffice\WPlugin\PDF\PdfDownloadException;
use onOffice\WPlugin\Record\EstateIdRequestGuard;
use onOffice\WPlugin\ScriptLoader\ScriptLoaderRegistrator;
use onOffice\WPlugin\Controller\EstateDetailUrl;
use onOffice\WPlugin\Utility\__String;
use onOffice\WPlugin\WP\WPQueryWrapper;

define('ONOFFICE_DI_CONFIG_PATH', implode(DIRECTORY_SEPARATOR, [ONOFFICE_PLUGIN_DIR, 'config', 'di-config.php']));

$pDIBuilder = new ContainerBuilder();
$pDIBuilder->addDefinitions(ONOFFICE_DI_CONFIG_PATH);
$pDI = $pDIBuilder->build();

$pAdminViewController = new AdminViewController();
$pDetailViewPostSaveController = $pDI->get(DetailViewPostSaveController::class);
$pDI->get(ScriptLoaderRegistrator::class)->generate();

add_action('plugins_loaded', function() use ($pDI) {
	$pDI->get(DatabaseChangesInterface::class)->install();
});

add_action('init', function() use ($pDI) {
	$pRewriteRuleBuilder = $pDI->get(RewriteRuleBuilder::class);
	$pRewriteRuleBuilder->addCustomRewriteTags();
	$pRewriteRuleBuilder->addStaticRewriteRules();
	$pRewriteRuleBuilder->addDynamicRewriteRules();
});

// This hook [wp] is one effective place to perform any high-level filtering or validation,
// following queries, but before WordPress does any routing, processing, or handling.
// https://codex.wordpress.org/Plugin_API/Action_Reference/wp
add_action('wp', [FormPostHandler::class, 'initialCheck']);

add_action('admin_menu', [$pAdminViewController, 'register_menu']);
add_action('admin_enqueue_scripts', [$pAdminViewController, 'enqueue_ajax']);
add_action('admin_enqueue_scripts', [$pAdminViewController, 'enqueue_css']);
add_action('admin_enqueue_scripts', [$pAdminViewController, 'enqueueExtraJs']);
add_action('wp_enqueue_scripts', [CaptchaDataChecker::class, 'registerScripts']);
add_action('save_post', [$pDetailViewPostSaveController, 'onSavePost']);
add_action('wp_trash_post', [$pDetailViewPostSaveController, 'onMoveTrash']);
add_action('oo_cache_cleanup', function() use ($pDI) {
	$pDI->get(CacheHandler::class)->clean();
});

add_action('init', [$pAdminViewController, 'onInit']);
add_action('init', function() use ($pAdminViewController) {
	$pAdminViewController->disableHideMetaboxes();
}, 11);
add_action('admin_init', [$pAdminViewController, 'add_ajax_actions']);
add_action('admin_init', [CaptchaDataChecker::class, 'addHook']);

add_action('plugins_loaded', function() {
	load_plugin_textdomain('onoffice-for-wp-websites', false, basename(ONOFFICE_PLUGIN_DIR) . '/languages');
	// Check 'onoffice-personalized' Folder exists
	$onofficePersonalizedFolderLanguages = plugin_dir_path(__DIR__) . 'onoffice-personalized/languages';
	$onofficePersonalizedFolder = plugin_dir_path(__DIR__) . 'onoffice-personalized';
	// Check 'onoffice-theme' Folder exists
	$onofficeThemeFolderLanguages = get_template_directory() . '/onoffice-theme/languages';
	$onofficeThemeFolder = get_template_directory() . '/onoffice-theme';

	if (is_dir($onofficeThemeFolderLanguages)) {
		load_textdomain('onoffice', $onofficeThemeFolder.'/languages/onoffice-'.get_locale().'.mo');
		load_textdomain('onoffice-for-wp-websites', $onofficeThemeFolder.'/languages/onoffice-for-wp-websites-'.get_locale().'.mo');
	} elseif (is_dir($onofficePersonalizedFolderLanguages)) {
		load_plugin_textdomain('onoffice', false, basename($onofficePersonalizedFolder) . '/languages');
	} else {
		load_plugin_textdomain('onoffice', false, basename(ONOFFICE_PLUGIN_DIR) . '/languages');
	}
});

// "Settings" link in plugins list
add_filter('plugin_action_links_'.plugin_basename(__FILE__), [$pAdminViewController, 'pluginSettingsLink']);

$pDI->get(ContentFilterShortCodeRegistrator::class)->register();

add_filter('document_title_parts', function($title) use ($pDI) {
	return $pDI->get(EstateViewDocumentTitleBuilder::class)->buildDocumentTitle($title);
}, 10, 2);

add_filter('wpml_ls_language_url', function($url) use ($pDI){
	$pWPQueryWrapper = $pDI->get(WPQueryWrapper::class);
	$estateId = (int) $pWPQueryWrapper->getWPQuery()->get('estate_id', 0);
	return $pDI->get(EstateDetailUrl::class)->createEstateDetailLink($url, $estateId);
}, 10, 2);

register_activation_hook(__FILE__, [Installer::class, 'install']);
register_deactivation_hook(__FILE__, [Installer::class, 'deactivate']);
register_uninstall_hook(__FILE__, [Installer::class, 'deinstall']);

if (!wp_next_scheduled('oo_cache_cleanup')) {
	wp_schedule_event(time(), 'hourly', 'oo_cache_cleanup');
}

// Gets triggered before we know if it has to be updated at all, so that no value has to be changed
add_action('pre_update_option', function($value, $option) use ($pDI) {
	if (__String::getNew($option)->startsWith('onoffice')) {
		$pDI->get(CacheHandler::class)->clear();
	}
	return $value;
}, 10, 2);

add_filter('query_vars', function(array $query_vars): array {
    $query_vars []= 'onoffice_estate_type_json';
    $query_vars []= 'onoffice_applicant_search_preview';
    $query_vars []= 'onoffice_estate_preview';
    $query_vars []= 'document_pdf';
    $query_vars []= 'preview_name';
    $query_vars []= 'nonce';
    return $query_vars;
});

add_action('parse_request', function(WP $pWP) use ($pDI) {
	if (isset($pWP->query_vars['document_pdf'])) {
		try {
			$pPdfDocumentModel = new PdfDocumentModel($pWP->query_vars['estate_id'] ?? 0, $pWP->query_vars['view'] ?? '');
			/* @var $pPdfDownload PdfDownload */
			$pPdfDownload = $pDI->get(PdfDownload::class);
			$pPdfDownload->download($pPdfDocumentModel);
		} catch (PdfDocumentModelValidationException $pEx) {
			$pWP->handle_404();
			include(get_query_template('404'));
		} catch (PdfDownloadException $pEx) {
			$pWP->handle_404();
			include(get_query_template('404'));
		}
		die();
	}
});

add_action('parse_request', function(WP $pWP) use ($pDI) {
	$estateId = $pWP->query_vars['estate_id'] ?? '';
	/** @var EstateIdRequestGuard $pEstateIdGuard */
	$pEstateIdGuard = $pDI->get(EstateIdRequestGuard::class);

	if ($estateId !== '') {
		$estateId = (int)$estateId;
		if ($estateId === 0 || !$pEstateIdGuard->isValid($estateId)) {
			$pWP->handle_404();
			include(get_query_template('404'));
			die();
		}
	}
});

add_action('parse_request', static function(WP $pWP) use ($pDI) {
	if (isset($pWP->query_vars['onoffice_estate_type_json'])) {
		$content = wp_json_encode($pDI->get(EstateKindTypeReader::class)->read());
		/** @var CachedOutput $pCachedOutput */
		$pCachedOutput = $pDI->get(CachedOutput::class);
		header('Content-Type: application/json; charset='.get_option('blog_charset'));
		$pCachedOutput->outputCached($content, 60 * 60 * 24 * 2);
		exit;
	}
});

add_action('parse_request', function(WP $pWP) use ($pDI) {
	if (isset($pWP->query_vars['onoffice_estate_preview'], $pWP->query_vars['preview_name']) &&
		wp_verify_nonce($pWP->query_vars['nonce'], 'onoffice-estate-preview') === 1) {
		wp_send_json($pDI->get(FormPreviewEstate::class)
			->preview((string)$pWP->query_vars['preview_name']));
	}
});

add_action('parse_request', function(WP $pWP) use ($pDI) {
	if (isset($pWP->query_vars['onoffice_applicant_search_preview'], $pWP->query_vars['preview_name']) &&
		wp_verify_nonce($pWP->query_vars['nonce'], 'onoffice-applicant-search-preview') === 1) {
		wp_send_json($pDI->get(FormPreviewApplicantSearch::class)
			->preview((string)$pWP->query_vars['preview_name']));
	}
});

return $pDI;
