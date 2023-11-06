<?

require_once __DIR__ . '/../libs/COMMON_Fkt.php'; 


class KP184 extends IPSModule {
	private $logLevel = 3;
	private $logCnt = 0;
	private $enableIPSLogOutput = false;		
	/*
	7 = ALL 	: Alle Meldungen werden ungefiltert ausgegeben
	6 = TRACE 	: ausf�hrlicheres Debugging, Kommentare
	5 = DEBUG	: allgemeines Debugging (Auffinden von Fehlern)
	4 = INFO	: allgemeine Informationen (Programm gestartet, Programm beendet, Verbindung zu Host Foo aufgebaut, Verarbeitung dauerte SoUndSoviel Sekunden .)
	3 = WARN	: Auftreten einer unerwarteten Situation
	2 = ERROR	: Fehler (Ausnahme wurde abgefangen. Bearbeitung wurde alternativ fortgesetzt)
	1 = FATAL	: Kritischer Fehler, Programmabbruch
	0 = OFF		: Logging ist deaktiviert
	*/	
	
	public function __construct($InstanceID) {
		
		parent::__construct($InstanceID);		// Diese Zeile nicht löschen

		$this->logLevel = @$this->ReadPropertyInteger("LogLevel"); 
		$this->enableIPSLogOutput = @$this->ReadPropertyBoolean("EnableIPSLogOutput");	
		if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("Log-Level is %d", $this->logLevel)); }
	}	
    
    public function Create() {
		
		parent::Create();				//Never delete this line!

		$logMsg = sprintf("Create Modul '%s [%s]'...", IPS_GetName($this->InstanceID), $this->InstanceID);
		if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, $logMsg); }
		IPS_LogMessage(__CLASS__."_".__FUNCTION__, $logMsg);

		$logMsg = sprintf("KernelRunlevel '%s'", IPS_GetKernelRunlevel());
		if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, $logMsg); }	

		
		$this->ConnectParent("{9CF2A083-5E3B-DC50-5D01-D6C6AF3B3BCC}"); // Splitter

		$this->RegisterPropertyBoolean('AutoUpdate', false);
		$this->RegisterPropertyInteger("TimerInterval", 5000);		
		$this->RegisterPropertyInteger("LogLevel", 3);
		$this->RegisterPropertyBoolean('EnableIPSLogOutput', false);		
		
		//Profiles and Vars

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
		
		$profilNameVolt = "Volt.3f";
		$this->RegisterVariableProfile($profilNameVolt, "", "", " V", 0, 0, 0, 3, 2);
		
		$profilNameAmpere = "Ampere.3f";
		$this->RegisterVariableProfile($profilNameAmpere, "", "", " A", 0, 0, 0, 3, 2);
		
		$profilNameWatt = "Watt.2f";
		$this->RegisterVariableProfile($profilNameWatt, "", "", " W", 0, 0, 0, 2, 2);
		
		$this->RegisterVariableBoolean('State', 'State', "~Switch", 10);
		$this->RegisterVariableInteger ('Mode', 'Mode', $profilNameMode, 11);
		
		$this->RegisterVariableFloat('Voltage', 'Voltage', $profilNameVolt, 20);
		$this->RegisterVariableFloat('Ampere', 'Ampere', $profilNameAmpere, 21);
		$this->RegisterVariableFloat('Watt', 'Watt', $profilNameWatt, 22);
		
		$this->RegisterVariableInteger('CRCErrorCnt', 'CRC Error Cnt', "", 90);	
		$this->RegisterVariableInteger('LastUpdate', 'Last Update', "~UnixTimestamp", 99);	

		//Timers
		$this->RegisterTimer('Timer_AutoUpdate', 0, 'KP184_Timer_AutoUpdate($_IPS[\'TARGET\']);');

	}

	public function Destroy() {
		IPS_LogMessage(__CLASS__."_".__FUNCTION__, sprintf("Destroy Modul '%s' ...", $this->InstanceID));
		parent::Destroy();						//Never delete this line!
	}

	public function ApplyChanges(){

		parent::ApplyChanges();					//Never delete this line!

		$this->logLevel = $this->ReadPropertyInteger("LogLevel");
		if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Set Log-Level to %d", $this->logLevel)); }
	
		$this->RegisterMessage(0, IPS_KERNELSTARTED);		// wait until IPS is started, dataflow does not work until stated	
		$this->RegisterMessage($this->InstanceID, FM_CONNECT);
        $this->RegisterMessage($this->InstanceID, FM_DISCONNECT); 
		$this->RegisterMessage($this->InstanceID, IM_CONNECT);
        $this->RegisterMessage($this->InstanceID, IM_DISCONNECT);  
		
		if (IPS_GetKernelRunlevel() <> KR_READY) {	// check kernel ready, if not wait
			return;
		}
				
				
		$autoUpdate = $this->ReadPropertyBoolean("AutoUpdate");		
		if($autoUpdate) {
			$timerInterval = $this->ReadPropertyInteger("TimerInterval");
		} else {
			$timerInterval = 0;
		}
		$this->SetTimerInterval("Timer_AutoUpdate", $timerInterval);				

		if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Interval 'Timer_AutoUpdate' set to '%d' seconds", $timerInterval)); }
	}
	
	public function Timer_AutoUpdate() {
		if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, "called ..."); }
		$this->RequestVoltageCurrent();		
	}
	
	public function RequestVoltageCurrent() {
		$data = "\x01\x03\x03\x00\x00\x00\x8E\x45";
		if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, $data, 1); }
		$this->SendToSplitter(utf8_encode($data));
	}
	
	protected function SendToSplitter(string $payload){						
		if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, $payload, 1); }
		$result = $this->SendDataToParent(json_encode(Array("DataID" => "{9912F430-B787-A588-00A9-9E53B20D94D4}", "Buffer" => $payload))); // Interface GUI
		return $result;
	}
	
	public function ReceiveData($JSONString)	{
		$data = json_decode($JSONString);
		$rawDataBuffer = $data->Buffer;
		$rawDataBufferDecoded = utf8_decode($rawDataBuffer);
		
		if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, $rawDataBuffer, 1); }
		if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__ . " (UTF8 decoded)", $rawDataBufferDecoded, 1); }
		
		$rawDataLen = strlen($rawDataBufferDecoded);
		
		$rawData = substr($rawDataBufferDecoded, 0, -2);
		$rawCRC = substr($rawDataBufferDecoded, -2, 2);

		$calcCRC = $this->CRC16_ModBus($rawData);

		if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__ . " [.rawData.]", $rawData, 1); }
		if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__ . " [.rawDataLen.]", $rawDataLen); }
		if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__ . " [.rawCRC.]", $rawCRC, 1); }
		if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__ . " [.calcCRC.]", $calcCRC, 1); }
		
		if($rawCRC != $calcCRC) {
			if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, "CRC ERROR [".$this->String2Hex($rawCRC) . " <> " . $this->String2Hex($calcCRC) ."]"); }
			$this->SetValue('CRCErrorCnt', $this->GetValue('CRCErrorCnt')+1);
		} else {
		
			$byte_array = $this->ByteStr2ByteArray($rawDataBufferDecoded);
			
			$arrayLen = count($byte_array);		
			$checksum = array_slice($byte_array, -2, 2);
			$byte_array = array_slice($byte_array, 0, $arrayLen-2);
			
			$state = ($byte_array[3] & 0b00000001);
			$mode = ($byte_array[3] & 0b00000110);

			$mV = ($byte_array[5]<<16) + ($byte_array[6]<<8) + ($byte_array[7]);
			$mA = ($byte_array[8]<<16) + ($byte_array[9]<<8) + ($byte_array[10]);

			$volt = $mV/1000;
			$ampere = $mA/1000;
			$watt = $volt * $ampere;

			$this->SetValue('State', $state);
			$this->SetValue('Mode', $mode);
			$this->SetValue('Voltage', $volt);
			$this->SetValue('Ampere', $ampere);	
			$this->SetValue('Watt', $watt);		
			$this->SetValue('LastUpdate', time());	
		}
	}
		

	private function String2Hex(string $string) {
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
				$this->AddLog(__FUNCTION__, "FM_CONNECT ...", 0);
                //$this->RegisterParent();
                //if ($this->HasActiveParent())
                //    $this->IOChangeState(IS_ACTIVE);
                //else
                //    $this->IOChangeState(IS_INACTIVE);
                break;
            case FM_DISCONNECT:	//DM_DISCONNECT
				$this->AddLog(__FUNCTION__, "FM_DISCONNECT ...", 0);
                //$this->RegisterParent();
                //$this->IOChangeState(IS_INACTIVE);
                break;
			case IM_CONNECT:	//DM_CONNECT
				$this->AddLog(__FUNCTION__, "IM_CONNECT ...", 0);
				//$this->RegisterParent();
                break;
            case IM_DISCONNECT:	//DM_DISCONNECT
				$this->AddLog(__FUNCTION__, "IM_DISCONNECT ...", 0);
				//$this->RegisterParent();
                break;				
            case IM_CHANGESTATUS:
				$this->AddLog(__FUNCTION__, "IM_CHANGESTATUS ...", 0);
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
				if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("UnregisterMessage 'IM_CHANGESTATUS' for %d", $OldParentId)); }	
                $this->UnregisterMessage($OldParentId, IM_CHANGESTATUS);
			}
            if ($ParentId > 0) {
				if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("RegisterMessage 'IM_CHANGESTATUS' for %d", $ParentId)); }
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
	 * Wird ausgeführt wenn der Kernel hochgefahren wurde.
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
				$this->AddLog(__FUNCTION__ , "Variable profile type does not match for profile " . $Name, 0);
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
	protected function SetValue($Ident, $Value)	{

		if (IPS_GetKernelVersion() >= 5) {
			parent::SetValue($Ident, $Value);
		} else {
			SetValue($this->GetIDForIdent($Ident), $Value);
		}
	}	

	protected function AddLog($name, $daten, $format=0, $ipsLogOutput=false) {
		$this->logCnt++;
		$logSender = "[".__CLASS__."] - " . $name;
		if($this->logLevel >= LogLevel::DEBUG) {
			$logSender = sprintf("%02d-T%2d [%s] - %s", $this->logCnt, $_IPS['THREAD'], __CLASS__, $name);
		} 
		$this->SendDebug($logSender, $daten, $format); 	
	
		if($ipsLogOutput or $this->enableIPSLogOutput) {
			if($format == 0) {
				IPS_LogMessage($logSender, $daten);	
			} else {
				IPS_LogMessage($logSender, $this->String2Hex($daten));			
			}
		}
	}

}