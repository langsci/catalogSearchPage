<?php

/**
 * @file plugins/generic/catalogSearchPage/classes/CatalogSearchFilter.php
 *
 * Copyright (c) 2021-2026 Language Science Press
 * Distributed under the GNU GPL v3. For full terms see the file docs/LICENSE.
 *
 * @class CatalogSearchFilter
 *
 * @brief Extends one submission Collector with what the catalog search page
 *   needs and the core collector can't do: matching ALL search terms (each term
 *   may match title, subtitle, prefix, authors, keywords, subjects, disciplines
 *   or abstract), filtering by publication state and sorting by series.
 */

namespace APP\plugins\generic\catalogSearchPage\classes;

use APP\core\Application;
use APP\submission\Collector;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use PKP\controlledVocab\ControlledVocab;
use PKP\facades\Locale;
use PKP\plugins\Hook;
use PKP\search\SubmissionSearch;

class CatalogSearchFilter
{
    /**
     * @param string[] $terms Lowercase search terms, all of which must match
     * @param int[]|null $pubStates Publication states to include, null for no state filtering
     * @param int $defaultPubState State assumed when a publication has none set
     * @param string|null $seriesSortDirection ASC or DESC to sort by series title
     */
    public function __construct(
        protected Collector $collector,
        protected array $terms = [],
        protected ?array $pubStates = null,
        protected int $defaultPubState = 1,
        protected ?string $seriesSortDirection = null
    ) {
    }

    /**
     * Apply to the collector. Other collectors are not affected.
     */
    public function register(): void
    {
        Hook::add('Submission::Collector', $this->apply(...));
    }

    public function apply(string $hookName, array $args): bool
    {
        /** @var Builder $q */
        $q = $args[0];
        if ($args[1] !== $this->collector) {
            return Hook::CONTINUE;
        }

        foreach ($this->terms as $term) {
            $this->addTermFilter($q, $term);
        }
        if ($this->pubStates !== null) {
            $this->addPubStateFilter($q);
        }
        if ($this->seriesSortDirection) {
            $this->addSeriesSort($q);
        }
        return Hook::CONTINUE;
    }

    protected function addTermFilter(Builder $q, string $term): void
    {
        $pattern = '%' . addcslashes($term, '\\%_') . '%';

        $q->where(
            fn (Builder $q) => $q
                // Title, subtitle and prefix of the current publication
                ->whereExists(
                    fn (Builder $sub) => $sub->select(DB::raw('1'))
                        ->from('publication_settings AS cps')
                        ->whereColumn('cps.publication_id', 'po.publication_id')
                        ->whereIn('cps.setting_name', ['title', 'subtitle', 'prefix'])
                        ->whereRaw('LOWER(cps.setting_value) LIKE ?', [$pattern])
                )
                // Author names
                ->orWhereExists(
                    fn (Builder $sub) => $sub->select(DB::raw('1'))
                        ->from('authors AS cau')
                        ->join('author_settings AS caus', 'caus.author_id', '=', 'cau.author_id')
                        ->whereColumn('cau.publication_id', 'po.publication_id')
                        ->whereIn('caus.setting_name', ['givenName', 'familyName', 'preferredPublicName'])
                        ->whereRaw('LOWER(caus.setting_value) LIKE ?', [$pattern])
                )
                // Keywords, subjects and disciplines
                ->orWhereExists(
                    fn (Builder $sub) => $sub->select(DB::raw('1'))
                        ->from('controlled_vocabs AS ccv')
                        ->join('controlled_vocab_entries AS ccve', 'ccve.controlled_vocab_id', '=', 'ccv.controlled_vocab_id')
                        ->join('controlled_vocab_entry_settings AS ccves', 'ccves.controlled_vocab_entry_id', '=', 'ccve.controlled_vocab_entry_id')
                        ->where('ccv.assoc_type', '=', Application::ASSOC_TYPE_PUBLICATION)
                        ->whereColumn('ccv.assoc_id', 'po.publication_id')
                        ->whereIn('ccv.symbolic', [
                            ControlledVocab::CONTROLLED_VOCAB_SUBMISSION_KEYWORD,
                            ControlledVocab::CONTROLLED_VOCAB_SUBMISSION_SUBJECT,
                            ControlledVocab::CONTROLLED_VOCAB_SUBMISSION_DISCIPLINE,
                        ])
                        ->where('ccves.setting_name', '=', 'name')
                        ->whereRaw('LOWER(ccves.setting_value) LIKE ?', [$pattern])
                )
                // Abstract. Matched on the search index, which holds the text without HTML tags.
                ->orWhereExists(
                    fn (Builder $sub) => $sub->select(DB::raw('1'))
                        ->from('submission_search_objects AS sso')
                        ->join('submission_search_object_keywords AS ssok', 'ssok.object_id', '=', 'sso.object_id')
                        ->join('submission_search_keyword_list AS sskl', 'sskl.keyword_id', '=', 'ssok.keyword_id')
                        ->whereColumn('sso.submission_id', 's.submission_id')
                        ->where('sso.type', '=', SubmissionSearch::SUBMISSION_SEARCH_ABSTRACT)
                        ->whereRaw('sskl.keyword_text LIKE ?', [$pattern])
                )
        );
    }

    protected function addPubStateFilter(Builder $q): void
    {
        $includeDefault = in_array($this->defaultPubState, $this->pubStates, true);
        // Values that read as the default state
        $values = array_map('strval', $this->pubStates);
        if ($includeDefault) {
            $values = array_merge($values, ['', '0']);
        }

        $stateRows = fn (Builder $sub) => $sub->select('pst.publication_id')
            ->from('publication_settings AS pst')
            ->where('pst.setting_name', '=', 'pubState');

        $q->where(function (Builder $q) use ($stateRows, $values, $includeDefault) {
            $q->whereIn('po.publication_id', fn (Builder $sub) => $stateRows($sub)->whereIn('pst.setting_value', $values));
            if ($includeDefault) {
                // A publication without a stored state has the default state
                $q->orWhereNotIn('po.publication_id', $stateRows);
            }
        });
    }

    protected function addSeriesSort(Builder $q): void
    {
        $q->leftJoin(
            'series_settings AS sst',
            fn (JoinClause $join) => $join->on('sst.series_id', '=', 'po.series_id')
                ->where('sst.setting_name', '=', 'title')
                ->where('sst.locale', '=', Locale::getLocale())
        )
            ->reorder()
            ->orderBy('sst.setting_value', $this->seriesSortDirection)
            ->orderBy('s.submission_id');
    }
}
