

//////////////////
Protokoll Details
\\\\\\\\\\\\\\\\\\


All data returned by the device consists of measurements and configuration status, in 130-byte chunks. To my knowledge, it will never send any other data. All bytes below are displayed in hex format; every command is a single byte.

# Commands to send:
F0 - Request new data dump; this triggers a 130-byte response
F1 - (device control) Go to next screen
F2 - (device control) Rotate screen
F3 - (device control) Switch to next data group
F4 - (device control) Clear data group
Bx - (configuration) Set recording threshold to a value between 0.00 and 0.15 A (where 'x' in the byte is 4 bits representing the value after the decimal point, eg. B7 to set it to 0.07 A)
Cx - (configuration) Same as Bx, but for when you want to set it to a value between 0.16 and 0.30 A (16 subtracted from the value behind the decimal point, eg. 0.19 A == C3)
Dx - (configuration) Set device backlight level; 'x' must be between 0 and 5 (inclusive)
Ex - (configuration) Set screen timeout ('screensaver'); 'x' is in minutes and must be between 0 and 9 (inclusive), where 0 disables the screensaver

# Response format:
All byte offsets are in decimal, and inclusive. All values are big-endian and unsigned.
0   - 1   Start marker (always 0x0963)
2   - 3   Voltage (in mV, divide by 1000 to get V)
4   - 5   Amperage (in mA, divide by 1000 to get A)
6   - 9   Wattage (in mW, divide by 1000 to get W)
10  - 11  Temperature (in celsius)
12  - 13  Temperature (in fahrenheit)
14        Unknown (not used in app)
15        Currently selected data group
16  - 95  Array of main capacity data groups (where the first one, group 0, is the ephemeral one)
            -- for each data group: 4 bytes mAh, 4 bytes mWh
