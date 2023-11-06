<?php

require_once __DIR__ . '/../libs/COMMON_Fkt.php'; 

	class TC66C extends IPSModule {

		private $logLevel = 3;
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


			$this->ConnectParent("{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}");

			$this->RegisterPropertyBoolean('AutoUpdate', false);
			$this->RegisterPropertyInteger("TimerInterval", 5000);		
			$this->RegisterPropertyInteger("LogLevel", 3);

			$this->RegisterTimer('Timer_AutoUpdate', 0, 'TC66C_Timer_AutoUpdate($_IPS[\'TARGET\']);');
			//$this->CreateVoltageCurrentMediaChart($this->InstanceID, 10) ;

		}
		
		public function Destroy() {
			IPS_LogMessage(__CLASS__."_".__FUNCTION__, sprintf("Destroy Modul '%s' ...", $this->InstanceID));
			parent::Destroy();						//Never delete this line!
		}

		public function ApplyChanges() {
			
			parent::ApplyChanges();					//Never delete this line!

			$this->logLevel = $this->ReadPropertyInteger("LogLevel");
			if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Set Log-Level to %d", $this->logLevel)); }
	

			$this->RegisterProfiles();
			$this->RegisterVariables();  

			$this->CreateVoltageCurrentMediaChart($this->InstanceID, 10) ;

			$autoUpdate = $this->ReadPropertyBoolean("AutoUpdate");		
			if($autoUpdate) {
				$timerInterval = $this->ReadPropertyInteger("TimerInterval");
			} else {
				$timerInterval = 0;
			}
			$this->SetUpdateInterval($timerInterval);			
		}

		protected function Send(string $data) {
			$currentStatus = $this->GetStatus();
			if($currentStatus == 102) {				//Instanz ist aktiv
				if($this->logLevel >= LogLevel::COMMUNICATION) { $this->AddLog(__FUNCTION__, sprintf("send '%s' to device ...", $data)); }
				$this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => $data)));
			} else {
				if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, "Instanz not activ"); }
			}
		}

		public function ReceiveData($JSONString) {
			SetValue($this->GetIDForIdent("LastDataReceived"), time()); 
			$data = json_decode($JSONString);
			$rawData = utf8_decode($data->Buffer);
			if($this->logLevel >= LogLevel::COMMUNICATION) { $this->AddLog(__FUNCTION__, $rawData, 1); }
			$this->ProcessData($rawData);
		}

		public function RequestData() {
			if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, "Request readings (getva) .."); }
			$this->Send("getva");
		}

		public function PreviousScreenPage() {
			if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, "Switch screen to 'Previous Page'"); }
			$this->Send("lastp");
		}
		
		public function NextScreenPage() {
			if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, "Switch screen to 'Next Page'"); }
			$this->Send("nextp");
		}
		
		public function RotateScreen() {
			if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, "Rotate screen"); }
			$this->Send("rotat");
		}	
		
		public function DeleteLoggedData() {

			if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, '  ..:: DELETE LOGGED DATA :: ..'); }
			$timerIntervalTemp = $this->GetTimerInterval("Timer_AutoUpdate");
			$this->SetTimerInterval("Timer_AutoUpdate", 0);
			if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, 'STOP "Timer_AutoUpdate" !'); }

			if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("InstanceID: %s", $this->InstanceID)); }

			$archiveControlID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0]; 
			if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf("Archiv Conrol ID: %s", $archiveControlID)); }

			$childrenIDs = IPS_GetChildrenIDs($this->InstanceID);
				foreach($childrenIDs as $childID) {
					if (IPS_GetObject($childID)["ObjectType"] == 2) {
					$loggingStatus = AC_GetLoggingStatus($archiveControlID, $childID);
					if($loggingStatus) {
						if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf('Logging Status for Variable "[%s] %s" is TRUE', $childID, IPS_GetName($childID))); }
						$result = AC_DeleteVariableData($archiveControlID, $childID, 0, time());
						if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf('%d Logged Values deleted for Variable "[%s] %s"', $result, $childID, IPS_GetName($childID))); }
						$result = AC_ReAggregateVariable($archiveControlID, $childID);
						if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf('Start Reaggregation for Variable "[%s] %s" [result: %b]', $childID, IPS_GetName($childID), $result)); }
						IPS_Sleep(150);
					} else {
						if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf('Logging Status for Variable "[%s] %s" is FALSE', $childID, IPS_GetName($childID))); }
					}
				} else {
					if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, sprintf('Object "[%s] %s" is no Variable', $childID, IPS_GetName($childID))); }	
				}
			}
			if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf('Restore Timer Interval for "Timer_AutoUpdate" to %d ms', $timerIntervalTemp)); }
			$this->SetTimerInterval("Timer_AutoUpdate", $timerIntervalTemp);
			if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, '  - - - :: LOGGED DATA DELETED :: - - - '); }
		}		

		public function Timer_AutoUpdate() {
			if($this->logLevel >= 5) { $this->AddLog(__FUNCTION__, "called ..."); }
			$this->RequestData();		
		}

		public function SetUpdateInterval(int $timerInterval) {
			if ($timerInterval == 0) {  
				if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, "Auto-Update stopped [TimerIntervall = 0]"); }	
			}else if ($timerInterval < 500) { 
				$timerInterval = 500; 
				if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Set Auto-Update Timer Intervall to %sms]", $timerInterval)); }	
			} else {
				if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, sprintf("Set Auto-Update Timer Intervall to %sms]", $timerInterval)); }
			}
			$this->SetTimerInterval("Timer_AutoUpdate", $timerInterval);	
		}


		protected function ProcessData($rawData) {
			$len = strlen($rawData);
			if($len == 192) {

				$rawData = base64_encode($rawData);
				$decodedData = $this->DecodeData($rawData);
				$byteArray = unpack('C*', $decodedData);
				
				$len = strlen($decodedData);
				if($len == 192) {

					$pac1 = array_slice($byteArray, 0, 64);
					$pac2 = array_slice($byteArray, 64, 64);
					$pac3 = array_slice($byteArray, 128, 64);

					if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("'pac1' Data: [%d] %s", count($pac1), $this->ByteArr2HexStr($pac1))); }
					if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("'pac2' Data: [%d] %s", count($pac2), $this->ByteArr2HexStr($pac2))); }
					if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("'pac3' Data: [%d] %s", count($pac3), $this->ByteArr2HexStr($pac3))); }

					// Process Data Block 1
					$pac1CRC_calc  = $this->CRC16Inverse(substr($decodedData, 0, 60));
					$result = $this->ValidatePacData("pac1", $pac1, $pac1CRC_calc);
					if($result) {
						$productName = $this->ExtractString($pac1, 4, 4);
						if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("extracted 'pac1 | Product Name' : %s", $productName)); }
						SetValue($this->GetIDForIdent("productName"), $productName);  

						$firmware = $this->ExtractString($pac1, 8, 4);
						if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("extracted 'pac1 | Firmware' : %s", $firmware)); }
						SetValue($this->GetIDForIdent("firmware"), $firmware);  

						$serialNumber = $this->ExtractInteger($pac1, 12, 1);
						if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("extracted 'pac1 | Serial Number' : %s", $serialNumber)); }
						SetValue($this->GetIDForIdent("serialNumber"), $serialNumber);  
	
						$numberOfRuns = $this->ExtractInteger($pac1, 44);
						if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("extracted 'pac1 | Number of runs' : %s", $numberOfRuns)); }
						SetValue($this->GetIDForIdent("numberOfRuns"), $numberOfRuns);  
	
						$voltage = $this->ExtractInteger($pac1, 48, 10000);
						if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("extracted 'pac1 | Voltage' : %s", $voltage)); }
						SetValue($this->GetIDForIdent("voltage"), $voltage);  

						$current  = $this->ExtractInteger($pac1, 52, 100000);
						if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("extracted 'pac1 | Current' : %s", $current)); }
						SetValue($this->GetIDForIdent("current"), $current);  

						$power  = $this->ExtractInteger($pac1, 56, 10000);
						if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("extracted 'pac1 | Power' : %s", $power)); }
						SetValue($this->GetIDForIdent("power"), $power);  

						SetValue($this->GetIDForIdent("DataBlockUpdateCntOK"), GetValue($this->GetIDForIdent("DataBlockUpdateCntOK")) + 1);  											
						if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, "Data-Block 1 Values successfully evaluated (V, A, W, ...)"); }

					} else {
						SetValue($this->GetIDForIdent("DataBlockUpdateCntFaild"), GetValue($this->GetIDForIdent("DataBlockUpdateCntFaild")) + 1);  
						if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, "'pac1' Data was NOT evaluated!"); }
					}

					// Process Data Block 2
					$pac2CRC_calc  = $this->CRC16Inverse(substr($decodedData, 64, 60));
					$result = $this->ValidatePacData("pac2", $pac2, $pac2CRC_calc);
					if($result) {
						$resistance = $this->ExtractInteger($pac2, 4, 10);
						if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("extracted 'pac2 | Resistance' : %s", $resistance)); }
						SetValue($this->GetIDForIdent("resistance"), $resistance);  

						$group0mAh = $this->ExtractInteger($pac2, 8);
						if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("extracted 'pac2 | Group0mAh' : %s", $group0mAh)); }
						SetValue($this->GetIDForIdent("group0mAh"), $group0mAh);  

						$group0mWh = $this->ExtractInteger($pac2, 12);
						if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("extracted 'pac2 | Group0mWh' : %s", $group0mWh)); }
						SetValue($this->GetIDForIdent("group0mWh"), $group0mWh);  

						$group1mAh = $this->ExtractInteger($pac2, 16);
						if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("extracted 'pac2 | Group1mAh' : %s", $group1mAh)); }
						SetValue($this->GetIDForIdent("group1mAh"), $group1mAh);  

						$group1mWh = $this->ExtractInteger($pac2, 20);
						if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("extracted 'pac2 | Group1mWh' : %s", $group1mWh)); }
						SetValue($this->GetIDForIdent("group1mWh"), $group1mWh);  

						$temperatureSign  = $this->ExtractInteger($pac2, 24);
						if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("extracted 'pac2 | TemperatureSign' : %s", $temperatureSign)); }
	
						$temperature  = $this->ExtractInteger($pac2, 28);
						if($temperatureSign == 1) { $temperature = $temperature * -1; }
						if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("extracted 'pac2 | Temperature' : %s", $temperature)); }
						SetValue($this->GetIDForIdent("temperature"), $temperature);  

						$voltage_USB_Dplus = $this->ExtractInteger($pac2, 32, 100);
						if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("extracted 'pac2 | voltage_USB_Dplus' : %s", $voltage_USB_Dplus)); }
						SetValue($this->GetIDForIdent("voltage_USB_Dplus"), $voltage_USB_Dplus); 

						$voltage_USB_Dminus = $this->ExtractInteger($pac2, 36, 100);
						if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("extracted 'pac2 | voltage_USB_Dminus' : %s", $voltage_USB_Dminus)); }
						SetValue($this->GetIDForIdent("voltage_USB_Dminus"), $voltage_USB_Dminus); 			
						
						$chargeModeID = $this->ExtractInteger($pac2, 40);
						if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("extracted 'pac2 | chargeModeID' : %s", $chargeModeID)); }
						SetValue($this->GetIDForIdent("chargeModeID"), $chargeModeID); 									

						$chargeModeName = $this->Decode_USB_DataLines($voltage_USB_Dplus, $voltage_USB_Dminus);
						if($this->logLevel >= LogLevel::TRACE) { $this->AddLog(__FUNCTION__, sprintf("extracted 'pac2 | chargeModeName' : %s", $chargeModeName)); }
						SetValue($this->GetIDForIdent("chargeModeName"), $chargeModeName); 						

						SetValue($this->GetIDForIdent("DataBlockUpdateCntOK"), GetValue($this->GetIDForIdent("DataBlockUpdateCntOK")) + 1);  
						if($this->logLevel >= LogLevel::INFO) { $this->AddLog(__FUNCTION__, "Data-Block 2 Values successfully evaluated (Res, mAh, mWh, Temp, D+/D-, ...)"); }

					} else {
						SetValue($this->GetIDForIdent("DataBlockUpdateCntFaild"), GetValue($this->GetIDForIdent("DataBlockUpdateCntFaild")) + 1); 
						if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, "'pac2' Data was NOT evaluated!"); }
					}

				} else {
					if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("192 bytes expected but %s bytes decoded", $len)); }
				}

			} else {
				if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("192 bytes expected but %s bytes received", $len)); }
			}

		}


		protected function ValidatePacData($dataBlockTxt, $pacDataArr, $pacCRC_calc) {
			$result = false;
			$pacTxt = $this->ExtractString($pacDataArr, 0, 4); 
			if($pacTxt == $dataBlockTxt) {
				$pacCRC  = $this->ExtractInteger($pacDataArr, 60);
				if($pacCRC == $pacCRC_calc) {
					$result = true;
					if($this->logLevel >= LogLevel::COMMUNICATION) {
						$logMsg = sprintf("'%s' CRC verification OK [%s == %s]", $dataBlockTxt, $pacCRC, $pacCRC_calc);
						$this->AddLog(__FUNCTION__, $logMsg, 0); 
					}					
				} else {
					$result = false;
					if($this->logLevel >= LogLevel::WARN) {
						$logMsg = sprintf("'%s' CRC verification failed [%s <> %s]", $dataBlockTxt, $pacCRC, $pacCRC_calc);
						$this->AddLog(__FUNCTION__, $logMsg, 0); 
					}
				}

			} else {
				$result = false;
				if($this->logLevel >= LogLevel::WARN) { $this->AddLog(__FUNCTION__, sprintf("Expected Data Block '%s' not found -> extracted '%s'", $dataBlockTxt, $pac1Txt)); }
			}
			return $result;
		}


		protected function DecodeData($rawData) {
			$key = array(0x58,0x21,0xfa,0x56,0x01,0xb2,0xf0,0x26,0x87,0xff,0x12,0x04,0x62,0x2a,0x4f,0xb0,0x86,0xf4,0x02,0x60,0x81,0x6f,0x9a,0x0b,0xa7,0xf1,0x06,0x61,0x9a,0xb8,0x72,0x88);
			$key = implode(array_map("chr", $key));
			return openssl_decrypt($rawData, "AES-256-ECB", $key, OPENSSL_ZERO_PADDING);
		}
	

		protected function ExtractString($byteArray, $first_byte, $len) {
			$txt = "";
			for($i=0; $i < $len; $i++) {
				$txt .= chr($byteArray[$first_byte + $i]);
			}
			return $txt;
		}
	
		protected function ExtractInteger($byteArray, $first_byte, $divider=1) {
			$byte1 = ($byteArray[$first_byte]) & 255;
			$byte2 = ($byteArray[$first_byte + 1]) & 255;
			$byte3 = ($byteArray[$first_byte + 2]) & 255;
			$byte4 = ($byteArray[$first_byte + 3]) & 255;
			return (($byte4<<32) + ($byte3<<16) + ($byte2<<8) + $byte1 ) / $divider;
		}


		protected function Decode_USB_DataLines($positive, $negative) {
			$modeTxt = "n.a.";
			$positive = round($positive, 1);
			$negative = round($negative, 1);

			if(($positive < 0.3) && ($negative < 0.3)) {
				$modeTxt = "DCP 1.5A";	
			} else if(($positive < 0.6) && ($negative < 0.2)) {
				$modeTxt = "Quick Charge 5V [D+ 0.6 | D- 0.0]";	
			} else if(($positive == 3.3) && ($negative < 0.6)) {
				$modeTxt = "Quick Charge 9V [D+ 3.3 | D- 0.6]";		
			} else if(($positive == 0.6) && ($negative == 0.6)) {
				$modeTxt = "Quick Charge 12V [D+ 0.6 | D- 0.6]";
			} else if(($positive == 3.3) && ($negative == 3.3)) {
				$modeTxt = "Quick Charge 20V [D+ 3.3 | D- 3.3]";								
			} else if(($positive == 2.0) && ($negative == 2.0)) {
				$modeTxt = "Apple 0.5A [D+ 2.0 | D- 2.0]";	
			} else if(($positive == 2.0) && ($negative == 2.7)) {
				$modeTxt = "Apple 1.0A [D+ 2.0 | D- 2.7]";									
			} else if(($positive == 2.7) && ($negative == 2.0)) {
				$modeTxt = "Apple 2.1A [D+ 2.7 | D- 2.0]";		
			} else if(($positive == 2.7) && ($negative == 2.7)) {
				$modeTxt = "Apple 2.4A [D+ 2.7 | D- 2.7]";		
			} else if(($positive == 1.7) && ($negative == 1.7)) {
				$modeTxt = "Samsung 0.9A [D+ 1.7 | D- 1.7]";	
			} else if(($positive == 0.8) && ($negative == 0.8)) {
				$modeTxt = "_QC2.0 5V";																							
			} else {
				$modeTxt = "Unknown";
			}
			return $modeTxt;
		}

		protected function CRC16Inverse($buffer) {
			$result = 0xFFFF;
			if ( ($length = strlen($buffer)) > 0) {
				for ($offset = 0; $offset < $length; $offset++) {
					$result ^= ord($buffer[$offset]);
					for ($bitwise = 0; $bitwise < 8; $bitwise++) {
						$lowBit = $result & 0x0001;
						$result >>= 1;
						if ($lowBit) $result ^= 0xA001;
					}
				}
			}
			return $result;
		}
		
		protected function String2Hex($string) {
			$hex='';
			for ($i=0; $i < strlen($string); $i++){
				//$hex .= dechex(ord($string[$i]));
				$hex .= "0x" . sprintf("%02X", ord($string[$i])) . " ";
			}
			return trim($hex);
		}

		protected function ByteArr2HexStr($arr) {
			$hex_str = "";
			foreach ($arr as $byte) {
				$hex_str .= sprintf("%02X ", $byte);
			}
			return $hex_str;
		}

		protected function RegisterProfiles() {

            if ( !IPS_VariableProfileExists('TC66_Voltage.2') ) {
                IPS_CreateVariableProfile('TC66_Voltage.2', 2 );
                IPS_SetVariableProfileDigits('TC66_Voltage.2', 2 );
				IPS_SetVariableProfileText('TC66_Voltage.2', "", " V" );
				IPS_SetVariableProfileValues("TC66_Voltage.2", 0, 5, 0);
			} 

            if ( !IPS_VariableProfileExists('TC66_Voltage.3') ) {
                IPS_CreateVariableProfile('TC66_Voltage.3', 2 );
                IPS_SetVariableProfileDigits('TC66_Voltage.3', 3 );
				IPS_SetVariableProfileText('TC66_Voltage.3', "", " V" );
				IPS_SetVariableProfileValues("TC66_Voltage.3", 0, 25, 0);
			} 			
			
            if ( !IPS_VariableProfileExists('TC66_Ampere.3') ) {
                IPS_CreateVariableProfile('TC66_Ampere.3', 2 );
                IPS_SetVariableProfileDigits('TC66_Ampere.3', 3 );
				IPS_SetVariableProfileText('TC66_Ampere.3', "", " A" );
				IPS_SetVariableProfileValues("TC66_Ampere.3", 0, 5, 0);
			} 
			
            if ( !IPS_VariableProfileExists('TC66_Power.3') ) {
                IPS_CreateVariableProfile('TC66_Power.3', 2 );
                IPS_SetVariableProfileDigits('TC66_Power.3', 3 );
				IPS_SetVariableProfileText('TC66_Power.3', "", " W" );
				IPS_SetVariableProfileValues("TC66_Power.3", 0, 120, 0);
			} 		
			
            if ( !IPS_VariableProfileExists('TC66_Resistance.1') ) {
				IPS_CreateVariableProfile('TC66_Resistance.1', 2 );
				IPS_SetVariableProfileDigits('TC66_Resistance.1', 1 );
				IPS_SetVariableProfileText('TC66_Resistance.1', "", " Ohm" );
				IPS_SetVariableProfileValues("TC66_Resistance.1", 0, 10000, 0);
			} 	
			
            if ( !IPS_VariableProfileExists('TC66_mAh') ) {
                IPS_CreateVariableProfile('TC66_mAh', 1 );
				IPS_SetVariableProfileText('TC66_mAh', "", " mAh" );
				IPS_SetVariableProfileValues("TC66_mAh", 0, 100000, 0);
			} 
			
            if ( !IPS_VariableProfileExists('TC66_mWh') ) {
                IPS_CreateVariableProfile('TC66_mWh', 1 );
				IPS_SetVariableProfileText('TC66_mWh', "", " mWh" );
				IPS_SetVariableProfileValues("TC66_mWh", 0, 100000, 0);
            } 			
			
            if ( !IPS_VariableProfileExists('TC66_Temp') ) {
                IPS_CreateVariableProfile('TC66_Temp', 1 );
				IPS_SetVariableProfileText('TC66_Temp', "", " °C" );
				IPS_SetVariableProfileValues("TC66_Temp", 0, 45, 0);
			} 
			
            if ( !IPS_VariableProfileExists('TC66_ChargeMode') ) {
                IPS_CreateVariableProfile('TC66_ChargeMode', 1 );
				IPS_SetVariableProfileText('TC66_ChargeMode', "", "" );
				IPS_SetVariableProfileAssociation ('TC66_ChargeMode', 0, "[%d] Unknown", "", -1);
				IPS_SetVariableProfileAssociation ('TC66_ChargeMode', 1, "[%d] QC2.0", "", -1);
				IPS_SetVariableProfileAssociation ('TC66_ChargeMode', 2, "[%d] QC3.0", "", -1);
				IPS_SetVariableProfileAssociation ('TC66_ChargeMode', 3, "[%d] APP2.4A", "", -1);
				IPS_SetVariableProfileAssociation ('TC66_ChargeMode', 4, "[%d] APP2.1A", "", -1);
				IPS_SetVariableProfileAssociation ('TC66_ChargeMode', 5, "[%d] APP1.0A", "", -1);
				IPS_SetVariableProfileAssociation ('TC66_ChargeMode', 6, "[%d] APP0.5A", "", -1);
				IPS_SetVariableProfileAssociation ('TC66_ChargeMode', 7, "[%d] DCP1.5A", "", -1);
				IPS_SetVariableProfileAssociation ('TC66_ChargeMode', 8, "[%d] SAMSUNG", "", -1);
				IPS_SetVariableProfileAssociation ('TC66_ChargeMode', 65535, "[%d] Unknown", "", -1);

            } 


			if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, "Variable Profiles registered"); }
		}

		protected function RegisterVariables() {

			$archivInstanzID = IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0];

			$this->RegisterVariableString("productName", "Product Name", "", 100);
			$this->RegisterVariableString("firmware", "Firmware", "", 101);
			$this->RegisterVariableInteger("serialNumber", "Serial Number", "", 102);
			$this->RegisterVariableInteger("numberOfRuns", "Number of runs", "", 103);
			
			$varId = $this->RegisterVariableFloat("voltage", "Voltage", "TC66_Voltage.3", 110);
			AC_SetLoggingStatus ($archivInstanzID, $varId, true);

			$varId = $this->RegisterVariableFloat("current", "Current", "TC66_Ampere.3", 120);
			AC_SetLoggingStatus ($archivInstanzID, $varId, true);

			$varId =  $this->RegisterVariableFloat("power", "Power", "TC66_Power.3", 130);
			AC_SetLoggingStatus ($archivInstanzID, $varId, true);

			$this->RegisterVariableFloat("resistance", "Resistance", "TC66_Resistance.1", 200);
			
			$varId =  $this->RegisterVariableInteger("group0mAh", "Group0mAh", "TC66_mAh", 210);
			AC_SetLoggingStatus ($archivInstanzID, $varId, true);
			
			$varId =  $this->RegisterVariableInteger("group0mWh", "Group0mWh", "TC66_mWh", 220);
			AC_SetLoggingStatus ($archivInstanzID, $varId, true);

			$this->RegisterVariableInteger("group1mAh", "Group1mAh", "TC66_mAh", 230);
			$this->RegisterVariableInteger("group1mWh", "Group1mWh", "TC66_mWh", 240);
			$this->RegisterVariableInteger("temperature", "Temperature", "TC66_Temp", 250);
			
			$varId = $this->RegisterVariableFloat("voltage_USB_Dplus", "USB Data Line Voltage +", "TC66_Voltage.2", 260);
			AC_SetLoggingStatus ($archivInstanzID, $varId, true);

			$varId = $this->RegisterVariableFloat("voltage_USB_Dminus", "USB Data Line Voltage -", "TC66_Voltage.2", 270);
			AC_SetLoggingStatus ($archivInstanzID, $varId, true);

			$varId = $this->RegisterVariableInteger("chargeModeID", "Charge Mode ID", "TC66_ChargeMode", 280);
			AC_SetLoggingStatus ($archivInstanzID, $varId, true);

			$this->RegisterVariableString("chargeModeName", "Charge Mode Name", "", 281);
			$this->RegisterVariableInteger("DataBlockUpdateCntOK", "Data Blocks Updates OK", "", 900);
			$this->RegisterVariableInteger("DataBlockUpdateCntFaild", "Data Blocks Updates FAILED", "", 910);
			$this->RegisterVariableInteger("LastDataReceived", "Last Data Received", "~UnixTimestamp", 920);

			IPS_ApplyChanges($archivInstanzID);

			if($this->logLevel >= LogLevel::DEBUG) { $this->AddLog(__FUNCTION__, "20 variables were registered"); }

		}

		protected function CreateVoltageCurrentMediaChart($parentID, $position) {

			$chart = [
				"datasets" => [
					 [
						 "variableID"=> 33412,
						 "fillColor"=> "clear",
						 "strokeColor"=> "#3023e1",
						 "timeOffset"=> 0,
						 "axis"=> 0
					 ], [
						 "variableID"=> 53568,
						 "fillColor"=> "clear",
						 "strokeColor"=> "#e30d0d",
						 "timeOffset"=> 0,
						 "axis"=> 1
					 ], [
						 "variableID"=> 59573,
						 "fillColor"=> "clear",
						 "strokeColor"=> "#e9e932",
						 "timeOffset"=> 0,
						 "axis"=> 0
					 ], [
						 "variableID"=> 46826,
						 "fillColor"=> "clear",
						 "strokeColor"=> "#e9e932",
						 "timeOffset"=> 0,
						 "axis"=> 0
					 ]
				],
			   "type"=>"line",
			   "axes"  => [
					 [
						 "profile" => "TC66_Voltage.3",
						 "side" => "left"
					 ], [
						 "profile" => "TC66_Ampere.3",
						 "side" => "right"
					 ]
				 ]
			];
			$chartJSON = json_encode($chart);
			$mediaID = $this->CreateMediaChart("Voltage/Current Chart", $chartJSON, $position, $parentID);
		}

		protected function CreateMediaChart($chartName, $chartJSON, $position, $parentID) {
			$mediaID = @IPS_GetObjectIDByName($chartName, $parentID); 
			if ($mediaID === false){ 
				$mediaID = IPS_CreateMedia(4); 
				IPS_SetParent($mediaID, $parentID);
				$media = IPS_GetKernelDir().join(DIRECTORY_SEPARATOR, array("media", "".$mediaID.".chart")); 
				IPS_SetPosition($mediaID, $position); 
				IPS_SetMediaCached($mediaID, false); 
				IPS_SetName($mediaID, $chartName); 
				IPS_SetIcon($mediaID,"Graph");

				IPS_SetMediaFile($mediaID, $media, false); 
				IPS_SetMediaContent($mediaID, base64_encode($chartJSON)); 
				IPS_SendMediaEvent($mediaID); 				
			}
			return $mediaID;
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