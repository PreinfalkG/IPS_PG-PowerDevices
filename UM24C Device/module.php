<?

class UM24C extends IPSModule
{
	private $debugLevel = 3;
	private $enableIPSLogOutput = false;
	/*
	7 = ALL 	: Alle Meldungen werden ungefiltert ausgegeben
	6 = TRACE 	: ausführlicheres Debugging, Kommentare
	5 = DEBUG	: allgemeines Debugging (Auffinden von Fehlern)
	4 = INFO	: allgemeine Informationen (Programm gestartet, Programm beendet, Verbindung zu Host Foo aufgebaut, Verarbeitung dauerte SoUndSoviel Sekunden .)
	3 = WARN	: Auftreten einer unerwarteten Situation
	2 = ERROR	: Fehler (Ausnahme wurde abgefangen. Bearbeitung wurde alternativ fortgesetzt)
	1 = FATAL	: Kritischer Fehler, Programmabbruch
	0 = OFF		: Logging ist deaktiviert
	*/	
	
	public function __construct($InstanceID) {
		
		parent::__construct($InstanceID);		// Diese Zeile nicht löschen

		$currentStatus = $this->GetStatus();
		if($currentStatus == 102) {				//Instanz ist aktiv
			$this->debugLevel = $this->ReadPropertyInteger("DebugLevel");
			$this->enableIPSLogOutput = $this->ReadPropertyBoolean("EnableIPSLogOutput");	
		} else {
			if($this->debugLevel >= 4) { $this->AddDebugLogEntry(__FUNCTION__, sprintf("Current Status is '%s'", $currentStatus), 0); }	
		}

		if($this->debugLevel >= 6) { $this->AddDebugLogEntry(__FUNCTION__, "called ...", 0); }
		if($this->debugLevel >= 6) { $this->AddDebugLogEntry(__FUNCTION__, sprintf("Debug Level is '%d'", $this->debugLevel), 0); }	
		if($this->debugLevel >= 6) { $this->AddDebugLogEntry(__FUNCTION__, sprintf("EnableIPSLogOutput is '%d'", $this->enableIPSLogOutput), 0); }
	}	
    
