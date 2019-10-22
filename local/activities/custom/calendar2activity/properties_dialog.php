<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<tr>
	<td align="right" width="40%"><span><?= GetMessage("PD_EVENTID") ?>:</span></td>
	<td width="60%">
		<?=CBPDocument::ShowParameterField("string", 'EventId', $arCurrentValues['EventId'], Array('size'=> 50))?>
	</td>
</tr>

<tr>
	<td align="right" width="40%"><span><?= GetMessage("PD_EVENTNAME") ?>:</span></td>
	<td width="60%">
		<?=CBPDocument::ShowParameterField("string", 'EventName', $arCurrentValues['EventName'], Array('size'=> 50))?>
	</td>
</tr>
<tr>
	<td align="right" width="40%"><span><?= GetMessage("PD_EVENTDESC") ?>:</span></td>
	<td width="60%">
		<?=CBPDocument::ShowParameterField("string", 'EventDesc', $arCurrentValues['EventDesc'], Array('size'=> 50))?>
	</td>
</tr>

<tr>
	<td align="right" width="40%"><span class="adm-required-field"><?= GetMessage("PD_DATEFROM") ?>:</span></td>
	<td width="60%">
		<?=CBPDocument::ShowParameterField("datetime", 'DateFrom', $arCurrentValues['DateFrom'])?>
	</td>
</tr>
<tr>
	<td align="right" width="40%"><span><?= GetMessage("PD_DATETO") ?>:</span></td>
	<td width="60%">
		<?=CBPDocument::ShowParameterField("datetime", 'DateTo', $arCurrentValues['DateTo'])?>
	</td>
</tr>
<tr>
	<td align="right" width="40%"><span><?= GetMessage("PD_REMIND") ?>:</span></td>
	<td width="60%">
		<?=CBPDocument::ShowParameterField("string", 'Remind', $arCurrentValues['Remind'], Array('size'=> 50))?>
	</td>
</tr>
<tr>
	<td align="right" width="40%"><span class="adm-required-field"><?= GetMessage("PD_EVENTUSER") ?>:</span></td>
	<td width="60%">
		<?=CBPDocument::ShowParameterField('user', 'EventUser', $arCurrentValues['EventUser'], Array('rows' => 1))?>
	</td>
</tr>
<tr>
	<td align="right" width="40%"><span><?= GetMessage("PD_CREATEICS") ?>:</span></td>
	<td width="60%">
		<?=CBPDocument::ShowParameterField("bool", 'CreateIcs', $arCurrentValues['CreateIcs'])?>
	</td>
</tr>