<?

require_once __DIR__ . '/../libs/COMMON_Fkt.php'; 

class UM24CSplitter extends IPSModule
{
	private $logLevel = 4;
	private $logCnt = 0;
	private $enableIPSLogOutput = false;		

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

		
		$this->RegisterPropertyInteger("LogLevel", 4);
		$this->RegisterPropertyBoolean('EnableIPSLogOutput', false);			
		
		//Vars
		$this->RegisterVariableString("BufferIN", "BufferIN", "", -4);	
		
		//These lines are parsed on Symcon Startup or Instance creation
        //You cannot use variables here. Just static values.
		$this->RequireParent("{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}"); //  I/O
    }
	
	public function Destroy() {
		IPS_LogMessage(__CLASS__."_".__FUNCTION__, sprintf("Destroy Modul '%s' ...", $this->InstanceID));
		parent::Destroy();						//Never delete this line!
	}

    public function ApplyChanges() {
	
		parent::ApplyChanges();					//Never delete this line!

		$this->logLevel = $this->ReadPropertyInteger("LogLevel");
		if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Set Log-Level to %d", $this->logLevel)); }
	      	   
    }
    
    // Type String, Declaration can be used when PHP 7 is available
    // public function ReceiveData($JSONString)
	public function ReceiveData($JSONString) {	 
		// Empfangene Daten vom I/O
		$data = json_decode($JSONString);
		if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, $data->Buffer, 1); }
		if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__ . " (UTF8 decoded)", utf8_decode($data->Buffer), 1); }
		
		$bufferID = $this->GetIDForIdent("BufferIN");
        $bufferIN = GetValueString($bufferID);
		
		$bufferIN = $bufferIN . $data->Buffer;		
		if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__ . "-BufferIN", $bufferIN, 1); }
		
		if($this->startsWith($bufferIN,"\x09\x63")) {
			$len = strlen($bufferIN);
			if($len < 130) {
				SetValueString($bufferID, $bufferIN);
			} else {
				SetValueString($bufferID, '');			
				if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__ . ">SendDataToChildren", $bufferIN, 1); }
				
				// Weiterleitung zu allen Ger�t-/Device-Instanzen
				$this->SendDataToChildren(json_encode(Array("DataID" => "{0693ED7E-D00E-2073-B745-7C90BD5E2A55}", "Buffer" => $bufferIN))); // Splitter Interface GUI
			}
		} else {
			if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, "Warning - Buffer CLEAR"); }
			SetValueString($bufferID, '');
		}
		
		/* -----------------		
		DEFAULT Weiterleitung zu allen Ger�t-/Device-Instanzen
		$this->SendDataToChildren(json_encode(Array("DataID" => "{0693ED7E-D00E-2073-B745-7C90BD5E2A55}", "Buffer" => $data->Buffer))); // Splitter Interface GUI				
		----------------- */
		
	}
	
	// Type String, Declaration can be used when PHP 7 is available
    //public function ForwardData($JSONString)
    public function ForwardData($JSONString) {
		// Empfangene Daten von der Device Instanz
		$data = json_decode($JSONString);
		if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__ . ">SendDataToParent", $data->Buffer, 1); }
		
		// Weiterleiten zur I/O Instanz
		$result = $this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => $data->Buffer))); // TX GUI
		return $result;	 
	}
	
	private function startsWith($haystack, $needle) {
		return strpos($haystack, $needle) === 0;
	}
	

	private function String2Hex($string){
		$hex='';
		for ($i=0; $i < strlen($string); $i++){
			//$hex .= dechex(ord($string[$i]));
			$hex .= "0x" . sprintf("%02X", ord($string[$i])) . " ";
		}
		return $hex;
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