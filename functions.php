<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2016 Andre Leinhos                                        |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by Andre Leinhos. See    |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
*/

function dell_dot($value){
	return ereg_replace("\.","",$value);
}


function split_on_dot($servername) {
	    $serverstring = explode(".", $servername);
		  return $serverstring[0];
}

function is_ip($string){  
    if (preg_match('/\b(?:\d{1,3}\.){3}\d{1,3}\b/',$string)) {
        return TRUE;
     }else{
      return FALSE;
     }
}
function window_alert($ausg){ /* Diese Funktion simuliert JavaScript window.confirm() */ 
  $a1 = "\n <script type=\"text/javascript\"> "; 
  $a2 = "\n alert(\""; 
  $a3 = "\"); "; 
  $a4 = "\n </script> \n"; 
  $ausgabe = $a1 . $a2 . $ausg . $a3 . $a4; 
  print $ausgabe;
}

/* array_to_string - change array to string with option format the returnvalue can be formated 
		@arg $array - array for change
		@arg $textformat - em, var, blockquote etc.
		@arg $delimiter - Delipiter to split array
*/
function array_to_string($array, $textformat = "",$delimiter = ",") {

	if (is_array($array)) {
		$tag_front= "";
		$tag_back = "";

		/* formating output */
		if ($textformat <> "") {
			$tag_front = "<".$textformat.">";
			$tag_back = "</".$textformat.">";
		}
			
		/* delimiter to split arry */
			$return_value = implode($delimiter, $array);
			$return_value = $tag_front.$return_value.$tag_back;
	return $return_value;
	} // is_array
}

function test_poller_server($hostname,$server_name){
  $f_multipollerserver_name = gethostbyaddr($hostname);           
     if (is_ip($f_multipollerserver_name) == FALSE){            
        $f_multipollerserver_name = split_on_dot($f_multipollerserver_name);
       }
     if (strstr(htmlspecialchars($f_multipollerserver_name),htmlspecialchars($server_name)) == FALSE){
      return 0;
      }else{
      return 1;
      }
}

function date_time($timestamp){
	if ($timestamp > 0){
				$time_value = date("d.M.Y - H:i:s",$timestamp);
				return $time_value;
			}else{
				return $time_value;
			}
} //date_time


function updatelog($log_values,$cp_error = 0,$new_version){
	if (strstr($log_values,'Reversed')) {
		cacti_log("ERROR: The Plugin Multi-Pollerserver notifies one or more errors at updateprocess. $log_values", FALSE, "POLLER");
	} elseif (count($log_values) == 0) {
		cacti_log("ERROR: The Plugin Multi-Pollerserver notifies the patches are not found.", FALSE, "POLLER");
	} elseif ($cp_error == 1) {
		cacti_log("ERROR: The Plugin Multi-Pollerserver notifies the backup of patching files not done.", FALSE, "POLLER");
	} else {
		#db_execute("UPDATE plugin_config SET version = '$new_version' WHERE directory = 'multipollerserver'");
		#cacti_log("STATS: The Plugin Multi-Pollerserver is now up to date. New version: $new_version $log_values", FALSE, "POLLER");
	}
} // updatelog

function update_plugin($cacti_version) {
	
	# Copy org_files for Backup 
	if (!is_dir("plugins/multipollerserver/org_files/")){
		mkdir("plugins/multipollerserver/org_files", 0777);
		}
	#backup cacti version files	
	check_patch_file("install",$cacti_version);
} //update_plugin

function update_pluginversion($new_version) {
	db_execute("UPDATE plugin_config SET version = '$new_version' WHERE directory = 'multipollerserver'");
	cacti_log("STATS: The Plugin Multi-Pollerserver is now up to date. New version: $new_version ", FALSE, "POLLER");
} //update_pluginversion


