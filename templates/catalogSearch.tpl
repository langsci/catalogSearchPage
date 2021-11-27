{**
 * templates/catalogSearch.tpl adopted from templates/frontend/pages/catalog.tpl
 *
 * Copyright (c) 2021 Language Science Press
 * Developed by Ronald Steffen
 * Distributed under the GNU GPL v3. For full terms see the file docs/LICENSE.
 *
 * @brief Display the page to view the catalog.
 *
 * @uses $publishedSubmissions array List of published submissions
 * @uses $prevPage int The previous page number
 * @uses $nextPage int The next page number
 * @uses $showingStart int The number of the first item on this page
 * @uses $showingEnd int The number of the last item on this page
 *}
{include file="frontend/components/header.tpl" pageTitle="navigation.catalog"}

<link rel="stylesheet" href="{$baseurl}/plugins/generic/catalogSearchPage/css/catalogSearch.css" type="text/css" />

<div class="page page_catalog">
	{include file="frontend/components/breadcrumbs.tpl" currentTitleKey="navigation.catalog"}
	<h1>{translate key="navigation.catalog"}</h1>

    <div>
        <div class="monograph_count cs_search_input" >
            <input type="text" id="searchPattern" value="" placeholder="{translate key="plugins.generic.catalogSearchPage.SearchPlaceholder"}" size=60>
        </div>
        <div id="monograph_count" class="monograph_count">
            {translate key="catalog.browseTitles" numTitles=$monographs|@count}
        </div>
    </div>

	{* No published titles *}
	{if !$monographs|@count}
		<h2>
			{translate key="catalog.category.heading"}
		</h2>
		<p>{translate key="catalog.noTitles"}</p>

	{* Monograph List *}
	{else}
		{if !$heading}
	        {assign var="heading" value="h2"}
        {/if}
        {if !$titleKey}
            {assign var="monographHeading" value=$heading}
        {elseif $heading == 'h2'}
            {assign var="monographHeading" value="h3"}
        {elseif $heading == 'h3'}
            {assign var="monographHeading" value="h4"}
        {else}
            {assign var="monographHeading" value="h5"}
        {/if}

        <div class="cmp_monographs_list">

            {* Optional title *}
            {if $titleKey}
                <{$heading} class="title">
                    {translate key=$titleKey}
                </{$heading}>
            {/if}

            <div id="pagination_top" class="cs_pagination" data-page="1">
            </div>

            <table id="catalog_table" class="cs_catalog_table paginated">
                <thead>
                    <tr>
                        <th data-type="" class="cs_col"></th>
                        <th id="title" data-type="title-asc" class="cs_col">&#8645;&nbsp;{translate key="plugins.generic.catalogSearchPage.TableColLabelTitle"}</th>
                        <th id="series" data-type="series-asc" class="cs_col">&#8645;&nbsp;{translate key="plugins.generic.catalogSearchPage.TableColLabelSeries"}</th>
                        <th id="year" data-type="year-asc" class="cs_col_year">&#8645;&nbsp;{translate key="plugins.generic.catalogSearchPage.TableColLabelYear"}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach name="monographListLoop" from=$monographs item=monograph}
                        <tr>
                            <td>
                        		<a {if $press}href="{url press=$press->getPath() page="catalog" op="book" path=$monograph->getBestId()}"{else}href="{url page="catalog" op="book" path=$monograph->getBestId()}"{/if} class="cover">
                                    {assign var="cover coverImage" value=$monograph->getCurrentPublication()->getLocalizedData('coverImage')}
                                    <img class="cs_image"
                                        src="{$monograph->getCurrentPublication()->getLocalizedCoverImageThumbnailUrl($monograph->getData('contextId'))}"
                                        alt="{$coverImage.altText|escape|default:'No alt text provided for this cover image.'}"
                                    >
                                </a>
                            </td>
                            <td>
                                <div class="title">
                                    <a {if $press}href="{url press=$press->getPath() page="catalog" op="book" path=$monograph->getBestId()}"{else}href="{url page="catalog" op="book" path=$monograph->getBestId()}"{/if}>
                                        <strong>{$monograph->getLocalizedFullTitle()|escape}</strong>
                                    </a>
                                </div>
                        		<div class="author">
                                    {$monograph->getAuthorOrEditorString()|escape}
                                </div>
                            </td>
                            <td class="cs_col_series">
                       	    	{if $monograph->getData('seriesPath')}
                                    <div class="seriesPosition tooltip" title="{$monograph->getData('seriesTitle')|escape}">
                                        {assign var=pubs value=$monograph->getData('publications')}
                                        <a {if $press}href="{url press=$press->getPath() page="catalog" op="series" path=$monograph->getData('seriesPath')}"{else}href="{url page="catalog" op="series" path=$monograph->getData('seriesPath')}"{/if}>
                                            {$monograph->getData('seriesPath')|escape|upper}
                                        </a>
                                    </div>
                                {/if}
                            </td>
                            <td class="cs_col cs_col_year">
                                {$monograph->getDatePublished()|date_format:"Y"}
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
            <div id="pagination_bottom" class="cs_pagination" data-page="1">
                <select id="pageLimits" class="cs_page_limits" onchange="updatePages()">
                    {* <option value=2>2</option> dev option *}
                    <option value=5>5</option>
                    <option value=10>10</option>
                    <option value=25 selected>25</option>
                    <option value=50>50</option>
                    <option value=150>150</option>
                </select>
            </div>
        </div>
	{/if}
</div><!-- .page -->

{include file="frontend/components/footer.tpl"}
