{**
 * templates/sectionEditor/submission/management.tpl
 *
 * Copyright (c) 2013-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the submission management table.
 * Edited Shamil K. 04.05.2018
 *}

<div id="submission">
<h3>{translate key="article.submission"}</h3>

{assign var="submissionFile" value=$submission->getSubmissionFile()}
{assign var="suppFiles" value=$submission->getSuppFiles()}
{assign var="status" value=$submission->getSubmissionStatus()}

{if $status==STATUS_QUEUED_UNASSIGNED}
<script type="text/javascript">
{literal}
document.addEventListener("DOMContentLoaded", 
  function(e) {
    var target = document.querySelector('#archiveinfo');
    var rng, sel;
    if (document.createRange) {
      rng = document.createRange();
      rng.selectNode(target)
      sel = window.getSelection();
      sel.removeAllRanges();
      sel.addRange(rng);
    } else {
      var rng = document.body.createTextRange();
      rng.moveToElementText(target);
      rng.select();
    }
  });
document.addEventListener("DOMContentLoaded", 
  function(e) {
	console.log('copy success', document.execCommand('copy'));
  });
function copyToClip() {
var copyText = document.getElementById('authorSurs');
copyText.select();
document.execCommand("Copy");
}
{/literal}
</script>
{/if}

<table width="100%" class="data">
{if $status==STATUS_QUEUED_UNASSIGNED}
	<tr>
		<td width="20%" class="label">{translate key="plugins.block.ljm.archiveinfo"}</td>
		<td width='80%'><table width="100%" id='archiveinfo'><tr><td width='10%'>{$submission->getAuthorString()|escape}</td>
		<td width='10%'>{$submission->getLocalizedTitle()|strip_unsafe_html}</td>
		<td width='10%'>{assign var=fauthor value=$submission->getFirstAuthorDAO()}{$fauthor->getCountryLocalized()|escape|default:"&mdash;"}</td>
		<td width='1%'></td>
		<td width='10%'>{$submission->getDateSubmitted()|date_format:$dateFormatShort|strip_unsafe_html|nl2br}</td>
		<td width='1%'></td>
		<td width='1%'></td>
		<td width='15%'>{$submission->getCommentsToEditor()|strip_unsafe_html|nl2br}</tr></table></td>
	</tr>
{/if}
	<tr>
		<td width="20%" class="label">{translate key="article.authors"}</td>
		<td width="80%" colspan="2" class="value">
			{url|assign:"url" page="user" op="email" redirectUrl=$currentUrl to=$submission->getAuthorEmails() subject=$submission->getLocalizedTitle() articleId=$submission->getId()}
			{$submission->getAuthorString()|escape} {icon name="mail" url=$url}
		</td>
	</tr>
	<tr>
		<td class="label">{translate key="article.title"}</td>
		<td colspan="2" class="value">{$submission->getLocalizedTitle()|strip_unsafe_html}</td>
	</tr>
	<tr>
		<td class="label">{translate key="submission.originalFile"}</td>
		<td colspan="2" class="value">
			{if $submissionFile}
				<a href="{url op="viewFile" path=$submission->getId()|to_array:$submissionFile->getFileId()}" class="file" target="_blank">{$submissionFile->getFileName()|escape}</a>&nbsp;&nbsp;{$submissionFile->getDateModified()|date_format:$dateFormatShort}
			{else}
				{translate key="common.none"}
			{/if} (<span id='authorSurs'>{$submission->getAuthorString(true)|escape}</span>)
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="article.suppFilesAbbrev"}</td>
		<td colspan="2" class="value">
			{foreach name="suppFiles" from=$suppFiles item=suppFile}
				{assign var=needOrig value=0}
				{if $suppFile->getFileId()}
					{if $suppFile->getFileType() eq 'application/octet-stream'}{assign var=needOrig value=1}
				{/if}
					<a href="{url op="downloadFile" path=$submission->getId()|to_array:$suppFile->getFileId()}" class="file">{if $needOrig==1}{$suppFile->getOriginalFileName()|escape}{else }{$suppFile->getFileName()|escape}{/if}{*edited getFileName to getOriginalFileName Shamil K.*}</a>
					&nbsp;&nbsp;
				{elseif $suppFile->getRemoteURL() != ''}
					<a href="{$suppFile->getRemoteURL()|escape}" target="_blank">{$suppFile->getRemoteURL()|truncate:20:"..."|escape}</a>
					&nbsp;&nbsp;
				{/if}
				{if $suppFile->getDateModified()}
					{$suppFile->getDateModified()|date_format:$dateFormatShort}&nbsp;&nbsp;
				{else}
					{$suppFile->getDateSubmitted()|date_format:$dateFormatShort}&nbsp;&nbsp;
				{/if}
				<a href="{url op="editSuppFile" from="submission" path=$submission->getId()|to_array:$suppFile->getId()}" class="action">{translate key="common.edit"}</a>
				&nbsp;|&nbsp;
				<a href="{url op="deleteSuppFile" from="submission" path=$submission->getId()|to_array:$suppFile->getId()}" onclick="return confirm('{translate|escape:"jsparam" key="author.submit.confirmDeleteSuppFile"}')" class="action">{translate key="common.delete"}</a>
				{if !$notFirst}
					&nbsp;&nbsp;&nbsp;&nbsp;
					<a href="{url op="addSuppFile" from="submission" path=$submission->getId()}" class="action">{translate key="submission.addSuppFile"}</a>
				{/if}
				<br />
				{assign var=notFirst value=1}
			{foreachelse}
				{translate key="common.none"}&nbsp;&nbsp;&nbsp;&nbsp;<a href="{url op="addSuppFile" from="submission" path=$submission->getId()}" class="action">{translate key="submission.addSuppFile"}</a>
			{/foreach}
		</td>
	</tr>
	<tr>
		<td class="label">{translate key="submission.submitter"}</td>
		<td colspan="2" class="value">
			{assign var="submitter" value=$submission->getUser()}
			{assign var=emailString value=$submitter->getFullName()|concat:" <":$submitter->getEmail():">"}
			{url|assign:"url" page="user" op="email" redirectUrl=$currentUrl to=$emailString|to_array subject=$submission->getLocalizedTitle|strip_tags articleId=$submission->getId()}
			{$submitter->getFullName()|escape} {icon name="mail" url=$url}
		</td>
	</tr>
	<tr>
		<td class="label">{translate key="common.dateSubmitted"}</td>
		<td>{$submission->getDateSubmitted()|date_format:$dateFormatShort}</td>
	</tr>
	<tr>
		<td class="label">{translate key="section.section"}</td>
		<td class="value">{$submission->getSectionTitle()|escape}</td>
		<td class="value"><form action="{url op="updateSection" path=$submission->getId()}" method="post">{translate key="submission.changeSection"} <select name="section" size="1" class="selectMenu">{html_options options=$sections selected=$submission->getSectionId()}</select> <input type="submit" value="{translate key="common.record"}" class="button" /></form></td>
	</tr>
	{if $submission->getCommentsToEditor()}
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.commentsToEditor"}</td>
		<td width="80%" colspan="2" class="data">{$submission->getCommentsToEditor()|strip_unsafe_html|nl2br}</td>
	</tr>
	{/if}
	{if $publishedArticle}
	<tr>
		<td class="label">{translate key="submission.abstractViews"}</td>
		<td>{$publishedArticle->getViews()}</td>
	</tr>
	{/if}
</table>
</div>