function uninstalllog($cp_error){
		if ($cp_error == 1) {
		cacti_log("ERROR: The Plugin Multi-Pollerserver notifies the original files could not be discoverd. Look at 'Cacti_Path'/plugins/multipollerserver/org/. ", FALSE, "POLLER");
		}else {
		cacti_log("STATS: The Plugin Multi-Pollerserver is completely uninstalled.", FALSE, "POLLER");
		}
} // uninstalllog

function multipollerserver_database_exist($serach_table) {
	$exist = false;
	$table_exist = db_fetch_cell("SHOW TABLES LIKE '$serach_table'");
	
	$exist = ($table_exist == "" ) ? false : true ;
	return $exist;
}//multipollerserver_database_exist ()


# SHOW FIELDS FROM host 
function database_field_exist($serach_table,$search_field) {
	
	$fields = db_fetch_assoc("SHOW COLUMNS FROM $serach_table");

		for ($i = 1 ; $i <= sizeof($fields); $i++) {
			if ($fields[$i]["Field"] == $search_field)
				return true;
		} // for
}//multipollerserver_database_exist ()

function multipollerserver_setup_database() {

db_execute("CREATE TABLE IF NOT EXISTS `poller_server` (
		`id` int(3) NOT NULL auto_increment,
		`name` varchar(100) NOT NULL,
		`hostname` varchar(50) NOT NULL,
		`poller_lastrun` int(50) default '0',
		`stats_poller` varchar(200) default '0',
		`aktive` char(2) default NULL,
		`hostcount` INT(5) default '0',
		`availability_method` smallint(5) unsigned NOT NULL default '3',
		`ping_method` smallint(5) unsigned default '3',
		`ping_port` int(12) unsigned default '33439',
		`ping_timeout` int(12) unsigned default '500',
		`ping_retries` int(12) unsigned default '2',
		`backup_poller_id` INT(3) default '0',
		`backup_mode` INT(1) default '0',
		PRIMARY KEY  (`id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;");
} //multipollerserver_setup_database


//compatibility for plugin update
function multipollerserver_version() {
  return plugin_multipollerserver_version();
} //multipollerserver_version


// Vorbereitung für weiter Settingsfunktionen
function multipollerserver_config_settings() {
	global $tabs, $settings;
	$tabs["server"] = "multipollerserver";
    	
	 $temp = array(
		"poller_server_header" => array(
			"friendly_name" => "multipollerserver Options",
			"method" => "spacer",
			),
    "poller_server" => array(
			"friendly_name" => "Server Name",
			"description" => "Name of servers",
			"method" => "textbox",
			"max_length" => 255,
			),
   );    
  
  if (isset($settings["server"]))
		$settings["server"] = array_merge($settings["server"], $temp);
	else
		$settings["server"]=$temp;
    unset($temp); 
} //multipollerserver_config_settings

function multipollerserver_include_multipollerserver() {  //Dropdown multipollerserver in device einbinden
	
	global $fields_host_edit;
	$fields_host_edit2 = $fields_host_edit;
	$fields_host_edit3 = array();

		foreach ($fields_host_edit2 as $f => $a) {
			$fields_host_edit3[$f] = $a;
				if ($f == 'device_threads') {
					if (is_fallbackmode() == FALSE) { // fallback_poller_id = 1
						$fields_host_edit3["poller_id"] = array(
								"method" => "drop_sql",
								"friendly_name" => "Poller Server",
								"disabled" => "disabled",
								"description" => "Choose what type of host, host template this is. The host template will govern what kinds of data should be gathered from this type of host.",
								"value" => "|arg1:poller_id|",
								"none_value" => "None",
								"sql" => "select id,name from poller_server order by id",
							);
						}else{
							$fields_host_edit3["poller_id"] = array(
								"friendly_name" => "Poller Server",
								"description" => "Choose what type of host, host template this is. The host template will govern what kinds of data should be gathered from this type of host. <br>
																		<FONT COLOR=red> The plugin is not enabled, so the poller are in fallbackmode.<br>
																		All settings where saved on database \"temp_multipollerserver_host\" so you can dump the settings for backup.</FONT>",
								"method" => "textbox",
								"value" => "NONE",
								"max_length" => 255,
								);
						}
				}
			}// foreach
	  $fields_host_edit = $fields_host_edit3;
	
	
}//multipollerserver_include_multipollerserver


function multipollerserver_api_device_save($save) {
	$save["poller_id"]	= form_input_validate($_POST["poller_id"], "poller_id", "", true, 3);
	
	if ($save["poller_id"] <> ""){
			db_execute("UPDATE host SET `poller_id` = '".$save['poller_id']."' WHERE `id` = '".$_POST['id']."'");
	}
	return $save;
} //multipollerserver_api_device_save

/* update the poller_iten table with the current pollerserver_id*/
function update_poller_item($host_id, $poller_id) {
	if (($host_id <> "") AND ($poller_id <> "")){
			db_execute("UPDATE poller_item SET `poller_id` = '".$poller_id."' WHERE `host_id` = '".$host_id."'");
			# update the hostcount on multipollerserver overview
			update_hostcount();
	}
} //update_poller_item

function update_hostcount() {
	
	$pollerserver_ids = db_fetch_assoc("SELECT `id` FROM `poller_server`");

		for ($i = 0 ; $i <= sizeof($pollerserver_ids)-1; $i++) {
				$hostcount = db_fetch_cell("SELECT count(`id`) FROM `host` WHERE `poller_id` = '".$pollerserver_ids[$i]["id"]."'");
				db_execute("UPDATE poller_server SET `hostcount` = '".$hostcount."' WHERE `id` = '".$pollerserver_ids[$i]["id"]."'");
		} // for

} //update_hostcount

function pollerid_to_pollername($poller_id) {
	if ($poller_id == 0){
			$pollerserver_name = "NONE";
		}else{
			$pollerserver_name = db_fetch_cell("SELECT `name` FROM `poller_server` WHERE `id` = $poller_id");
		}
		return $pollerserver_name;
} //pollerid_to_pollername

function pollername_to_pollerid($pollername){
	if ($pollername == "NONE"){
			$pollerserver_name = "0";
		}else{
			$pollerserver_name = db_fetch_cell("SELECT `id` FROM `poller_server` WHERE `name` = '$pollername'");
		}
		return $pollerserver_name;
}//pollername_to_pollerid


/* function for poller.php*/
function check_backuppollerserver($server_remote_name){
	
	$poller_server_id = db_fetch_cell("SELECT id FROM poller_server WHERE name ='$server_remote_name' AND aktive = 'on'");
	$poller_backup_server_id = db_fetch_cell("SELECT backup_poller_id FROM poller_server WHERE name ='$server_remote_name' ");
	
	if ($poller_backup_server_id > 0) { // = 0 is NONE
			$poller_backup_server_name = db_fetch_cell("SELECT name FROM poller_server WHERE id ='$poller_backup_server_id' ");
			$poller_backup_server_lastrun = db_fetch_cell("SELECT poller_lastrun  FROM poller_server WHERE id ='$poller_backup_server_id' ");
			$poller_backup_server_mode = db_fetch_cell("SELECT backup_mode FROM poller_server WHERE id ='$poller_backup_server_id' ");
	
			$sum_of_backup_mode = db_fetch_cell("SELECT SUM(`backup_mode`) FROM poller_server");
			
			$now_time = time();
			if(($now_time - $poller_backup_server_lastrun > 420) AND ($poller_backup_server_mode == 0) AND ($sum_of_backup_mode == 0)){
					db_execute("UPDATE `host` SET `backup_poller_id` = `poller_id`,`poller_id` = $poller_server_id  WHERE `poller_id` = $poller_backup_server_id");
					db_execute("UPDATE `poller_server` SET `backup_mode` = '1' WHERE `id` = $poller_backup_server_id ");
										/* update the poller_iten table with the current pollerserver_id*/
					$array_host_id = db_fetch_assoc("SELECT `id` FROM host WHERE poller_id = $poller_server_id");
						if (sizeof($array_host_id)) {
							foreach($array_host_id as $host_id) {
							update_poller_item($host_id["id"], $poller_server_id);
							}
					}
				cacti_log("WARNING: Pollerserver: ".$server_remote_name." takes over the polling for Pollerserver ".$poller_backup_server_name."'", TRUE);
			}elseif(($now_time - $poller_backup_server_lastrun > 420) AND ($poller_backup_server_mode == 0) AND ($sum_of_backup_mode > 0)){
						cacti_log("WARNING: SORRY for Pollerserver: ".$poller_backup_server_name." is no backupprocess possible, only one Pollerserver can run in Backup Mode!", TRUE);
			}elseif(($now_time - $poller_backup_server_lastrun < 420) AND ($poller_backup_server_mode == 1)){
					/* update the poller_iten table with the current pollerserver_id*/
					$array_host_id = db_fetch_assoc("SELECT `id` FROM host WHERE backup_poller_id = $poller_backup_server_id");
					if (sizeof($array_host_id)) {
							foreach($array_host_id as $host_id) {
								update_poller_item($host_id["id"], $poller_backup_server_id);
							}
						}
					db_execute("UPDATE `host` SET `poller_id` = `backup_poller_id`,`backup_poller_id` = '0'  WHERE `backup_poller_id` = $poller_backup_server_id");
					db_execute("UPDATE `poller_server` SET `backup_mode` = '0' WHERE `id` = $poller_backup_server_id ");
					update_hostcount();
					cacti_log("WARNING: Pollerserver: ".$poller_backup_server_name." takes over the polling '", TRUE);
			}//check time diff lastpoll
		} //$poller_backup_server_id > 0
return $poller_server_id;
}//check_backuppollerserver


/* function for look fallbackmode*/
function is_fallbackmode(){
		$fallback_mode_id = db_fetch_cell("SELECT value FROM `settings` WHERE `name` = 'fallback_poller_id'");
	if ($fallback_mode_id == 1){
		return TRUE; //1
	}else{
		return FALSE; //0
	}
}//is_fallbackmode


/* check if table exists */
# @arg1 = TABLE_NAME
# @arg1 = COLUMN_NAME
function table_exists( $f_tablename,$f_columnname){
	global $database_default;
	$exist_table = db_fetch_cell("SELECT ORDINAL_POSITION FROM information_schema.COLUMNS WHERE COLUMN_NAME='$f_columnname' AND TABLE_NAME='$f_tablename' AND TABLE_SCHEMA= '$database_default'");
		if ($exist_table <> ""){
			return TRUE; //exists
			}else{
				return FALSE; //Not exists
			}
} // table_exists

function db_install_execute($cacti_version, $sql) {
	$sql_install_cache = (isset($_SESSION["sess_sql_install_cache"]) ? $_SESSION["sess_sql_install_cache"] : array());

	if (db_execute($sql)) {
		$sql_install_cache{sizeof($sql_install_cache)}[$cacti_version][1] = $sql;
	}else{
		$sql_install_cache{sizeof($sql_install_cache)}[$cacti_version][0] = $sql;
	}

	$_SESSION["sess_sql_install_cache"] = $sql_install_cache;
}//db_install_execute($cacti_version, $sql)

function check_patch_file($check_opt,$cacti_version){

	$patched_files = array("poller.php","lib/api_poller.php","lib/poller.php");
	$patched_files_name = array("poller","lib_api_poller","lib_poller");

	$cacti_version_dell = dell_dot($cacti_version);
	
	$pached = 0;
	For ($i=0; $i<count($patched_files); $i++) {
		$search_checksum = file ($patched_files[$i]);
	if ($check_opt == "install"){
	
		rename($patched_files[$i].".orig", "plugins/multipollerserver/org_files/".$cacti_version."_".$patched_files_name[$i].".php.orig");
		 if(strstr($search_checksum[1],'#Multipollerserver_checksumm=cacti_088h_multipollerserver_088h') == TRUE) {
	    			cacti_log("STATS: Patch-Pollerserver:  The file ".$patched_files[$i]." are pateched succesfully.", FALSE, "POLLER");
	    		} 
	    else{
	    		cacti_log("ERROR: Patch-Pollerserver:  The file ".$patched_files[$i]." are NOT pateched succesfully.", FALSE, "POLLER");
	    }		
	    $pached = $pached++;
    } //$check_opt == ""
	
	if ($check_opt == "check"){

		 if(strstr($search_checksum[1],'#Multipollerserver_checksumm=cacti_088h_multipollerserver_088h') == TRUE) {
	    	$message_inst = $message_inst = "The file ".$patched_files[$i]." are pateched succesfully.<br>" .$message_inst;
	    	$status = 1;
	   }else{
	    	$message_inst = $message_inst = "The file ".$patched_files[$i]." are NOT pateched succesfully.<br>".$message_inst;
	    	$status = 0;
	   }
		} //$check_opt == "install"					
	}//for

	$status += $status;

	if ($status == 0) {
		return array($message_inst,0);
		} else{
			return array($message_inst,1);
		}
} //check_patch_file($check_opt)

function fallback_mode($mode){

	if ($mode == "enable"){
		if (db_execute("UPDATE `poller_item` set `poller_id` = '2'") == '1'){
			cacti_log("STATS: Fallbackmode: All poller-items where set to 2.", FALSE, "POLLER");
		}else{
			cacti_log("Error: Fallbackmode: Update the table poller_item ist broken!", FALSE, "POLLER");
		}

		if (db_execute("UPDATE `host` set `poller_id` = '2'") == '1'){
			cacti_log("STATS: Fallbackmode: All host polled by master", FALSE, "POLLER");
		}else{
			cacti_log("Error: Fallbackmode: Update the table poller_item ist broken!", FALSE, "POLLER");
		}

		if (db_execute("UPDATE `host` set `backup_poller_id` = '0'") == '1'){
			cacti_log("STATS: Fallbackmode: Disabled Backuppollerserver", FALSE, "POLLER");
		}else{
			cacti_log("Error: Fallbackmode: NOT Disabled Backuppollerserver!", FALSE, "POLLER");
		}


		if (db_execute("UPDATE `settings` SET `value` = '1' WHERE `name` = 'fallback_poller_id'") == '1'){
			cacti_log("STATS: Fallbackmode: Plugin Multipollerserver update settings for run in Fallbackmode.", FALSE, "POLLER");
		}else{
			cacti_log("Error: Fallbackmode: Plugin Multipollerserver NOT update settings for run in Fallbackmode!", FALSE, "POLLER");
		}

		if (table_exists("temp_multipollerserver_host","id") == TRUE){
				if (db_execute("DROP TABLE temp_multipollerserver_host") == '1'){
					cacti_log("STATS: Fallbackmode: Plugin Multipollerserver delete old backup table.", FALSE, "POLLER");
				}else{
					cacti_log("Error: Fallbackmode: Plugin Multipollerserver NOT delete old backup table.!", FALSE, "POLLER");
				}
		}

		if (db_execute("CREATE TABLE IF NOT EXISTS `temp_multipollerserver_host` (
															  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
															  `hostname` varchar(250) NOT NULL,
															  `poller_id` smallint(5) NOT NULL,
															  `backup_poller_id` int(3) NOT NULL,
															  PRIMARY KEY (`id`)
														) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ; ") == '1') {
			cacti_log("STATS: Fallbackmode: Plugin Multipollerserver backup for Fallbackmode.", FALSE, "POLLER");
			}else{
				cacti_log("Error: Fallbackmode: NOT create temp db-table for Fallbackmode!", FALSE, "POLLER");
			}

		$sql1 = mysql_query("SELECT `hostname`,`poller_id`,`backup_poller_id` FROM `host`");
		while ($erg_qry = mysql_fetch_array($sql1)){
			if (db_execute("INSERT INTO `temp_multipollerserver_host` ( `id` ,`hostname` ,`poller_id` ,`backup_poller_id`) VALUES ( NULL , '".$erg_qry['hostname']."', ".$erg_qry['poller_id'].", ".$erg_qry['backup_poller_id'].")") != '1'){
				cacti_log("Error: Fallbackmode: Fallbackmode: NOT update temp db-table for Fallbackmode!", FALSE, "POLLER");
				}
		}
		if (db_execute("UPDATE `cacti`.`plugin_config` SET `status` = '4' WHERE `directory` = 'multipollerserver'") == '1'){
			cacti_log("STATS: Fallbackmode: Plugin Multipollerserver is now run in Fallbackmode.", FALSE, "POLLER");
		}else{
			cacti_log("Error: Fallbackmode: Plugin Multipollerserver is NOT run in Fallbackmode!", FALSE, "POLLER");
		}
	} // ($mode == "enable"){

	if ($mode == "disable"){

		$fallback_tb_entry = db_fetch_assoc("SELECT `hostname`, `poller_id`, `backup_poller_id` FROM `temp_multipollerserver_host` ");

		for ($i = 0 ; $i <= sizeof($fallback_tb_entry)-1; $i++) {
				
				#db_execute("UPDATE poller_server SET `hostcount` = '".$hostcount."' WHERE `id` = '".$pollerserver_ids[$i]["id"]."'");
			if (db_execute("UPDATE `poller_item` set `poller_id` = '".$fallback_tb_entry[$i]["poller_id"]."' WHERE `hostname` = '".$fallback_tb_entry[$i]["hostname"]."'") == '1'){
			cacti_log("STATS: Fallbackmode: Update '".$fallback_tb_entry[$i]["hostname"]."' in tbl poller_item.", FALSE, "POLLER");
			}else{
			cacti_log("Error: Fallbackmode: Error Hostname '".$fallback_tb_entry[$i]["hostname"]."' not found in tbl poller_item!", FALSE, "POLLER");
			}
			if (db_execute("UPDATE `host` set `poller_id` = '".$fallback_tb_entry[$i]["poller_id"]."' WHERE `hostname` = '".$fallback_tb_entry[$i]["hostname"]."'") == '1'){
				#cacti_log("STATS: Fallbackmode: All host polled by master", FALSE, "POLLER");
			}else{
				cacti_log("Error: Fallbackmode: Update the table poller_item ist broken!", FALSE, "POLLER");
			}
			if (db_execute("UPDATE `host` set `backup_poller_id`  = '".$fallback_tb_entry[$i]["backup_poller_id"]."' WHERE `hostname` = '".$fallback_tb_entry[$i]["hostname"]."'") == '1'){
				#cacti_log("STATS: Fallbackmode: All host polled by master", FALSE, "POLLER");
			}else{
				cacti_log("Error: Fallbackmode: Update the table poller_item ist broken!", FALSE, "POLLER");
			}
		} // for

		if (db_execute("UPDATE `settings` SET `value` = '0' WHERE `name` = 'fallback_poller_id'") == '1'){
			#cacti_log("STATS: Fallbackmode: Plugin Multipollerserver update settings for run in Normalpollermode.", FALSE, "POLLER");
		}else{
			cacti_log("Error: Fallbackmode: Plugin Multipollerserver NOT run in Normalpollermode!", FALSE, "POLLER");
		}
	
		if (db_execute("UPDATE `cacti`.`plugin_config` SET `status` = '1' WHERE `directory` = 'multipollerserver'") == '1'){
			cacti_log("STATS: Fallbackmode: Plugin Multipollerserver update settings for run in Normalpollermode.", FALSE, "POLLER");
		}else{
			cacti_log("Error: Fallbackmode: Plugin Multipollerserver is NOT run in Normalpollermode!", FALSE, "POLLER");
		}
	} // ($mode == "disable"){
} //fallback_mode()

/* function for look fallbackmode*/
function is_pluginstatus($f_plugin){
		$pluginstatus = db_fetch_cell("SELECT status FROM `cacti`.`plugin_config` WHERE `directory` = '".$f_plugin."'");
		return $pluginstatus;
}//is_pluginstatus

/* function for look cacti_web_path*/
function cacti_web_path(){
		$cacti_web_path = db_fetch_cell("select `value` from settings WHERE `name` = 'path_webroot'");
		return $cacti_web_path ;
}//cacti_web_path

function delete_pluginentry($f_plugin){
		$pluginstatus = db_fetch_cell("DELETE FROM `cacti`.`plugin_config` WHERE `directory` = '".$f_plugin."'");
}//delete_pluginentry

function plugin_multipollerserver_files_uninstall() {

	$cacti_web_path = cacti_web_path();

	if (!copy($cacti_web_path."/poller.php.orig", $cacti_web_path."/poller.php")) {
	$cp_error = 1;
	}if (!copy($cacti_web_path."/lib/poller.php.orig", $cacti_web_path."/lib/poller.php")) {
		$cp_error = 1;
	}if (!copy($cacti_web_path."/lib/api_poller.php.orig", $cacti_web_path."/lib/api_poller.php")) {
		$cp_error = 1;
	}
	
	uninstalllog($cp_error);
		
	unlink($cacti_web_path."/poller.php.orig");
	unlink($cacti_web_path."/lib/poller.php.orig");
	unlink($cacti_web_path."/lib/api_poller.php.orig");
	
}//plugin_multipollerserver_files_uninstall()

function update_sql() {

	#add column fallback_poller_id
	if (table_exists("settings","fallback_poller_id") == FALSE){
		if (	db_execute("INSERT INTO `settings` (`name`, `value`) SELECT * FROM (SELECT 'fallback_poller_id', '0') AS tmp WHERE NOT EXISTS ( SELECT `name` FROM `settings` WHERE `name` = 'fallback_poller_id')") == '1'){
				cacti_log("STATS: UPDATE-Pollerserver: Column fallback_poller_id add to table settings.", FALSE, "POLLER");
		}else{
				cacti_log(" ERROR: UPDATE-Pollerserver: Column fallback_poller_id NOT add to table settings.", FALSE, "POLLER");
				}
	} //table_exists

	/* add column backup_poller_id to hosts*/
	if (table_exists("host","poller_id") == FALSE){
		if (db_execute("ALTER TABLE `host` ADD `poller_id` SMALLINT( 5 ) NOT NULL DEFAULT '2'") == '1'){
			cacti_log("STATS: UPDATE-Pollerserver: Column poller_id add to table host.", FALSE, "POLLER");
		}else{
			cacti_log(" ERROR: UPDATE-Pollerserver: Column poller_id NOT add to table host.", FALSE, "POLLER");
		}
	} //table_exists

	/* add column backup_poller_id to hosts*/
	if (table_exists("host","backup_poller_id") == FALSE){
		if (db_execute("ALTER TABLE `host` ADD `backup_poller_id` INT(3) NULL DEFAULT '0'") == '1'){
			cacti_log("STATS: UPDATE-Pollerserver: Column backup_poller_id add to table host.", FALSE, "POLLER");
		}else{
			cacti_log(" ERROR: UPDATE-Pollerserver: Column backup_poller_id NOT add to table host.", FALSE, "POLLER");
		}
	} //table_exists
} // update_sql{}

?>