    public function Create() {
		
        parent::Create();	// Diese Zeile nicht löschen
		
		if($this->debugLevel >= 6) { $this->AddDebugLogEntry(__FUNCTION__, "called ...", 0); }
		
		$this->ConnectParent("{C89DF982-32A6-386E-3A86-5AD89741DB2C}"); // Splitter

		$this->RegisterPropertyBoolean('AutoUpdate', false);
		$this->RegisterPropertyInteger("TimerInterval", 5000);		
		$this->RegisterPropertyInteger("DebugLevel", 3);
		$this->RegisterPropertyBoolean('EnableIPSLogOutput', false);		
		
		//Profiles and Vars

/*
		$profilNameMode = "KP184.Mode";
		$this->RegisterVariableProfile($profilNameMode, "", "", "", -1, -1, 0, 0, 1); 	//$Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits, $Vartype
		IPS_SetVariableProfileAssociation ($profilNameMode, 0, "CV [%d]", "", -1);
		IPS_SetVariableProfileAssociation ($profilNameMode, 1, "? [%d]", "", -1);
		IPS_SetVariableProfileAssociation ($profilNameMode, 2, "CC [%d]", "", -1);
		IPS_SetVariableProfileAssociation ($profilNameMode, 3, "? [%d]", "", -1);
		IPS_SetVariableProfileAssociation ($profilNameMode, 4, "CR [%d]", "", -1);
		IPS_SetVariableProfileAssociation ($profilNameMode, 5, "? [%d]", "", -1);
		IPS_SetVariableProfileAssociation ($profilNameMode, 6, "CW [%d]", "", -1);
		IPS_SetVariableProfileAssociation ($profilNameMode, 7, "? [%d]", "", -1);
*/		
		$profilNameVolt = "Volt.3f";
		$this->RegisterVariableProfile($profilNameVolt, "", "", " V", 0, 0, 0, 3, 2);
		
		$profilNameAmpere = "Ampere.3f";
		$this->RegisterVariableProfile($profilNameAmpere, "", "", " A", 0, 0, 0, 3, 2);
		
		$profilNameWatt = "Watt.3f";
		$this->RegisterVariableProfile($profilNameWatt, "", "", " W", 0, 0, 0, 3, 2);
		
		$profilName_mAh = "Energie.mAh.3f";
		$this->RegisterVariableProfile($profilName_mAh, "", "", " mAh", 0, 0, 0, 0, 1);
		
		$profilName_mWh = "Energie.mWh.3f";
		$this->RegisterVariableProfile($profilName_mWh, "", "", " mWh", 0, 0, 0, 0, 1);		
		
		$profilNameDuration = "Duration.Minutes";
		$this->RegisterVariableProfile($profilNameDuration, "", "", " min", 0, 0, 0, 0, 1);	
		
		$profilNameResistance = "Resistance.1f";
		$this->RegisterVariableProfile($profilNameResistance, "", "", " Ohm", 0, 0, 0, 0, 2);	
		
		//Measured Values
		$this->RegisterVariableFloat('Voltage', 'Voltage', $profilNameVolt, 100);
		$this->RegisterVariableFloat('Ampere', 'Ampere', $profilNameAmpere, 101);
		$this->RegisterVariableFloat('Watt', 'Watt', $profilNameWatt, 102);
		$this->RegisterVariableFloat('Temp', 'Temp', "~Temperature", 103);
		$this->RegisterVariableInteger('SelDataGroup', 'Selected Data Group', "", 104);
		$this->RegisterVariableFloat('VoltageUSB_Minus', 'USB Data Line Voltage +', $profilNameVolt, 105);
		$this->RegisterVariableFloat('VoltageUSB_Plus', 'USB Data Line Voltage -', $profilNameVolt, 106);
		
		//Charging & Recording
		$this->RegisterVariableInteger('ChargingMode', 'Selected Data Group', "", 200);
		$this->RegisterVariableInteger('Recorded_mAh', 'Recorded [mAh]', $profilName_mAh, 201);
		$this->RegisterVariableInteger('Recorded_mWh', 'Recorded [mWh]', $profilName_mWh, 202);
		$this->RegisterVariableFloat('RecordingThreshold', 'Recording Threshold', $profilNameAmpere, 203);
		$this->RegisterVariableInteger('RecordingDuration', 'Recording Duration', "~UnixTimestampTime", 204);
		$this->RegisterVariableBoolean('RecordingActive', 'Recording Active', "~Switch", 205);
		
		//Settings
		$this->RegisterVariableInteger('ScreenTimeout', 'Screen Timeout', $profilNameDuration, 300);
		$this->RegisterVariableInteger('BacklightSetting', 'Backlight Setting', "", 301);
		$this->RegisterVariableFloat('Resistance', "Resistance", $profilNameResistance, 302);
		$this->RegisterVariableInteger('CurrentScreen', 'Current Screen', "", 303);		
		
		
		//Update Info
		$this->RegisterVariableInteger('UpdateCnt', 'Update Cnt', "", 990);	
		$this->RegisterVariableInteger('CRCErrorCnt', 'CRC Error Cnt', "", 991);	
		$this->RegisterVariableInteger('LastUpdate', 'Last Update', "~UnixTimestamp", 999);	

		//Timers
		$this->RegisterTimer('Timer_AutoUpdate', 0, 'UM24C_Timer_AutoUpdate($_IPS[\'TARGET\']);');

	}

	public function Destroy() {
		parent::Destroy();			//Never delete this line!
		if($this->debugLevel >= 6) { $this->AddDebugLogEntry(__FUNCTION__, "called ...", 0); }
	}

	public function ApplyChanges()	{

		if($this->debugLevel >= 6) { $this->AddDebugLogEntry(__FUNCTION__, "called ...", 0); }
				
		$this->RegisterMessage(0, IPS_KERNELSTARTED);		// wait until IPS is started, dataflow does not work until stated	
		$this->RegisterMessage($this->InstanceID, FM_CONNECT);
        $this->RegisterMessage($this->InstanceID, FM_DISCONNECT); 
		$this->RegisterMessage($this->InstanceID, IM_CONNECT);
        $this->RegisterMessage($this->InstanceID, IM_DISCONNECT);  
		
		parent::ApplyChanges();	// Diese Zeile nicht löschen
		if (IPS_GetKernelRunlevel() <> KR_READY) {	// check kernel ready, if not wait
			return;
		}
				
		$this->debugLevel = $this->ReadPropertyInteger("DebugLevel");
		$this->AddDebugLogEntry(__FUNCTION__, sprintf("INFO :: Set Debug Level  to %d", $this->debugLevel), 0);
		
		$this->enableIPSLogOutput = $this->ReadPropertyBoolean("EnableIPSLogOutput");	
		$this->AddDebugLogEntry(__FUNCTION__, sprintf("INFO :: Set IPS-Log-Output  to %b", $this->enableIPSLogOutput), 0);
		
		$autoUpdate = $this->ReadPropertyBoolean("AutoUpdate");		
		if($autoUpdate) {
			$timerInterval = $this->ReadPropertyInteger("TimerInterval");
		} else {
			$timerInterval = 0;
		}
		$this->SetTimerInterval("Timer_AutoUpdate", $timerInterval);				

		if($this->debugLevel >= 4) { $this->AddDebugLogEntry(__FUNCTION__, sprintf("Interval 'Timer_AutoUpdate' set to '%d' seconds", $timerInterval), 0); }
	}
	
