{*+*************************************************************************************
 * The contents of this file are subject to the VTECRM License Agreement
 * ("licenza.txt"); You may not use this file except in compliance with the License
 * The Original Code is: VTECRM
 * The Initial Developer of the Original Code is VTECRM LTD.
 * Portions created by VTECRM LTD are Copyright (C) VTECRM LTD.
 * All Rights Reserved.
 ***************************************************************************************}

{* crmv@56023 *}

<table width="100%" border="0">
	<tr>
		<td align="left">
			{if $LIST_ENTRIES neq ''}
				{$RECORD_COUNTS}
			{/if}
		</td>
		{$NAVIGATION}
	</tr>
</table>

<table class="vtetable">
	<thead>
		<tr>
			{foreach item=header from=$LIST_HEADER}
				<th>{$header}</th>
			{/foreach}
		</tr>
	</thead>
	<tbody>
		{foreach item=entity key=entity_id from=$LIST_ENTRIES}
			<tr>
				{foreach item=data from=$entity}
					<td>{$data}</td>
				{/foreach}
			</tr>
		{foreachelse}
			<tr>
				<td colspan="{$LIST_HEADER|@count}" height="300px" align="center" class="genHeaderSmall">{$MOD.LBL_NO_DATA}</td>
			</tr>
		{/foreach}
	</tbody>
</table>
