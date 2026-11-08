/**
 * @file plugins/generic/catalogSearchPage/js/catalogSearch.js
 *
 * Copyright (c) 2021-2026 Language Science Press
 * Distributed under the GNU GPL v3. For full terms see the file docs/LICENSE.
 *
 * Progressive enhancement: the page works as a plain GET form. This script
 * only submits the form automatically when the search input pauses or a
 * filter changes, and restores the focus afterwards.
 */
(function () {
	'use strict';

	var FOCUS_KEY = 'catalogSearchFocus';
	var DEBOUNCE_MS = 500;

	var form = document.getElementById('cs_search_form');
	var input = document.getElementById('cs_query');
	if (!form || !input) {
		return;
	}

	function submit() {
		if (input === document.activeElement) {
			sessionStorage.setItem(FOCUS_KEY, '1');
		}
		form.submit();
	}

	if (sessionStorage.getItem(FOCUS_KEY)) {
		sessionStorage.removeItem(FOCUS_KEY);
		input.focus();
		input.setSelectionRange(input.value.length, input.value.length);
	}

	var timer = null;
	var lastValue = input.value;
	input.addEventListener('input', function () {
		clearTimeout(timer);
		if (input.value === lastValue) {
			return;
		}
		lastValue = input.value;
		timer = setTimeout(submit, DEBOUNCE_MS);
	});

	form.addEventListener('change', function (event) {
		if (event.target !== input) {
			submit();
		}
	});
})();
