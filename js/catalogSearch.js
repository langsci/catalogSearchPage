/* 
 * @file plugins/generic/catalogSearchPage/js/catalogSearch.js
 * 
 * Copyright (c) 2021 Language Science Press
 * Developed by Ronald Steffen
 * Distributed under the GNU GPL v3. For full terms see the file docs/LICENSE.
 */

// search catalog table
function searchCatalog() {
    $("#searchPattern").on("keyup", function () {
        var values = $(this).val().toLowerCase().split(" ").filter(n => n);
        // filter by text
        $("#catalog_table tbody tr").filter(function () {
            return $(this).toggle(values.every(x => $(this).text().toLowerCase().indexOf(x) > -1));
        });
        if ($("#includeForthcoming")) {
            // filter by forthcoming
            if (!$("#includeForthcoming")[0].checked) {
                $("#catalog_table tbody tr").filter(function () {
                    if ($(this).find('#pubState')[0]) {
                        if ($(this).find('#pubState')[0].textContent == 1) {
                            $(this).hide();
                        }
                    }
                });
            }
            // filter by superseded
            if (!$("#includeSuperseded")[0].checked) {
                $("#catalog_table tbody tr").filter(function () {
                    if ($(this).find('#pubState')[0]) {
                        if ($(this).find('#pubState')[0].textContent == 3) {
                            $(this).hide();
                        }
                    }
                });
            }
        }
        // count hits
        $("#monograph_count")[0].innerText = $("#monograph_count")[0].innerText.replace(/\d+/g,  $("#catalog_table tbody tr").filter(":visible").length);
        updateTable();
    });
};

// sort catalog table
catalog_table.onclick = function (e) {
    if (e.target.tagName != 'TH') return;

    let th = e.target;
    sortGrid(th.cellIndex, th.dataset.type);

    if (th.dataset.type.endsWith("asc")) {
        th.dataset.type = th.dataset.type.replace("asc", "desc");
    } else {
        th.dataset.type = th.dataset.type.replace("desc", "asc");
    }
    updatePages();
};

function sortGrid(cs_colNum, type) {
    let tbody = catalog_table.querySelector('tbody');

    let rowsArray = Array.from(tbody.rows);

    // compare(a, b) compares two rows, need for sorting
    let compare;

    switch (type.split("-", 1)[0]) {
        case 'year':
            compare = function (rowA, rowB) {
                if (type.endsWith('asc')) {
                    return -(rowA.cells[cs_colNum].innerText - rowB.cells[cs_colNum].innerText);
                } else {
                    return rowA.cells[cs_colNum].innerText - rowB.cells[cs_colNum].innerText;
                };
            };
            break;
        case 'title':
        case 'series':
            compare = function (rowA, rowB) {
                if (type.endsWith('asc')) {
                    return rowA.cells[cs_colNum].innerText.replace(/\s+/g,"") > rowB.cells[cs_colNum].innerText.replace(/\s+/g,"") ? -1 : 1;
                } else {
                    return rowA.cells[cs_colNum].innerText.replace(/\s+/g,"") > rowB.cells[cs_colNum].innerText.replace(/\s+/g,"") ? 1 : -1;
                }
            };
            break;
    }

    // sort
    rowsArray.sort(compare);
    tbody.append(...rowsArray);
}

function updateTable() {

    // paginate results
    var totalRows = $('#catalog_table').find('tbody tr:has(td):visible').length;
    var recordPerPage = $('#PageLimit, option:selected')[0].value;

    $('.cs_pageNumber').remove();
    $('.cs_pagination')[0].dataset.page = 1;

    if (totalRows > recordPerPage) {

        var totalPages = Math.ceil(totalRows / recordPerPage); 
        
        // create pagination div
        var $pages = $('.cs_pagination');
        $('<span class="cs_pageNumber" data-n="prev">&nbsp;&lt;</span>').appendTo($pages);
        for (i = 0; i < totalPages; i++) {  
            $('<span class="cs_pageNumber" data-n="' + (i + 1) + '">&nbsp;' + (i + 1) + '</span>').appendTo($pages); 
        }
        $('<span class="cs_pageNumber" data-n="next">&nbsp;&gt;</span>').appendTo($pages);

        // add focus style to page numbers
        $('.cs_pageNumber').on("mouseenter", function() {
            $(this).addClass('cs_focus');
        }).on("mouseleave", function() {
            $(this).removeClass('cs_focus');
        });

        // show first page
        $('table tbody tr:has(td)').slice(recordPerPage, totalRows).hide();

        // bind event to show subsquent pages
        $('.cs_pageNumber').on("click", function() {
            var totalPages = $('.cs_pageNumber').length - 2;

            if ($(this)[0].dataset.n == "prev" && $('.cs_pagination')[0].dataset.page > 1) {
                $('.cs_pagination')[0].dataset.page = parseInt($('.cs_pagination')[0].dataset.page) - 1;
            } else if ($(this)[0].dataset.n == "next" && $('.cs_pagination')[0].dataset.page < totalPages) {
                $('.cs_pagination')[0].dataset.page = parseInt($('.cs_pagination')[0].dataset.page) + 1;
            } else if (parseInt($(this)[0].dataset.n)) {
                $('.cs_pagination')[0].dataset.page = $(this)[0].dataset.n;
            };
            
            var page = $('.cs_pagination')[0].dataset.page;
            $('#catalog_table').find('tbody tr:has(td)').hide(); 
            var nBegin = (page - 1) * recordPerPage;  
            var nEnd = page * recordPerPage;
            $('table tbody tr:has(td)').slice(nBegin, nEnd).show();
        });
    } else {
        $('.cs_pagination').addClass('cs_border_none');
    }
}

function updatePages() {
    // reset
    $("#catalog_table tbody tr").show();
    // do text search
    $("#searchPattern").trigger("keyup");
}

function initTable() {
    if ($("#includeForthcoming")) {
        $("#includeForthcoming").on("change", function(){updatePages();});
        $("#includeSuperseded").on("change", function(){updatePages();});
    }
    searchCatalog();
    updatePages();
}

jQuery(initTable());