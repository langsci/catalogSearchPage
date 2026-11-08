<?php

/**
 * @file plugins/generic/catalogSearchPage/CatalogSearchPageHandler.php
 *
 * Copyright (c) 2021-2026 Language Science Press
 * Developed by Ronald Steffen
 * Distributed under the GNU GPL v3. For full terms see the file docs/LICENSE.
 *
 * @class CatalogSearchPageHandler
 *
 * @brief Displays the searchable, sortable and paginated catalog table.
 */

namespace APP\plugins\generic\catalogSearchPage;

use APP\core\Request;
use APP\facades\Repo;
use APP\handler\Handler;
use APP\plugins\generic\catalogSearchPage\classes\CatalogSearchFilter;
use APP\submission\Collector;
use APP\submission\Submission;
use APP\template\TemplateManager;
use PKP\core\VirtualArrayIterator;
use PKP\security\Role;
use PKP\userGroup\UserGroup;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CatalogSearchPageHandler extends Handler
{
    public const SORT_COLUMNS = ['title', 'series', 'year'];
    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];
    public const DEFAULT_PER_PAGE = 25;
    public const MAX_TERMS = 8;
    public const PAGE_NAME = 'catalogSearch';

    public function __construct(protected CatalogSearchPagePlugin $plugin)
    {
        parent::__construct();
    }

    /**
     * Show the catalog search page
     *
     * @param array $args
     * @param Request $request
     */
    public function index($args, $request)
    {
        $context = $request->getContext();
        if (!$context) {
            throw new NotFoundHttpException();
        }

        $templateMgr = TemplateManager::getManager($request);
        $this->setupTemplate($request);

        // Read and sanitize the request
        $query = trim($this->getStringVar($request, 'query'));
        $terms = array_slice(
            preg_split('/\s+/u', mb_strtolower($query), -1, PREG_SPLIT_NO_EMPTY) ?: [],
            0,
            self::MAX_TERMS
        );
        $sort = $this->getStringVar($request, 'sort');
        $sort = in_array($sort, self::SORT_COLUMNS, true) ? $sort : 'title';
        $dir = strtolower($this->getStringVar($request, 'dir')) === 'desc' ? 'desc' : 'asc';
        $perPage = (int) $this->getStringVar($request, 'perPage');
        $perPage = in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : self::DEFAULT_PER_PAGE;
        $page = max(1, (int) $this->getStringVar($request, self::getPageParamName(self::PAGE_NAME)));

        // Publication state filters are only offered when langsciAdditionalFeatures provides the state
        $pubStateFeature = $this->plugin->getPubStateFeature();
        $filterSubmitted = (bool) $request->getUserVar('filter');
        $includeForthcoming = $filterSubmitted ? (bool) $request->getUserVar('forthcoming') : true;
        $includeSuperseded = $filterSubmitted ? (bool) $request->getUserVar('superseded') : false;

        $pubStates = null;
        if ($pubStateFeature) {
            $pubStates = [$pubStateFeature::PUBLISHED];
            if ($includeForthcoming) {
                $pubStates[] = $pubStateFeature::FORTHCOMING;
            }
            if ($includeSuperseded) {
                $pubStates[] = $pubStateFeature::SUPERSEDED;
            }
        }

        $orderDirection = $dir === 'desc' ? Collector::ORDER_DIR_DESC : Collector::ORDER_DIR_ASC;
        $collector = Repo::submission()
            ->getCollector()
            ->filterByContextIds([$context->getId()])
            ->filterByStatus([Submission::STATUS_PUBLISHED])
            ->orderBy(
                $sort === 'year' ? Collector::ORDERBY_DATE_PUBLISHED : Collector::ORDERBY_TITLE,
                $orderDirection
            );

        (new CatalogSearchFilter(
            $collector,
            $terms,
            $pubStates,
            $pubStateFeature ? $pubStateFeature::FORTHCOMING : 1,
            $sort === 'series' ? $orderDirection : null
        ))->register();

        $total = $collector->getCount();
        $pageCount = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pageCount);
        $submissions = $collector->limit($perPage)->offset(($page - 1) * $perPage)->getMany()->all();

        // One query for all series instead of one per book
        $series = [];
        foreach (Repo::section()->getCollector()->filterByContextIds([$context->getId()])->getMany() as $item) {
            $series[$item->getId()] = $item;
        }

        $authorUserGroups = UserGroup::withRoleIds([Role::ROLE_ID_AUTHOR])
            ->withContextIds([$context->getId()])
            ->get();

        $rows = [];
        foreach ($submissions as $submission) {
            $publication = $submission->getCurrentPublication();
            $seriesItem = $series[$publication->getData('seriesId')] ?? null;
            $coverImage = $publication->getLocalizedData('coverImage');
            $pubState = $pubStateFeature ? $pubStateFeature->getPubState($publication) : null;
            $datePublished = (string) $publication->getData('datePublished');

            $rows[] = [
                'bestId' => $submission->getBestId(),
                'title' => $publication->getLocalizedFullTitle(null, 'html'),
                'authors' => $publication->getAuthorString($authorUserGroups, true),
                'coverUrl' => $publication->getLocalizedCoverImageThumbnailUrl($context->getId()),
                'coverAlt' => $coverImage['altText'] ?? '',
                'seriesPath' => $seriesItem?->getPath(),
                'seriesTitle' => $seriesItem?->getLocalizedTitle(),
                'pubState' => $pubState,
                'pubStateLabel' => $pubStateFeature ? trim($pubStateFeature->getPubStateLabel($publication), ': ') : '',
                // Forthcoming books have no year yet
                'year' => $pubStateFeature && $pubState === $pubStateFeature::FORTHCOMING ? '' : substr($datePublished, 0, 4),
            ];
        }

        $params = [
            'query' => $query,
            'sort' => $sort,
            'dir' => $dir,
            'perPage' => $perPage,
        ];
        if ($pubStateFeature) {
            $params['filter'] = 1;
            if ($includeForthcoming) {
                $params['forthcoming'] = 1;
            }
            if ($includeSuperseded) {
                $params['superseded'] = 1;
            }
        }

        // Header links toggle the direction of the current column and start at page 1
        $sortLinks = [];
        foreach (self::SORT_COLUMNS as $column) {
            $isCurrent = $column === $sort;
            $sortLinks[$column] = [
                'url' => $request->url(null, self::PAGE_NAME, null, null, array_merge($params, [
                    'sort' => $column,
                    'dir' => $isCurrent && $dir === 'asc' ? 'desc' : 'asc',
                ])),
                'ariaSort' => $isCurrent ? ($dir === 'asc' ? 'ascending' : 'descending') : 'none',
            ];
        }

        $templateMgr->assign([
            'rows' => $rows,
            'total' => $total,
            'results' => new VirtualArrayIterator($rows, $total, $page, $perPage),
            'query' => $query,
            'sort' => $sort,
            'dir' => $dir,
            'perPage' => $perPage,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'pageParams' => $params,
            'sortLinks' => $sortLinks,
            'pubStateEnabled' => (bool) $pubStateFeature,
            'includeForthcoming' => $includeForthcoming,
            'includeSuperseded' => $includeSuperseded,
        ]);

        $assetsUrl = $request->getBaseUrl() . '/' . $this->plugin->getPluginPath();
        $templateMgr->addStyleSheet('catalogSearch', $assetsUrl . '/css/catalogSearch.css', ['contexts' => ['frontend']]);
        $templateMgr->addJavaScript('catalogSearch', $assetsUrl . '/js/catalogSearch.js', ['contexts' => ['frontend']]);

        $templateMgr->display($this->plugin->getTemplateResource('catalogSearch.tpl'));
    }

    /**
     * Get a request variable as string, ignoring array values.
     */
    protected function getStringVar(Request $request, string $name): string
    {
        $value = $request->getUserVar($name);
        return is_scalar($value) ? (string) $value : '';
    }
}
