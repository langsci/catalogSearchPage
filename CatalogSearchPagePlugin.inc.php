<?php

 /*
 *
 * @file plugins/generic/catalogSearchPage/catalogSearchPagePlugin.inc.php
 *
 * Copyright (c) 2021 Language Science Press
 * Developed by Ronald Steffen
 * Distributed under the GNU GPL v3. For full terms see the file docs/LICENSE.
 *
 * @brief catalogSearchPagePlugin class definition
 *
 */


import('lib.pkp.classes.plugins.GenericPlugin');

class CatalogSearchPagePlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = null)
    {

        // If the system isn't installed, or is performing an upgrade, don't
        // register hooks. This will prevent DB access attempts before the
        // schema is installed.
        if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) {
            return true;
        }
        
        $success = parent::register($category, $path, $mainContextId);
        if ($success) {
            if ($this->getEnabled($mainContextId)) {
                if ($this->getEnabled()) {
                    $this->addLocaleData();

                    // register locale files for reviews grid controller classes
                    $locale = AppLocale::getLocale();
                    AppLocale::registerLocaleFile($locale, 'plugins/generic/catalogSearchPage/locale/'.$locale.'/locale.po');

                    // register hooks
                    HookRegistry::register('LoadHandler', array($this, 'loadCatalogSearchPageHandler'));
                    HookRegistry::register('TemplateResource::getFilename', array($this, 'getTemplateFilePath'));
                    HookRegistry::register('Publication::publish', array($this, 'testSearchIndex'));
                    HookRegistry::register('Publication::unpublish', array($this, 'testSearchIndex'));
                    define('SUBMISSION_SEARCH_DATE_PUBLISHED',	0x00000200);
                    define('SUBMISSION_SEARCH_SERIES_TITLE',	0x00000210);
                }
            }
            return $success;
        }
        return $success;
    }

    function testSearchIndex($hookname, $params) {
        $publication = $params[1];
        $submission = $params[2];

        //
        // Get the series
		$seriesDao = DAORegistry::getDAO('SeriesDAO'); /* @var $seriesDao SeriesDAO */
		$series = $seriesDao->getById($publication->getData('seriesId'));

        // Update search index
		import('classes.search.MonographSearch');
		$submissionId = $submission->getId();
		Application::getSubmissionSearchIndex()->updateTextIndex($submissionId, SUBMISSION_SEARCH_DATE_PUBLISHED, date('Y', strtotime($publication->getData('datePublished'))));
        Application::getSubmissionSearchIndex()->updateTextIndex($submissionId, SUBMISSION_SEARCH_SERIES_TITLE, $series->getLocalizedTitle());
        return;
    }

    function loadCatalogSearchPageHandler($hookname, $params) {
        $page = $params[0];
        $op = &$params[1];
        $sourceFile = &$params[2];

        switch ($page) {
            case 'catalogSearch':
                switch ($op) {
                    case 'index':
                        define('HANDLER_CLASS', 'CatalogSearchPageHandler');
                        $this->import('CatalogSearchPageHandler');
                        break;
                }
                return true;
            }
        return false;
    }

	function getDisplayName() {
		return __('plugins.generic.catalogSearchPage.displayName');
	}

	function getDescription() {
		return __('plugins.generic.catalogSearchPage.description');
	}

    function getTemplateFilePath($hookname, $args)
    {
        switch ($args[1]) {
        case 'catalogSearch.tpl':
            $args[0] = 'plugins/generic/catalogSearchPage/templates/catalogSearch.tpl';
            return false;
        }
    }
}
?>