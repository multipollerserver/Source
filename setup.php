<?php
#ini_set('error_reporting', E_ALL);
#ini_set("display_errors", 1); 
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

include_once(__DIR__."/../../include/global.php");
include_once(__DIR__."/functions.php");

/* Cancel Installation */
if(isset($_GET['cancel_inst'])){
	if ($_GET['cancel_inst'])	{
		delete_pluginentry('multipollerserver');
		plugin_multipollerserver_files_uninstall();
		header ("Location: index.php");
	}
}//$cancel_inst


if ((multipollerserver_database_exist("poller_server") == true) AND (api_plugin_is_enabled('multipollerserver') == TRUE)) {
/* Pollerserver Plugin Versions */
$multipollerserver_versions = "0.8.8h";

# find cacti version
$cacti_version = db_fetch_cell("select cacti from version");

# find pollerserver version
$old_pollerserver_version = db_fetch_cell("SELECT version FROM plugin_config WHERE directory = 'multipollerserver'");

		if (($old_pollerserver_version <> $multipollerserver_versions) AND ($cacti_version = "0.8.8h")){
				update_sql();
				update_plugin($cacti_version);
				update_pluginversion($multipollerserver_versions);
		}
	#include_once("update/index.php");  #<-- problem
}

if ((is_pluginstatus('multipollerserver') == '4') AND (is_fallbackmode() == FALSE)){
	fallback_mode("enable");
}

if ((is_pluginstatus('multipollerserver') == '1') AND (is_fallbackmode() == TRUE)){
	fallback_mode("disable");
}

function plugin_multipollerserver_check_config() {
$config["cacti_server_os"] = (strstr(PHP_OS, "WIN")) ? "win32" : "unix";
if ($config["cacti_server_os"] == "unix") {
//called after install
//if return true, plugin will be installed but disabled
//if return false, plugin will be waiting configuration
  return true;
  }else{ 
	cacti_log("ERROR: The Plugin Multi-Pollerserver is only for UNIX. ".$config["cacti_server_os"]."", FALSE, "POLLER");
}// is unix

}

function plugin_multipollerserver_version() {
	return array(
			'name' 	=> 'multipollerserver',
			'version'  => '0.8.8h',
			'longname' => 'Multi-Pollerserver (Cluster)',
			'author'   => 'Andre Leinhos',
			'homepage' => 'http://www.cacti-multipollerserver.de',
			'email'    => 'info@www.cacti-multipollerserver.de',
			'url'      => 'http://www.cacti-multipollerserver.de'
		);
}
#function multipollerserver_config_arrays () {
function plugin_multipollerserver_config_arrays() {
	global $tree_item_types, $tree_item_handlers, $menu;


	$wm_menu = array(
		'plugins/multipollerserver/multipollerserver.php' => "Multi-Pollerserver"
	);
	
	$menu["Management"]['plugins/multipollerserver/multipollerserver.php'] = $wm_menu;
	
}

function plugin_multipollerserver_uninstall() {
	db_execute("DROP TABLE `poller_server`");
	db_execute("ALTER TABLE `host` DROP `poller_id`");
	db_execute("ALTER TABLE `host` DROP `backup_poller_id`");
	db_execute("DELETE FROM `settings` WHERE `name` = 'fallback_poller_id'");
	db_execute("UPDATE `poller_item` SET `poller_id` = 0");
	api_plugin_remove_realms ('multipollerserver');
	
	
	plugin_multipollerserver_files_uninstall();
	
	
}