	public function Timer_AutoUpdate() {
		if($this->debugLevel >= 6) { $this->AddDebugLogEntry(__FUNCTION__, "called ...", 0); }
		$this->RequestData();		
	}
	
	public function RequestData() {
		$data = "\xf0";
		if($this->debugLevel >= 6) { $this->AddDebugLogEntry(__FUNCTION__, $data, 1); }
		$this->SendToSplitter(utf8_encode($data));
	}
	
	protected function SendToSplitter(string $payload)
	{						
		if($this->debugLevel >= 5) { $this->AddDebugLogEntry(__FUNCTION__, $payload, 1); }
		$result = $this->SendDataToParent(json_encode(Array("DataID" => "{4C47F019-D24D-9A2E-A66F-3A7E14CB383E}", "Buffer" => $payload))); // Interface GUI
		return $result;
	}
	
	public function ReceiveData($JSONString)
	{
		$data = json_decode($JSONString);
		$rawDataBuffer = $data->Buffer;
		$rawDataBufferDecoded = utf8_decode($rawDataBuffer);
		
		if($this->debugLevel >= 5) { $this->AddDebugLogEntry(__FUNCTION__, $rawDataBuffer, 1); }
		if($this->debugLevel >= 5) { $this->AddDebugLogEntry(__FUNCTION__ . " (UTF8 decoded)", $rawDataBufferDecoded, 1); }
		
		$rawDataLen = strlen($rawDataBufferDecoded);
		
		$rawData = substr($rawDataBufferDecoded, 0, -2);
		$rawCRC = substr($rawDataBufferDecoded, -2, 2);

		$calcCRC = $this->CRC16_ModBus($rawData);

		if($this->debugLevel >= 6) { $this->AddDebugLogEntry(__FUNCTION__ . " [.rawData.]", $rawData, 1); }
		if($this->debugLevel >= 6) { $this->AddDebugLogEntry(__FUNCTION__ . " [.rawDataLen.]", $rawDataLen, 0); }
		if($this->debugLevel >= 6) { $this->AddDebugLogEntry(__FUNCTION__ . " [.rawCRC.]", $rawCRC, 1); }
		if($this->debugLevel >= 6) { $this->AddDebugLogEntry(__FUNCTION__ . " [.calcCRC.]", $calcCRC, 1); }
		
		
		//if($rawCRC != $calcCRC) {
		if(1 != 1) {
			if($this->debugLevel >= 3) { $this->AddDebugLogEntry(__FUNCTION__, "CRC ERROR [".$this->String2Hex($rawCRC) . " <> " . $this->String2Hex($calcCRC) ."]", 0); }
			$this->SetValue('CRCErrorCnt', $this->GetValue('CRCErrorCnt')+1);
		} else {
		
			$byte_array = unpack('C*', $rawData);
		
			/*
			if ($debug) {
			
				//var_dump($byte_array);
				//var_dump($byte_array[126]);
				$rawData = array_slice($byte_array, 110, 4);
				$hex_str = "";
				foreach ($rawData as $byte) {
					$hex_str .= sprintf("%02X-", $byte);
				}
				$hex_str .= sprintf("[%02X]", $byte_array[114]);
				SetValue(20934, $hex_str);
			}
			*/
		
		
			$voltage = (($byte_array[3]<<8) + ($byte_array[4]));
			$ampere = (($byte_array[5]<<8) + ($byte_array[6]));
			$watt = (($byte_array[7]<<32) + ($byte_array[8])<<16) + (($byte_array[9]<<8) + ($byte_array[10]));
			$tempC = (($byte_array[11]<<8) + ($byte_array[12]));
			$tempF = (($byte_array[13]<<8) + ($byte_array[14]));
			$selectedDataGroup =  (($byte_array[15]<<8) + ($byte_array[16]));
			//$byte_array[15] - $byte_array[94] --> Array of main capacity data groups -- for each data group: 4 bytes mAh, 4 bytes mWh
			$dataLineVoltagePositive = (($byte_array[97]<<8) + ($byte_array[98]));
			$dataLineVoltageNegative = (($byte_array[99]<<8) + ($byte_array[100]));
			$chargingMode = (($byte_array[101]<<8) + ($byte_array[102]));
			$recordingThreshold_mAh = (($byte_array[103]<<32) + ($byte_array[104])<<16) + (($byte_array[105]<<8) + ($byte_array[106]));
			$recordingThreshold_mWh = (($byte_array[107]<<32) + ($byte_array[108])<<16) + (($byte_array[109]<<8) + ($byte_array[110]));
			$recordingThreshold = (($byte_array[111]<<32) + ($byte_array[112])); 
			$recordingDuration = (($byte_array[113]<<32) + ($byte_array[114])<<16) + (($byte_array[115]<<8) + ($byte_array[116]));	
			$recordingActive = (($byte_array[117]<<8) + ($byte_array[118]));
			$screenTimeout = (($byte_array[119]<<8) + ($byte_array[120])); 
			$backlightSetting = (($byte_array[121]<<8) + ($byte_array[122])); 
			$resistance = (($byte_array[123]<<32) + ($byte_array[124])<<16) + (($byte_array[125]<<8) + ($byte_array[126]));
			$currentScreen = (($byte_array[127]<<8) + ($byte_array[128])); 

			//Measured Values
			$this->SetValue('Voltage', $voltage/100);
			$this->SetValue('Ampere', $ampere/1000);	
			$this->SetValue('Watt', $watt/1000);	
			$this->SetValue('Temp', $tempC);
			$this->SetValue('SelDataGroup', $selectedDataGroup);
			$this->SetValue('VoltageUSB_Minus', $dataLineVoltageNegative/100);
			$this->SetValue('VoltageUSB_Plus', $dataLineVoltagePositive/100);

			//Charging & Recording
			$this->SetValue('ChargingMode', $chargingMode);
			$this->SetValue('Recorded_mAh', $recordingThreshold_mAh);			
			$this->SetValue('Recorded_mWh', $recordingThreshold_mWh);
			$this->SetValue('RecordingThreshold', $recordingThreshold/100);
			$this->SetValue('RecordingDuration', $recordingDuration-3600);
			$this->SetValue('RecordingActive', $recordingActive);

			//Settings
			$this->SetValue('ScreenTimeout', $screenTimeout);
			$this->SetValue('BacklightSetting', $backlightSetting);			
			$this->SetValue('Resistance', $resistance/10);
			$this->SetValue('CurrentScreen', $currentScreen);

		
			//Update Info
			$this->SetValue('UpdateCnt', $this->GetValue('UpdateCnt')+1);
			$this->SetValue('LastUpdate', time());			
		}
	}
		
