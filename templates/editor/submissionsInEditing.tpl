{**
 * templates/editor/submissionsInEditing.tpl
 *
 * Copyright (c) 2013-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show editor's submissions in editing.
 *
 *}
<div id="submissions">
<table width="100%" class="listing">
	<tr>
		<td colspan="10" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="5%">{sort_search key="common.id" sort="id"}</td>
		<td width="5%"><span class="disabled">{translate key="submission.date.mmdd"}</span><br />{sort_heading key="submissions.submit" sort="submitDate"}</td>
		<td width="5%">{sort_search key="submissions.sec" sort="section"}</td>
		<td width="15%">{sort_search key="article.authors" sort="authors"}</td>
		<td width="25%">{sort_search key="article.title" sort="title"}</td>
		<td width="8%">{sort_search key="submission.copyedit" sort="subCopyedit"}</td>
		<td width="8%">{sort_search key="submission.layout" sort="subLayout"}</td>
		<td width="8%">{sort_search key="submissions.proof" sort="subProof"}</td>
		<td width="5%">{translate key="article.sectionEditor"}</td>
		<td width="10%">{sort_search key="issue.issue"}</td>
	</tr>
	<tr>
		<td colspan="10" class="headseparator">&nbsp;</td>
	</tr>
	
	{iterate from=submissions item=submission}
	{assign var="layoutSignoff" value=$submission->getSignoff('SIGNOFF_LAYOUT')}
	{assign var="layoutEditorProofSignoff" value=$submission->getSignoff('SIGNOFF_PROOFREADING_LAYOUT')}
	{assign var="copyeditorFinalSignoff" value=$submission->getSignoff('SIGNOFF_COPYEDITING_FINAL')}
	{assign var="highlightClass" value=$submission->getHighlightClass()}
	{assign var="fastTracked" value=$submission->getFastTracked()}
	{assign var='publishedArticle' value=$publishedArticleDao->getPublishedArticleByArticleId($submission->getId())}
	{if $publishedArticle}
		{assign var='issueId' value=$publishedArticle->getIssueId()}
		{assign var='issue' value=$issueDao->getIssueById($issueId)}
		{assign var='issueTitle' value=$issue->getIssueIdentification(true)}
	{else}
		{assign var='issueTitle' value=-101}
	{/if}
	<tr valign="top"{if $highlightClass || $fastTracked} class="{$highlightClass|escape} {if $fastTracked}fastTracked{/if}"{/if} {if $issueTitle!=-101} style="color:#006100;background:#c6efce;"{/if}>
		<td>{$submission->getId()}</td>
		<td>{$submission->getDateSubmitted()|date_format:$dateFormatTrunc}</td>
		<td>{$submission->getSectionAbbrev()|escape}</td>
		<td>{$submission->getAuthorString(true)|truncate:40:"..."|escape}</td>
		<td><a href="{url op="submissionEditing" path=$submission->getId()}" class="action">{$submission->getLocalizedTitle()|strip_tags|truncate:40:"..."}</a></td>
		<td>{if $copyeditorFinalSignoff->getDateCompleted()}{$copyeditorFinalSignoff->getDateCompleted()|date_format:$dateFormatTrunc}{else}&mdash;{/if}</td>
		<td>{if $layoutSignoff->getDateCompleted()}{$layoutSignoff->getDateCompleted()|date_format:$dateFormatTrunc}{else}&mdash;{/if}</td>
		<td>{if $layoutEditorProofSignoff->getDateCompleted()}{$layoutEditorProofSignoff->getDateCompleted()|date_format:$dateFormatTrunc}{else}&mdash;{/if}</td>
		<td>
			{assign var="editAssignments" value=$submission->getEditAssignments()}
			{foreach from=$editAssignments item=editAssignment}{$editAssignment->getEditorInitials()|escape} {/foreach}
		</td>
		<td>
			{if $issueTitle!=-101}
				<a href='{url op="issueToc" path=$issue->getId()}' class="action">{$issueTitle|strip_unsafe_html|nl2br}</a>
			{else}&mdash;{/if}
		</td>
	</tr>
	<tr>
		<td colspan="10" class="{if $submissions->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $submissions->wasEmpty()}
	<tr>
		<td colspan="10" class="nodata">{translate key="submissions.noSubmissions"}</td>
	</tr>
	<tr>
		<td colspan="10" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="5" align="left">{page_info iterator=$submissions}</td>
		<td colspan="4" align="right">{page_links anchor="submissions" name="submissions" iterator=$submissions searchField=$searchField searchMatch=$searchMatch search=$search dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateFromMonth=$dateFromMonth dateToDay=$dateToDay dateToYear=$dateToYear dateToMonth=$dateToMonth dateSearchField=$dateSearchField section=$section sort=$sort sortDirection=$sortDirection}</td>
	</tr>
{/if}
</table>
</div>

