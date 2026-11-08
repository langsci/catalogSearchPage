# Catalog Search Page Plugin

- Author: Ronald Steffen
- Version: 2.0.0.0

## Description

An OMP plugin that adds a "Catalog Page" providing a tabular, searchable, sortable and paginated list view.

Search, sorting and pagination run on the server. A search phrase is split into words and a book must match
**all** words. Each word may match the title, subtitle, prefix, author names, keywords, subjects, disciplines
or the abstract (substring match). Abstracts are matched through the OMP search index, so after importing
books run `php tools/rebuildSearchIndex.php` once if abstract matches are missing.

The page works without JavaScript. A small script submits the search automatically when typing pauses.

## Publication state (optional)

If the `pubState` feature of the langsciAdditionalFeatures plugin is enabled for the press, the page shows
the state label of forthcoming and superseded books, offers filters for both, and hides the year of
forthcoming books. Without it, these parts are not shown.

## License

Copyright (c) 2021-2026 Language Science Press

Distributed under the GNU GPL v3. For full terms see the file docs/LICENSE.

## System Requirements

- OMP 3.5

## Usage

Create a link or menu entry pointing at `http://<base URL>/catalogSearch`
