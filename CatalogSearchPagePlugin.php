<?php

/**
 * @file plugins/generic/catalogSearchPage/CatalogSearchPagePlugin.php
 *
 * Copyright (c) 2021-2026 Language Science Press
 * Developed by Ronald Steffen
 * Distributed under the GNU GPL v3. For full terms see the file docs/LICENSE.
 *
 * @class CatalogSearchPagePlugin
 *
 * @brief Adds a tabular, searchable and sortable catalog page at /catalogSearch.
 */

namespace APP\plugins\generic\catalogSearchPage;

use APP\plugins\generic\langsciAdditionalFeatures\classes\features\pubState\PubStateFeature;
use APP\plugins\generic\langsciAdditionalFeatures\LangsciAdditionalFeaturesPlugin;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\plugins\PluginRegistry;

class CatalogSearchPagePlugin extends GenericPlugin
{
    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);
        if ($success && $this->getEnabled($mainContextId)) {
            // Run before the core handler for custom navigation menu items, which
            // would otherwise claim /catalogSearch if a menu item uses that path
            Hook::add('LoadHandler', $this->loadHandler(...), Hook::SEQUENCE_CORE);
        }
        return $success;
    }

    /**
     * Attach the catalog search handler to the /catalogSearch page.
     */
    public function loadHandler(string $hookName, array $args): bool
    {
        $page = $args[0];
        $op = $args[1];
        $handler = &$args[3];

        if ($page === 'catalogSearch' && $op === 'index') {
            $handler = new CatalogSearchPageHandler($this);
            return Hook::ABORT;
        }
        return Hook::CONTINUE;
    }

    /**
     * Get the publication state feature of langsciAdditionalFeatures,
     * or null if the plugin or the feature is not enabled for this press.
     */
    public function getPubStateFeature(): ?PubStateFeature
    {
        $plugin = PluginRegistry::getPlugin('generic', 'langsciadditionalfeaturesplugin');
        if (!$plugin instanceof LangsciAdditionalFeaturesPlugin) {
            return null;
        }
        $feature = $plugin->getFeature('pubState');
        return $feature instanceof PubStateFeature ? $feature : null;
    }

    public function getDisplayName()
    {
        return __('plugins.generic.catalogSearchPage.displayName');
    }

    public function getDescription()
    {
        return __('plugins.generic.catalogSearchPage.description');
    }
}
