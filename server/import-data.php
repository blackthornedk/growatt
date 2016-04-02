<?php

$datadir = '/var/lib/growatt/data';
$sqlhost = 'localhost';
$sqluser = 'solar';
$sqlpass = 'BrVxvA46';
$sqldb   = 'solar';

$inverter_id = null;

$mysqli = new mysqli($sqlhost, $sqluser, $sqlpass, $sqldb);
if ($mysqli->connect_errno) {
	echo "Failed to connect to MySQL\n";
	die();
}

if ($handle = opendir($datadir)) {
	while (false !== ($entry = readdir($handle))) {
		if ($entry != "." && $entry != "..") {
			$file = "{$datadir}/{$entry}";
			if (!($strFile = file_get_contents($file))) continue;
			// don't enter empty readings to DB.
			if (sizeof($strFile) == 0) continue;
			
			preg_match('/data-([\d]+)\.txt/', $entry, $time);
			preg_match('/Model: (.*) Serial: ([^\s]+) Firmware: ([^\s]+)/',		$strFile, $model);
			
			if (sizeof($model) < 3 || strlen($model[2]) == 0 || sizeof($time) < 2 || strlen($time[1]) == 0) continue;
			
			$str_serial = $mysqli->escape_string($model[2]);
			$str_time = $mysqli->escape_string($time[1]);

			// Check if Inverter is created, otherwise add it
			$res = $mysqli->query("SELECT `id` FROM `inverter` WHERE `serial` = '{$str_serial}';");
			if ($res->num_rows == 0) {
				preg_match('/Rating max: ([^\s]+)W Vdc: ([^\s]+)V/(', $strFile, $rating);
				$str_model = $mysqli->escape_string($model[1]);
				$str_firmware = $mysqli->escape_string($model[3]);
				$str_rating_max = $mysqli->escape_string($rating[1]);
				$str_vdc_max = $mysqli->escape_string($rating[2]);
				if (!$mysqli->query("INSERT INTO `inverter` (`type`, `model`, `serial`, `firmware`, `rating_max`, `vdc_max`) VALUES ('Growatt', '{$str_model}', '{$str_serial}', '{$str_firmware}', '{$str_rating_max}', '{$str_vdc_max}');")) {
					// Could not add inverter, funny data?
					continue;
				}
			}

			$res = $mysqli->query("SELECT COUNT(*) FROM `reading` WHERE `inverter_id` = (SELECT `id` FROM `inverter` WHERE `serial` = '{$str_serial}') AND `time` = FROM_UNIXTIME({$str_time});");
			$row = $res->fetch_array(MYSQLI_NUM);

			// Data already in DB.
			if ($row[0] > 0) continue;
			echo "File: $file\n";
			
			preg_match('/Status: ([^\s]+) \(Fault type: ([^\s]+)\)/',		$strFile, $status);
			preg_match('/Temperature: ([^\s]+) degC/',				$strFile, $temperature);
			preg_match('/PV1: ([^\s]+)V PV2: ([^\s]+)V/',				$strFile, $pv);
			preg_match('/Input: ([^\s]+)W/',					$strFile, $input);
			preg_match('/Grid Voltage: ([^\s]+)V Freq: ([^\s]+)Hz/',		$strFile, $grid);
			preg_match('/Output: ([^\s]+)W ([^\s]+)A/',				$strFile, $output);
			preg_match('/Energy Today: ([^\s]+)kWh/',				$strFile, $today);
			preg_match('/Energy Total: ([^\s]+)kWh Time Total: ([^\s]+)hrs/',	$strFile, $total);

			$str_status		= $mysqli->escape_string($status[1]);
			$str_fault_type		= $mysqli->escape_string($status[2]);
			$str_temperature	= $mysqli->escape_string($temperature[1]);
			$str_pv1		= $mysqli->escape_string($pv[1]);
			$str_pv2		= $mysqli->escape_string($pv[2]);
			$str_input		= $mysqli->escape_string($input[1]);
			$str_grid_volt		= $mysqli->escape_string($grid[1]);
			$str_frequency		= $mysqli->escape_string($grid[2]);
			$str_output		= $mysqli->escape_string($output[1]);
			$str_amp		= $mysqli->escape_string($output[2]);
			$str_today		= $mysqli->escape_string($today[1]);
			$str_total_energy	= $mysqli->escape_string($total[1]);
			$str_total_time		= $mysqli->escape_string($total[2]);

			$mysqli->query("INSERT INTO `reading` (`inverter_id`, `time`, `status`, `fault_type`, `temperature`, `pv1`, `pv2`, `input`, `grid_volt`, `frequency`, `output`, `amp`, `today`, `total_energy`, `total_time`) VALUES ((SELECT `id` FROM `inverter` WHERE `serial` = '{$str_serial}'), FROM_UNIXTIME({$str_time}), '{$str_status}', '{$str_fault_type}', '{$str_temperature}', '{$str_pv1}', '{$str_pv2}', '{$str_input}', '{$str_grid_volt}', '{$str_frequency}', '{$str_output}', '{$str_amp}', '{$str_today}', '{$str_total_energy}', '{$str_total_time}');");
		}
	}
}
