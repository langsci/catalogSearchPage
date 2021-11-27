<?php

 /*
 *
 * @file plugins/generic/catalogSearchPage/catalogSearchPageHandler.inc.php
 *
 * Copyright (c) 2021 Language Science Press
 * Developed by Ronald Steffen
 * Distributed under the GNU GPL v3. For full terms see the file docs/LICENSE.
 *
 * @brief catalogSearchPageHandler class definition
 *
 */

import('lib.pkp.pages.catalog.PKPCatalogHandler');

class CatalogSearchPageHandler extends PKPCatalogHandler
{

    /**
     * Show the series index page
     * @param $args array 
     * @param $request PKPRequest
     */
    public function index($args, $request)
    {
		$templateMgr = TemplateManager::getManager($request);
		$this->setupTemplate($request);
		$context = $request->getContext();

		import('classes.submission.Submission'); // STATUS_ constants
		import('classes.submission.SubmissionDAO'); // ORDERBY_ constants

		$orderOption = $context->getData('catalogSortOption') ? $context->getData('catalogSortOption') : ORDERBY_DATE_PUBLISHED . '-' . SORT_DIRECTION_DESC;
		list($orderBy, $orderDir) = explode('-', $orderOption);

		$submissionService = Services::get('submission');
		$params = array(
			'contextId' => $context->getId(),
			'orderByFeatured' => true,
			'orderBy' => $orderBy,
			'orderDirection' => $orderDir == SORT_DIRECTION_ASC ? 'ASC' : 'DESC',
			'status' => STATUS_PUBLISHED,
		);
		$monographs = iterator_to_array($submissionService->getMany($params));

		$seriesDao = DAORegistry::getDAO('SeriesDAO'); /* @var $seriesDao SeriesDAO */
		foreach ($monographs as $monograph) {
			// Get the series for this monograph
			$series = $seriesDao->getById( $monograph->getData('publications')[0]->getData('seriesId'),$context->getId());
			if ($series) {
				$monograph->setData('seriesTitle', $series->getLocalizedTitle());
				$monograph->setData('seriesPath', $series->getData('path'));
			}
		}

		$templateMgr->assign(array(
			'monographs' => $monographs,
			'baseurl' => $request->getBaseUrl()
		));

		$templateMgr->addJavaScript('catalogSearch', $request->getBaseUrl()."/plugins/generic/catalogSearchPage/js/catalogSearch.js");

        $templateMgr->display('catalogSearch.tpl');
    }
}
?>