96  - 97  USB data line voltage (positive) in centivolts (divide by 100 to get V)
98  - 99  USB data line voltage (negative) in centivolts (divide by 100 to get V)
100       Charging mode; this is an enum, where 0 = unknown/standard, 1 = QC2.0, and presumably 2 = QC3.0 (but I haven't verified this)
101       Unknown (not used in app)
102 - 105 mAh from threshold-based recording
106 - 109 mWh from threshold-based recording
110 - 111 Currently configured threshold for recording
112 - 115 Duration of recording, in seconds since start
116       Recording active (1 if recording)
117       Unknown (not used in app)
118 - 119 Current screen timeout setting
120 - 121 Current backlight setting
122 - 125 Resistance in deci-ohms (divide by 10 to get ohms)
126       Unknown
127       Current screen (same order as on device)
128 - 129 Stop marker (always 0xfff1)


10.01.2019 09:00:20 | Register Variable | array(126) {
  [1]=>  int(1)				Voltage
  [2]=>  int(253)			Voltage
  [3]=>  int(0)				Ampere
  [4]=>  int(2)				Ampere
  [5]=>  int(0)				Watt
  [6]=>  int(0)				Watt
  [7]=>  int(0)				Watt
  [8]=>  int(10)			Watt
  [9]=>  int(0)				Temperature °C
  [10]=>  int(24)			Temperature °C
  [11]=>  int(0)			Temperature F
  [12]=>  int(76)			Temperature F
  [13]=>  int(0)			Currently selected data group
  [14]=>  int(2)			Currently selected data group
  [15]=>  int(0)			Array of main capacity data groups -- for each data group: 4 bytes mAh, 4 bytes mWh
  [16]=>  int(0)
  [17]=>  int(6)
  [18]=>  int(17)
  [19]=>  int(0)
  [20]=>  int(0)
  [21]=>  int(30)
  [22]=>  int(152)
  [23]=>  int(0)
  [24]=>  int(0)
  [25]=>  int(0)
  [26]=>  int(0)
  [27]=>  int(0)
  [28]=>  int(0)
  [29]=>  int(0)
  [30]=>  int(0)
  [31]=>  int(0)
  [32]=>  int(0)
  [33]=>  int(0)
  [34]=>  int(25)
  [35]=>  int(0)
  [36]=>  int(0)
  [37]=>  int(0)
  [38]=>  int(130)
  [39]=>  int(0)
  [40]=>  int(0)
  [41]=>  int(0)
  [42]=>  int(0)
  [43]=>  int(0)
  [44]=>  int(0)
  [45]=>  int(0)
  [46]=>  int(0)
  [47]=>  int(0)
  [48]=>  int(0)
  [49]=>  int(0)
  [50]=>  int(0)
  [51]=>  int(0)
  [52]=>  int(0)
  [53]=>  int(0)
  [54]=>  int(0)
  [55]=>  int(0)
  [56]=>  int(0)
  [57]=>  int(0)
  [58]=>  int(0)
  [59]=>  int(0)
  [60]=>  int(0)
  [61]=>  int(0)
  [62]=>  int(0)
  [63]=>  int(0)
  [64]=>  int(0)
  [65]=>  int(0)
  [66]=>  int(0)
  [67]=>  int(0)
  [68]=>  int(0)
  [69]=>  int(0)
  [70]=>  int(0)
  [71]=>  int(0)
  [72]=>  int(0)
  [73]=>  int(0)
  [74]=>  int(0)
  [75]=>  int(0)
  [76]=>  int(0)
  [77]=>  int(0)
  [78]=>  int(0)
  [79]=>  int(0)
  [80]=>  int(0)
  [81]=>  int(0)
  [82]=>  int(0)
  [83]=>  int(0)
  [84]=>  int(0)
  [85]=>  int(0)
  [86]=>  int(0)
  [87]=>  int(0)
  [88]=>  int(0)
  [89]=>  int(0)
  [90]=>  int(0)
  [91]=>  int(0)
  [92]=>  int(0)
  [93]=>  int(0)
  [94]=>  int(0)
  [95]=>  int(0)			USB data line voltage (positive)
  [96]=>  int(0)			USB data line voltage (positive)
  [97]=>  int(0)			USB data line voltage (negativ)
  [98]=>  int(0)			USB data line voltage (negativ)						
  [99]=>  int(0)			Charging mode
  [100]=>  int(0)			Charging mode
  [101]=>  int(0)			recordingThreshold_mAh
  [102]=>  int(0)			recordingThreshold_mAh
  [103]=>  int(0)			recordingThreshold_mAh
  [104]=>  int(8)			recordingThreshold_mAh
  [105]=>  int(0)			recordingThreshold_mWh
  [106]=>  int(0)			recordingThreshold_mWh
  [107]=>  int(0)			recordingThreshold_mWh
  [108]=>  int(43)			recordingThreshold_mWh
  [109]=>  int(0)			Currently configured recordingThreshold
  [110]=>  int(0)			Currently configured recordingThreshold
  [111]=>  int(0)			Duration of recording, in seconds since start
  [112]=>  int(0)			Duration of recording, in seconds since start
  [113]=>  int(63)			Duration of recording, in seconds since start
  [114]=>  int(218)			Duration of recording, in seconds since start
  [115]=>  int(0)			Recording active
  [116]=>  int(1)			Recording active
  [117]=>  int(0)			Current screen timeout setting
  [118]=>  int(8)			Current screen timeout setting
  [119]=>  int(0)			Current backlight setting
  [120]=>  int(3)			Current backlight setting
  [121]=>  int(0)			Resistance in deci-ohms (divide by 10 to get ohms)
  [122]=>  int(0)			Resistance in deci-ohms (divide by 10 to get ohms)
  [123]=>  int(99)			Resistance in deci-ohms (divide by 10 to get ohms)
  [124]=>  int(106)			Resistance in deci-ohms (divide by 10 to get ohms)
  [125]=>  int(0)			Unknown
  [126]=>  int(0)			Current screen (same order as on device)
}

?>