function plugin_multipollerserver_install() {
	global $plugin_hooks, $error;
				$submit_values = array();


		api_plugin_register_realm('multipollerserver', 'multipollerserver.php', 'Plugin -> Multipollerserver: Configure', 1);
		
		api_plugin_register_hook('multipollerserver', 'config_arrays', 'plugin_multipollerserver_config_arrays', 'setup.php');
		api_plugin_register_hook('multipollerserver', 'config_form', 'multipollerserver_include_multipollerserver', 'setup.php');
		api_plugin_register_hook('multipollerserver', 'api_device_save', 'multipollerserver_api_device_save', 'setup.php');
		api_plugin_enable('multipollerserver');
		api_plugin_hook('multipollerserver');
				
	#comming soon
	#$plugin_hooks['config_settings']['multipollerserver'] = 'multipollerserver_config_settings';	
	$plugin_hooks['config_arrays']['multipollerserver'] = 'plugin_multipollerserver_config_arrays';

	$fail_text = "<span style='color: red; font-weight: bold; font-size: 12px;'>[Fail]</span>&nbsp;";
	$success_text = "<span style='color: green; font-weight: bold; font-size: 12px;'>[Success]</span>&nbsp;";
	
	if ((multipollerserver_database_exist("poller_server") == false) AND (api_plugin_is_enabled('multipollerserver') == TRUE)) { // wird ausgeführt wenn die Tabelle noch nicht existiert.
	

	include("./lib/ping.php");
			?>
			<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
			<html>
			<head>
				<title>cacti</title>
				<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
				<style>
				<!--
					BODY,TABLE,TR,TD
					{
						font-size: 10pt;
						font-family: Verdana, Arial, sans-serif;
					}
					.code
					{
						font-family: Courier New, Courier;
					}
					.header-text
					{
						color: white;
						font-weight: bold;
					}
				-->
				</style>
			</head>
			<body>

			<form method="post" action="" target="_SELF">
			<table width="500" align="center" cellpadding="1" cellspacing="0" border="0" bgcolor="#104075">
				<tr bgcolor="#FFFFFF" style="height:10px;">
					<td>&nbsp;</td>
				</tr>
				<tr>
					<td width="100%">
						<table cellpadding="3" cellspacing="0" border="0" bgcolor="#E6E6E6" width="100%">
							<tr>
								<td bgcolor="#104075" class="header-text">Multipollerserver Installation Guide</td>
							</tr>
							<tr>
							<td width="100%" style="font-size: 12px;">

							<?php
							# Parameter $_POST["host_value"] wird ausgewertet
							if (isset($_POST["host_value"])) {
							if (is_ip($_POST["host_value"]) == TRUE){  // ip wird ausgewertet
								$pollerserver["name"] = gethostbyaddr($_POST["host_value"]);
								
								if (is_ip($pollerserver["name"]) == TRUE){
										$message_text_ip = "IP does not match with DNS request!";
										$pollerserver["name"] = "";
										$error = 1;
									}							
								else {
									$pollerserver["hostname"] = $_POST["host_value"];
									$pollerserver["name"] = split_on_dot($pollerserver["name"]);
									$error = 0;
									}
								} //
							else { // Name wird ausgewertet
								$pollerserver["hostname"] =gethostbyname($_POST["host_value"]);
								if (is_ip($pollerserver["hostname"]) == TRUE){
									$pollerserver["name"] = split_on_dot($_POST["host_value"]);
									$error = 0;
									}
								else {
									$message_text_name = "Name does not match with DNS request!";
									$pollerserver["hostname"] = "";
									$error = 1;
									
								}
							} // Ende Auswertung
							
						
								$pollerserver["availability_method"] = AVAIL_PING;
								$pollerserver["ping_port"] = 23;
								$pollerserver["ping_timeout"] = 400;
								$pollerserver["ping_method"] = 2;
								$pollerserver["ping_retries"] = 2;
						
						 if ($pollerserver["availability_method"] == AVAIL_PING)  {
								$ping = new Net_Ping;
								$ping->host = $pollerserver;
								$ping->port = $pollerserver["ping_port"];
						
								$ping->ping($pollerserver["availability_method"], $pollerserver["ping_method"], $pollerserver["ping_timeout"], $pollerserver["ping_retries"]);
								
								if 	($ping->ping_status != "down")	{
										$pollerserver_down = false;
										$color     = "#000000";
										$error = 0;
								}else{
										$pollerserver_down = true;
										$color     = "#ff0000";
										$error = 1;
									}
						}
					} // isset($_POST["host_value"])


							$check_inst_value =	check_patch_file("check","");
									if ($check_inst_value[1] == 0) {
												$color_check = "#ff0000";
									}else{
										$color_check = "#04B404";
									}

							if ((isset($_POST['Submit_Next']) == FALSE) OR ($_POST['error'] == 1 ) OR ($_POST['setup_1'] == 0)) { ?>
						<p>
							State off patched files for using plugin correctly.  <BR><BR>
								<span style="font-size: 10px; font-weight: normal; color: <?php print $color_check; ?>; font-family: monospace;">
								<?php print $check_inst_value[0]; ?>
								</span>
							</p>
							<p>
								Pleace patch the files on console in CACTI-PATH if not done! <BR><BR>

								<span style="font-size: 12px; font-weight: normal; color: #ff0000; font-family: monospace;">
									<?php print "sudo patch -p0 -b -N < plugins/multipollerserver/patches/update_multipollerserver_088e.patch" ?><BR><BR>
								</span>
								Then click NEXT again!<BR>
							</p>
							
							<?php
								if ($check_inst_value[1] == 1){  ?>	
							<p>
								IP address or hostname of masterserver:	<br> <input type="text"  size="30" name="host_value" value="<?	print ($_POST['pollerserver["hostname"]']); ?>">
							</p>
							
								<input type="hidden" name="setup_1" value="1">
								
						<?php 
									}//($check_inst_value[1] == 0)
								} //(isset($_POST['Submit_Next']) == FALSE) OR ($_POST['error'] == 1 ) OR ($_POST['setup_1'] == 0)) 
						
								if ($_POST['setup_1'] == 1 ) { ?>
								<p>
									<b> Hostname of masterserver:</b>		
									<p class="code"><? 
										if (($pollerserver["hostname"] <> "") AND ($pollerserver["name"] <> "") AND ($ping->ping_status != "down")) { echo $success_text;}
										else {echo $fail_text;}
										print ($pollerserver["name"]); ?>
										<input type="hidden" name="name" value="<?php echo $pollerserver["name"]; ?>">
										<span style="font-size: 12px; font-weight: normal; color: #ff0000; font-family: monospace;">
												<?php print $message_text_name; ?>
										</span>
								</p>
								
								<p>
									<b> IP address of masterserver:</b>	<br>
										<span style="font-size: 10px; font-weight: normal; color: <?php print $color; ?>; font-family: monospace;">
										<?php print $ping->ping_response; ?>
										<?php print "Port:".$ping->port; ?>
										<?php print "Host:".$ping->host["name"]; ?>
									</span>
								</p>
								<p class="code"><?
										if (($pollerserver["name"] <> "") AND ($pollerserver["hostname"] <> "")) { echo $success_text;}
										else {echo $fail_text; $pollerserver["hostname"] = "";}
										print ($pollerserver["hostname"]); ?>
										<input type="hidden" name="hostname" value="<?php echo $pollerserver["hostname"]; ?>">
										<span style="font-size: 12px; font-weight: normal; color: #ff0000; font-family: monospace;">
											<?php print $message_text_ip; ?>
										</span>
								</p>
								
									<b> Ping Methode:</b>
										<select name="ping_method">
											<option value="1"<?php print ($pollerserver["ping_method"] == "2") ? " selected" : "";?>>ICMP Ping</option>
											<option value="2"<?php print ($pollerserver["ping_method"] == "2") ? " selected" : "";?>>TCP Ping</option>
											<option value="3"<?php print ($pollerserver["ping_method"] == "2") ? " selected" : "";?>>UDP Ping</option>
									</select>
								</p>
								<p>
									<b> Ping Port:</b>	
											<input type="text"  size="10" name="ping_port" value="<?	print ($pollerserver["ping_port"]); ?>">
								</p>
								<p>
									<b> Ping Timeout:</b>	
											<input type="text"  size="10" name="ping_timeout" value="<?	print ($pollerserver["ping_timeout"]); ?>">
								</p>
								<p>
									<b> Ping Retries:</b>	
											<input type="text"  size="10" name="ping_retries" value="<?	print ($pollerserver["ping_retries"]); ?>">
								</p>
								<p>
									<b> aktive:</b>
										<select name="aktive">
											<option value="off"<?php print ($pollerserver["aktive"] == "off") ? " selected" : "";?>>disabled</option>
											<option value="on"<?php print ($pollerserver["aktive"] == "on") ? " selected" : "";?>>enabled</option>
									</select>
								</p>
							<input type="hidden" name="setup_1" value="0">
							<?php } //isset($_POST['Submit_Next'] 

							 if(($error == "1") OR ($_POST['host_value'] == "")) { ?> 
										<p align="right"><a href="plugins/multipollerserver/setup.php?cancel_inst=cancel" title"Cancel">Cancel</a></p>
										<p align="right"><input type="image" src="install/install_next.gif" alt="Next"></p>
										<input type="hidden" name="Submit_Next" value="Submit_Next">
								<?php } 
								
								 if(($_POST['host_value'] != "") AND ($error == "0")) { ?> 
										<p align="right"><a href="plugins/multipollerserver/setup.php?cancel_inst=cancel" title"Cancel">Cancel</a></p>
										<p align="right"><input type="image" src="install/install_finish.gif" alt="Finish"></p> 
										<input type="hidden" name="Submit_Finish" value="Submit_Finish">
										<input type="hidden" name="error" value="0">
								<?php } ?>
							
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
			
			</form>
			</body>
			</html>

		<?php

	if(isset($_POST['Submit_Finish']))
		if ($_POST['Submit_Finish'])	{
			multipollerserver_setup_database();
			sleep(1);
			update_sql();
			sleep(1);
			
			if (multipollerserver_database_exist("poller_server") == true){
				db_execute("insert into poller_server (name, hostname, aktive, ping_method, ping_port,ping_timeout,ping_retries) values ('".$_POST['name']."','".$_POST['hostname']."','".$_POST['aktive']."','".$_POST['ping_method']."','".$_POST['ping_port']."','".$_POST['ping_timeout']."','".$_POST['ping_retries']."')");
				header ("Location: index.php");
			} // multipollerserver_database_exist
		} // $_POST['Submit_Finish']
	exit;
	}// (multipollerserver_database_exist () == false)

}// plugin_multipollerserver_install()



?>
