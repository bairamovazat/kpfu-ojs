<?php

/**
 * @file LjmBlockPlugin.inc.php
 *
 * Copyright (c) 2016 Sam K.
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LjmBlockPlugin
 * @ingroup plugins_blocks_ljm
 *
 * @brief Class for "ljm" block plugin
 */

import('lib.pkp.classes.plugins.BlockPlugin');

class LjmBlockPlugin extends BlockPlugin {
	
	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return __('plugins.block.ljm.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return __('plugins.block.ljm.description');
	}
}

?>