	private function AddDebugLogEntry($name, $daten, $format) {
		$this->SendDebug("[" . __CLASS__ . "] - " . $name, $daten, $format); 	

		if($this->enableIPSLogOutput) {
			if($format == 0) {
				IPS_LogMessage("[" . __CLASS__ . "] - " . $name, $daten);	
			} else {
				IPS_LogMessage("[" . __CLASS__ . "] - " . $name, $this->String2Hex($daten));			
			}
		}
	}
	
	private function String2Hex($string) {
		$hex='';
		for ($i=0; $i < strlen($string); $i++){
			//$hex .= dechex(ord($string[$i]));
			$hex .= "0x" . sprintf("%02X", ord($string[$i])) . " ";
		}
		return trim($hex);
	}
	
	private function ByteStr2ByteArray($s) {
		return array_slice(unpack("C*", "\0".$s), 1);
	}	
		
	// Calculate CRC16 (ModBus)
	private function CRC16_ModBus($data) {
		$crc = 0xFFFF;
		for ($i = 0; $i < strlen($data); $i++)
		{
			$crc ^=ord($data[$i]);
     		for ($j = 8; $j !=0; $j--)
			{
				if (($crc & 0x0001) !=0)
				{
					$crc >>= 1;
					$crc ^= 0xA001;
				}
				else
				$crc >>= 1;
			}		
		}
		$highCrc=floor($crc/256);
		$lowCrc=($crc-$highCrc*256);
		return chr($highCrc).chr($lowCrc);
	}	
	
	
	public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
		switch ($Message) {
			case IPS_KERNELSTARTED: 	// only after IP-Symcon started
				$this->KernelReady(); 	// if IP-Symcon is ready
				break;
				
			case FM_CONNECT:	//DM_CONNECT
				$this->AddDebugLogEntry(__FUNCTION__, "FM_CONNECT ...", 0);
                //$this->RegisterParent();
                //if ($this->HasActiveParent())
                //    $this->IOChangeState(IS_ACTIVE);
                //else
                //    $this->IOChangeState(IS_INACTIVE);
                break;
            case FM_DISCONNECT:	//DM_DISCONNECT
				$this->AddDebugLogEntry(__FUNCTION__, "FM_DISCONNECT ...", 0);
                //$this->RegisterParent();
                //$this->IOChangeState(IS_INACTIVE);
                break;
			case IM_CONNECT:	//DM_CONNECT
				$this->AddDebugLogEntry(__FUNCTION__, "IM_CONNECT ...", 0);
				//$this->RegisterParent();
                break;
            case IM_DISCONNECT:	//DM_DISCONNECT
				$this->AddDebugLogEntry(__FUNCTION__, "IM_DISCONNECT ...", 0);
				//$this->RegisterParent();
                break;				
            case IM_CHANGESTATUS:
				$this->AddDebugLogEntry(__FUNCTION__, "IM_CHANGESTATUS ...", 0);
                //if ($SenderID == $this->ParentID)
                //    $this->IOChangeState($Data[0]);
                break;				
				
				
		}
	}

    protected function RegisterParent() {
        $OldParentId = $this->GetBuffer('ParentID');
        $ParentId = @IPS_GetInstance($this->InstanceID)['ConnectionID'];
        if ($ParentId <> $OldParentId) {
            if ($OldParentId > 0) {
				if($this->debugLevel >= 4) { $this->AddDebugLogEntry(__FUNCTION__, sprintf("UnregisterMessage 'IM_CHANGESTATUS' for %d", $OldParentId), 0); }	
                $this->UnregisterMessage($OldParentId, IM_CHANGESTATUS);
			}
            if ($ParentId > 0) {
				if($this->debugLevel >= 4) { $this->AddDebugLogEntry(__FUNCTION__, sprintf("RegisterMessage 'IM_CHANGESTATUS' for %d", $ParentId), 0); }
                $this->RegisterMessage($ParentId, IM_CHANGESTATUS);
			}  else {
                $ParentId = 0;
			}
            
			//$this->SetBuffer('ParentID') = $ParentId;
			$this->SetBuffer('ParentID', $ParentId);
        }
        return $ParentId;
    } 
	
    protected function HasActiveParent() {
        $instance = IPS_GetInstance($this->InstanceID);
        if ($instance['ConnectionID'] > 0)
        {
            $parent = IPS_GetInstance($instance['ConnectionID']);
            if ($parent['InstanceStatus'] == 102)
                return true;
        }
        return false;
    }  

    protected function IOChangeState($State) {
        // Wenn der IO Aktiv wurde
        if ($State == IS_ACTIVE)
        {
            //$this->startCommunication();  / TODO
        }
        else // und wenn nicht setzen wir uns auf inactive
        {
            $this->SetStatus(IS_INACTIVE);
        }
    }  	


	/**
	 * Wird ausgefÃ¼hrt wenn der Kernel hochgefahren wurde.
	 * @access protected
	 */
	protected function KernelReady()
	{
		$this->ApplyChanges();
	}
	
	//Profile
	protected function RegisterVariableProfile($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits, $Vartype)
	{

		if (!IPS_VariableProfileExists($Name)) {
			IPS_CreateVariableProfile($Name, $Vartype); // 0 boolean, 1 int, 2 float, 3 string,
		} else {
			$profile = IPS_GetVariableProfile($Name);
			if ($profile['ProfileType'] != $Vartype)
				$this->AddDebugLogEntry(__FUNCTION__ , "Variable profile type does not match for profile " . $Name, 0);
		}

		IPS_SetVariableProfileIcon($Name, $Icon);
		IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
		IPS_SetVariableProfileDigits($Name, $Digits); //  Nachkommastellen
		IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize); // string $ProfilName, float $Minimalwert, float $Maximalwert, float $Schrittweite
	}

	protected function RegisterProfileAssociation($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits, $Vartype, $Associations)
	{
		if (sizeof($Associations) === 0) {
			$MinValue = 0;
			$MaxValue = 0;
		}

		$this->RegisterProfile($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits, $Vartype);

		//boolean IPS_SetVariableProfileAssociation ( string $ProfilName, float $Wert, string $Name, string $Icon, integer $Farbe )
		foreach ($Associations as $Association) {
			IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
		}

	}

	
	/***********************************************************
	 * Migrations
	 ***********************************************************/

	/**
	 * Polyfill for IP-Symcon 4.4 and older
	 * @param string $Ident
	 * @param mixed $Value
	 */
	//Add this Polyfill for IP-Symcon 4.4 and older
	protected function SetValue($Ident, $Value)
	{

		if (IPS_GetKernelVersion() >= 5) {
			parent::SetValue($Ident, $Value);
		} else {
			SetValue($this->GetIDForIdent($Ident), $Value);
		}
	}	
}