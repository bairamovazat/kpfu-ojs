{**
 * plugins/blocks/languageToggle/block.tpl
 *
 * Copyright (c) 2013-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site sidebar menu -- language toggle.
 *
 *}

{if $enableLanguageToggle}

<div class="block" id="sidebarLanguageToggle">
	<script type="text/javascript">
		<!--
		{* Edited by Shamil K.*}
		function changeLanguage(lang) {ldelim}
			//var e = document.getElementById('languageSelect');
			var new_locale = lang; //e.options[e.selectedIndex].value;

			var redirect_url = '{url|escape:"javascript" page="user" op="setLocale" path="NEW_LOCALE" source=$smarty.server.REQUEST_URI escape=false}';
			redirect_url = redirect_url.replace("NEW_LOCALE", new_locale);

			window.location.href = redirect_url;
		{rdelim}
		//-->
	</script>
    {* Edited by Shamil K.*}
	<a class="icon" onclick="changeLanguage('en_US'); return false;">
	<img src="http://ojs.kpfu.ru/plugins/blocks/languageToggle/styles/en.png" alt="English" title="English" width="32" height="32"></a>

	<a class="icon" onclick="changeLanguage('ru_RU'); return false;">
	<img src="http://ojs.kpfu.ru/plugins/blocks/languageToggle/styles/ru.png" alt="Русский" title="Russian" width="32" height="32"></a>
</div>
{/if}
