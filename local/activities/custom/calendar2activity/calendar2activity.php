<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Bizproc\FieldType;

class CBPCalendar2Activity
	extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		
		// Зададим начальные значения свойств действия при добавлении в шаблон
		// в конструкторе. В целом, заранее свойства декларировать не требуется.
		// По сути — это просто ключи массива.
		$this->arProperties = [
			'EventId' 		=> '',
			'EventName'		=> '',
			'EventDesc' 	=> '',
			'DateFrom' 		=> '',
			'DateTo' 		=> '',
			'Remind' 		=> '60',
			'RemindDate' 	=> '',
			'EventUser' 	=> '',
			'CreateIcs' 	=> '',
			'FileIcsPath' 	=> '',
			
			'FileIcsPath' 	=> '',
		];
		
		$this->SetPropertiesTypes(
		[
			'EventId' => [
				'Type' => FieldType::INT
			],
			'EventName' => [
				'Type' => FieldType::STRING
			],
			'EventDesc' => [
				'Type' => FieldType::STRING
			],
			'DateFrom' => [
				'Type' => FieldType::STRING
			],
			'DateTo' => [
				'Type' => FieldType::STRING
			],
			'Remind' => [
				'Type' => FieldType::INT
			],
			'RemindDate' => [
				'Type' => FieldType::STRING
			],
			'EventUser' => [
				'Type' => FieldType::USER
			],
			'CreateIcs' => [
				'Type' => FieldType::BOOL
			],
			'FileIcsPath' => [
				'Type' => FieldType::STRING
			],
		]);
	}

	public function Execute()
	{
		if (!CModule::IncludeModule("calendar"))
			return CBPActivityExecutionStatus::Closed;

		$rootActivity = $this->GetRootActivity();
		$documentId = $rootActivity->GetDocumentId();
		$documentService = $this->workflow->GetService("DocumentService");

		$fromTs = CCalendar::Timestamp($this->DateFrom);
		$toTs = $this->DateTo == '' ? $fromTs : CCalendar::Timestamp($this->DateTo);

		$arFields = array(
			"CAL_TYPE" => 'user',
		
			"DESCRIPTION" => $this->CalendarDesrc,
			"SKIP_TIME" => date('H:i', $fromTs) == '00:00' && date('H:i', $toTs) == '00:00',
			"IS_MEETING" => false,
			"RRULE" => false,
		);

		if ($fromTs == $toTs && !$arFields["SKIP_TIME"])
			$toTs += 3600 /* HOUR LENGTH*/;

		$arFields['DATE_FROM'] = CCalendar::Date($fromTs);
		$arFields['DATE_TO'] = CCalendar::Date($toTs);
		
		// Если указан ID события, то обновим время, иначе создадим новое
		if($this->EventId)
			$arFields["ID"] = intVal($this->EventId);
		else
		{
			$arFields['NAME'] = $this->EventName;
			$arFields['DESCRIPTION'] = $this->EventDesc;
		}
		
		AddMessage2Log($arFields, "arFields");

		$arCalendarUser = CBPHelper::ExtractUsers($this->EventUser, $documentId);
		foreach ($arCalendarUser as $calendarUser)
		{
			$arFields["CAL_TYPE"] = "user";
			$arFields["OWNER_ID"] = $calendarUser;

			CCalendar::SaveEvent(
				array(
					'arFields' => $arFields,
					'autoDetectSection' => true
				)
			);
		}

		// Создадим файл в формате ICS, если отмечена галочка
		if($this->CreateIcs == 'Y')
		{
			$this->FileIcsPath = CBPCalendar2Activity::CreateICSFile($documentId[2], $fromTs, $toTs, $this->EventName, $this->EventDesc);
			AddMessage2Log($this->FileIcsPath, "FileIcsPath");
		}
		
		// Вычислим дату напоминания, для отправки сообщения за указанное количество минут
		if($this->Remind)
		{
			$this->RemindDate = $fromTs - $this->Remind * 60 - time();
			AddMessage2Log($this->RemindDate, "RemindDate");
		}

		return CBPActivityExecutionStatus::Closed;
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = array();

		if (!array_key_exists("EventUser", $arTestProperties) || count($arTestProperties["EventUser"]) <= 0)
			$arErrors[] = array("code" => "NotExist", "parameter" => "EventUser", "message" => GetMessage("BPSNMA_EMPTY_CALENDARUSER"));
		 if (!array_key_exists("DateFrom", $arTestProperties) || $arTestProperties["DateFrom"] == '')
			$arErrors[] = array("code" => "NotExist", "parameter" => "DateFrom", "message" => GetMessage("BPSNMA_EMPTY_CALENDARFROM")); 

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "")
	{		
		if (! is_array($arCurrentValues)) {
			$arCurrentValues = [
				'EventId' 		=> '',
				'EventName'		=> '',
				'EventDesc' 	=> '',
				'DateFrom' 		=> '',
				'DateTo' 		=> '',
				'Remind' 		=> '60',
				'EventUser' 	=> '',
				'CreateIcs' 	=> 'N',
			];

			$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName(
				$arWorkflowTemplate, 
				$activityName
			);
			if (is_array($arCurrentActivity['Properties'])) {
				$arCurrentValues = array_merge(
					$arCurrentValues, 
					$arCurrentActivity['Properties']
				);
				$arCurrentValues['EventUser'] = CBPHelper::UsersArrayToString(
					$arCurrentValues['EventUser'], 
					$arWorkflowTemplate, 
					$documentType
				);
			}
		}

		$runtime = CBPRuntime::GetRuntime();
		return $runtime->ExecuteResourceFile(
			__FILE__, 
			"properties_dialog.php", 
			[
				"arCurrentValues" 	=> $arCurrentValues,
				"formName" 			=> $formName
			]
		);
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		$arErrors = [];
		
		$arProperties = [
			'EventId' 		=> $arCurrentValues['EventId'],
			'EventName'		=> $arCurrentValues['EventName'],
			'EventDesc' 	=> $arCurrentValues['EventDesc'],
			'DateFrom' 		=> $arCurrentValues['DateFrom'],
			'DateTo' 		=> $arCurrentValues['DateTo'],
			'Remind' 		=> $arCurrentValues['Remind'],
			'EventUser' 	=> CBPHelper::UsersStringToArray(
				$arCurrentValues['EventUser'],
				$documentType,
				$arErrors
			),
			'CreateIcs' 	=> $arCurrentValues['CreateIcs'],
		];

		// Проверим Все ли необходимые поля заполнены
		$arErrors = self::ValidateProperties($arProperties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		
		if (count($arErrors) > 0)
			return false;

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $arProperties;

		return true;
	}
	
	public static function CreateICSFile($activityId, $timestampStart, $timestampEnd, $eventName, $eventDesc)
	{
		$uid 		= md5(uniqid(mt_rand(), true)).'@test.ru';
		$pathFile 	= '/upload/activity/ics/event_'.$activityId.'.ics';
		
		$timeStart 	= gmdate('Ymd', $timestampStart).'T'. gmdate('His', $timestampStart) . "Z";
		$timeEnd 	= gmdate('Ymd', $timestampEnd).'T'. gmdate('His', $timestampEnd) . "Z";
		
		$content = "BEGIN:VCALENDAR".PHP_EOL.
		"VERSION:2.0".PHP_EOL.
		"PRODID:-//hacksw/handcal//NONSGML v1.0//EN".PHP_EOL.
		"BEGIN:VEVENT".PHP_EOL.
		"UID:" . $uid .PHP_EOL.
		"DTSTAMP:" . $timeStart .PHP_EOL.
		"DTSTART:" . $timeStart .PHP_EOL.
		"DTEND:" . $timeEnd .PHP_EOL.
		"SUMMARY:". $eventName .PHP_EOL.
		"DESCRIPTION:". $eventDesc .PHP_EOL.
		"END:VEVENT";
		
		$f = fopen ($_SERVER["DOCUMENT_ROOT"].$pathFile, "a+");
		fwrite ($f, $content);
		fclose($f);
		
		$http = (!empty($_SERVER["HTTPS"])) ? 'https' : 'http';

		return  $pathFile;
		//return  $http.'://'.$_SERVER["SERVER_NAME"].$pathFile;
	}
}