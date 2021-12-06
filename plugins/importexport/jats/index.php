<?php

/**
 * @defgroup plugins_importexport_jats
 */
 
/**
 * @file plugins/importexport/jats/index.php
 *
 * Copyright (c) 2013-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_importexport_jats
 * @brief Wrapper for jats XML import/export plugin.
 *
 */

require_once('JatsImportExportPlugin.inc.php');

return new JatsImportExportPlugin();

?>
