{**
 * templates/catalogSearch.tpl
 *
 * Copyright (c) 2021-2026 Language Science Press
 * Developed by Ronald Steffen
 * Distributed under the GNU GPL v3. For full terms see the file docs/LICENSE.
 *
 * @brief Searchable, sortable and paginated catalog table
 *
 * @uses $rows array The books of the current page
 * @uses $total int Number of books matching the search
 * @uses $results VirtualArrayIterator Used for the page links
 * @uses $query string The search phrase
 * @uses $sortLinks array Header link and aria-sort value per column
 * @uses $pageParams array Request parameters to keep in the page links
 * @uses $pubStateEnabled bool Whether publication states are available
 *}
{include file="frontend/components/header.tpl" pageTitle="navigation.catalog"}

<div class="page page_catalog cs_page">
	{include file="frontend/components/breadcrumbs.tpl" currentTitleKey="navigation.catalog"}
	<h1>{translate key="navigation.catalog"}</h1>

	<form class="cs_search_form" id="cs_search_form" method="get" action="{url page="catalogSearch"}" role="search">
		<input type="hidden" name="sort" value="{$sort|escape}">
		<input type="hidden" name="dir" value="{$dir|escape}">
		<div class="cs_search_input">
			<label for="cs_query" class="pkp_screen_reader">{translate key="plugins.generic.catalogSearchPage.SearchLabel"}</label>
			<input type="search" id="cs_query" name="query" value="{$query|escape}" placeholder="{translate key="plugins.generic.catalogSearchPage.SearchPlaceholder"}" size="60">
			<button type="submit" class="pkp_button">{translate key="common.search"}</button>
		</div>

		{if $pubStateEnabled}
			<fieldset class="cs_select_pubState">
				<legend>{translate key="plugins.generic.catalogSearchPage.includeInSearch"}</legend>
				<input type="hidden" name="filter" value="1">
				<label>
					<input type="checkbox" name="forthcoming" value="1"{if $includeForthcoming} checked{/if}>
					{translate key="plugins.generic.catalogSearchPage.includeForthcoming"}
				</label>
				<label>
					<input type="checkbox" name="superseded" value="1"{if $includeSuperseded} checked{/if}>
					{translate key="plugins.generic.catalogSearchPage.includeSuperseded"}
				</label>
			</fieldset>
		{/if}

		<div class="cs_page_limits">
			<label for="cs_per_page">{translate key="plugins.generic.catalogSearchPage.perPage"}</label>
			<select id="cs_per_page" name="perPage">
				{foreach from=$perPageOptions item=option}
					<option value="{$option|escape}"{if $option == $perPage} selected{/if}>{$option|escape}</option>
				{/foreach}
			</select>
		</div>
	</form>

	<p id="cs_result_count" class="monograph_count" aria-live="polite">
		{translate key="catalog.browseTitles" numTitles=$total}
	</p>

	{if !$rows|@count}
		<p>{translate key="plugins.generic.catalogSearchPage.noResults"}</p>
	{else}
		<table id="catalog_table" class="cs_catalog_table">
			<thead>
				<tr>
					<th scope="col" class="cs_col"><span class="pkp_screen_reader">{translate key="monograph.coverImage"}</span></th>
					<th scope="col" class="cs_col" aria-sort="{$sortLinks.title.ariaSort}">
						<a href="{$sortLinks.title.url|escape}">{translate key="plugins.generic.catalogSearchPage.TableColLabelTitle"}</a>
					</th>
					<th scope="col" class="cs_col" aria-sort="{$sortLinks.series.ariaSort}">
						<a href="{$sortLinks.series.url|escape}">{translate key="plugins.generic.catalogSearchPage.TableColLabelSeries"}</a>
					</th>
					<th scope="col" class="cs_col_year" aria-sort="{$sortLinks.year.ariaSort}">
						<a href="{$sortLinks.year.url|escape}">{translate key="plugins.generic.catalogSearchPage.TableColLabelYear"}</a>
					</th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$rows item=row}
					{capture assign=bookUrl}{url page="catalog" op="book" path=$row.bestId}{/capture}
					<tr data-pubstate="{$row.pubState|escape}">
						<td>
							{if $row.coverUrl}
								<a href="{$bookUrl|escape}" class="cover">
									<img class="cs_image" loading="lazy" src="{$row.coverUrl|escape}" alt="{$row.coverAlt|escape}">
								</a>
							{/if}
						</td>
						<td>
							<div class="title">
								<a href="{$bookUrl|escape}">
									{if $row.pubStateLabel}<sup class="pubState_label pubState_{$row.pubState|escape}">{$row.pubStateLabel|escape}</sup> {/if}<strong>{$row.title|strip_unsafe_html}</strong>
								</a>
							</div>
							<div class="author">{$row.authors|escape}</div>
						</td>
						<td class="cs_col_series">
							{if $row.seriesPath}
								<a href="{url page="catalog" op="series" path=$row.seriesPath}" title="{$row.seriesTitle|escape}">{$row.seriesPath|escape|upper}</a>
							{/if}
						</td>
						<td class="cs_col cs_col_year">{$row.year|escape}</td>
					</tr>
				{/foreach}
			</tbody>
		</table>

		<div class="cmp_pagination cs_pagination">
			{page_links anchor="catalog_table" iterator=$results name="catalogSearch" params=$pageParams}
		</div>
	{/if}
</div><!-- .page -->

{include file="frontend/components/footer.tpl"}
