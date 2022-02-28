<?php

/**
 * @file plugins/themes/kfuTheme/KfuThemePlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class KfuThemePlugin
 * @ingroup plugins_themes_kfuTheme
 *
 * @brief "KfuTheme" theme plugin
 */

import('classes.plugins.ThemePlugin');

class KfuThemePlugin extends ThemePlugin {
	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'KfuThemePlugin';
	}

	function getDisplayName() {
		return 'Kfu Theme';
	}

	function getDescription() {
		return 'KFU Science Journals theme';
	}

	function getStylesheetFilename() {
		return 'kfuTheme.css';
	}

	function getLocaleFilename($locale) {
		return null; // No locale data
	}
}

?>
