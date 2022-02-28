{**
 * plugins/blocks/ljm/block.tpl
 *
 * Copyright (c) 2013-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site sidebar menu -- "ljm" block.
 *
 *}
<!--div class="block" id="customblock-undertitle">
	<div id="undertitle_text">{translate key="plugins.block.ljm.undertitle"}</div>
</div-->
<div class="block" id="customblock-kfu">
	<div class="full_line">
		<div class="white_text">{translate key="plugins.block.ljm.kfu"}</div>
	</div>
</div>
<div id="pagefooterbox">
	<div id="footerbox" class="full_line">
		<div id="footertext">&copy; <a href="http://kpfu.ru" alt="{translate key="plugins.block.ljm.kfu"}">{translate key="plugins.block.ljm.kfu-short"}</a>, 2013—{'Y'|date}</div>
	</div>
</div>