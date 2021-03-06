<?php
/*
Name: Netatmo PHP Widget
URI: https://www.potsky.com/code/netatmo/
Description: A PHP Widget to display weather information of several locations and Netatmo modules
Version: 0.5.8
Date: 2014-08-31
Author: potsky
Author URI: http://www.potsky.com/about/

Copyright © 2014 Raphael Barbate ( potsky ) <potsky@me.com> [http://www.potsky.com]
This file is part of Netatmo PHP Widget.

Netatmo PHP Widget is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License.

Netatmo PHP Widget is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Netatmo PHP Widget.  If not, see <http://www.gnu.org/licenses/>.
*/

// This include will generate an array named 'result' with formatted informations about your devices and external modules
require_once( 'inc' . DIRECTORY_SEPARATOR . 'global.inc.php' );

$text_wo_rain       = "In external module '_device_name_:_name_', on _human_date_ at _human_hour_, temperature is _temp_°C and humidity is _humi_%";
$text_wi_rain       = "In external module '_device_name_:_name_', on _human_date_ at _human_hour_, temperature is _temp_°C, humidity is _humi_% and _rain_mm of rain fell in 24h";
$text_wo_rainsensor = "No rain sensor";

if ( isset( $_GET['text_wo_rain'] ) )       $text_wo_rain       = $_GET['text_wo_rain'];
if ( isset( $_GET['text_wi_rain'] ) )       $text_wi_rain       = $_GET['text_wi_rain'];
if ( isset( $_GET['text_wo_rainsensor'] ) ) $text_wo_rainsensor = $_GET['text_wo_rainsensor'];

$display                     = ( isset( $_GET['a'] ) ) ? (int)$_GET['a'] : 0;
$without_rain_sensor_seconds = ( isset( $_GET['r'] ) ) ? (int)$_GET['r'] : 60*60*24;
$without_rain_sensor_offset  = 0;

header('Content-type: text/plain; charset=UTF-8');

// Only parse informations if 'result' is an array
if ( is_array( $result ) ) {

	// Only parse informations if 'result' is not empty
	if ( count( $result ) > 0 ) {

		// Uncomment the line below to see the structure of the 'result' array in your browser
		// var_dump($result); die();

		// For all devices -> in my case, I have 3 netamos : at home, at office and at my parents home
		$idx = -1;
		foreach ( $result as $data ) {

			$idx++;
			if ( $display > 0 ) {
				if ( $idx !== $display ) continue;
			}

			// array 'data' is now just the subpart of the array 'result' for the first device, and the next time for the second, etc... until there is no more device to parse
			$device_name = @$data['station'];

			// find rain...
			$rain   = '';
			$rain1  = '';
			$rain24 = '';
			if ( isset( $data['m'] ) && is_array( $data['m'] ) ) {
				foreach ( $data['m'] as $moduleid => $datam ) {
					if ( ( isset( $datam['dashboard']["sum_rain_24"] ) ) || ( isset( $datam['dashboard']["Rain"] ) ) ) {
						$without_rain_sensor_offset = time() - (int)@$datam['dashboard']['time_utc'];
						$rain24                     = @$datam['dashboard']["sum_rain_24"];
						$rain1                      = @$datam['dashboard']["sum_rain_1"];
						$rain                       = @$datam["misc"]["sum_rain"];
						unset( $data['m'][ $moduleid ] );
					}
				}
			}

			// get all external modules for the current device
			if ( isset( $data['m'] ) && is_array( $data['m'] ) ) {
				foreach ( $data['m'] as $moduleid => $datam ) {

					// Now 'moduleid' is the unique id of the current external module and 'datam' is an array with all informations about this exteral module

					// Uncomment the line below to see the structure of the exteral module array in your browser
					//var_dump($datam); die();

					// We retrieve all informations in variables
					$name = $datam['name'];
					$time = $datam['time'];
					$temp = $datam['results']['Temperature'];
					$humi = $datam['results']['Humidity'];

					// you can change the format as you want, all informations are at https://php.net/manual/function.date.php
					$human_date = date( 'd.m.Y', $time);
					$human_hour = date( 'H:i'  , $time);

					// Display the text that you want
					// \n at the end puts a new line at the end of this one
					if ( $without_rain_sensor_offset < $without_rain_sensor_seconds ) {
						echo str_replace(
							array( '_device_name_' , '_name_' , '_human_date_' , '_human_hour_' , '_temp_' , '_humi_' , '_rain_' , '_rain1_' , '_rain24_' ),
							array(  $device_name   ,  $name   ,  $human_date   ,  $human_hour   ,  $temp   ,  $humi   ,  $rain   ,  $rain1   , $rain24    ),
							( $rain24 == '' ) ? $text_wo_rain : $text_wi_rain
						);
					} else {
						echo str_replace(
							array( '_device_name_' , '_name_' , '_human_date_' , '_human_hour_' , '_temp_' , '_humi_' , '_rain_' , '_rain1_' , '_rain24_' ),
							array(  $device_name   ,  $name   ,  $human_date   ,  $human_hour   ,  $temp   ,  $humi   ,  $rain   ,  $rain1   , $rain24    ),
							$text_wo_rainsensor
						);
					}
					echo "\n";
				}
			}
		}
	}
	else {
		echo __('No device');
	}
}
else {
	echo $result->result['error']['message'];
}
