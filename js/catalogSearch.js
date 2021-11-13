// search catalog table
function searchCatalog() {
    $("#searchPattern").on("keyup", function () {
        var value = $(this).val().toLowerCase();
        $("#catalog_table tbody tr").filter(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
        $("#monograph_count")[0].innerText = $("#monograph_count")[0].innerText.replace(/\d+/g,  $("#catalog_table tbody tr").filter(":visible").length);
    })
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
};

function sortGrid(colNum, type) {
    let tbody = catalog_table.querySelector('tbody');

    let rowsArray = Array.from(tbody.rows);

    // compare(a, b) compares two rows, need for sorting
    let compare;

    switch (type.split("-", 1)[0]) {
        case 'year':
            compare = function (rowA, rowB) {
                if (type.endsWith('asc')) {
                    return -(rowA.cells[colNum].innerText - rowB.cells[colNum].innerText);
                } else {
                    return rowA.cells[colNum].innerText - rowB.cells[colNum].innerText;
                };
            };
            break;
        case 'title':
        case 'series':
            compare = function (rowA, rowB) {
                if (type.endsWith('asc')) {
                    return rowA.cells[colNum].innerText > rowB.cells[colNum].innerText ? -1 : 1;
                } else {
                    return rowA.cells[colNum].innerText > rowB.cells[colNum].innerText ? 1 : -1;
                }
            };
            break;
    }

    // sort
    rowsArray.sort(compare);

    tbody.append(...rowsArray);
}