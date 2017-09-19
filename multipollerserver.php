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

include_once(__DIR__."/functions.php");

chdir('../../');
include ("./include/auth.php");
include_once("./lib/tree.php");
include_once("./lib/html_tree.php");
include_once("./lib/utility.php");
include_once("./lib/template.php");
include_once("./lib/ping.php");

define("MAX_DISPLAY_PAGES", 21);

$ds_actions = array(
	1 => "Delete",
	2 => "Disable",
	3 => "Enable" #,
	);



/* select all Pollerserver on database */

$ds_choose_server = array(10=>"NONE");
//array_push($ds_choose_server, "NONE");
$a = 11;
$result = mysql_query("SELECT name from poller_server");
while ($erg_qry = mysql_fetch_array($result))
			{
				$ds_choose_server[$a] = $erg_qry['name'];
				$a++;
			}

/* set default action */
if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = ""; }

switch ($_REQUEST["action"]) {
	case 'save':
		form_save();

		break;
	case 'actions':
		form_actions();

	case 'template_remove':
		template_remove();

		header("Location: multipollerserver.php");
		break;
	case 'template_edit':
		include_once("./include/top_header.php");

		template_edit();

		include_once ("./include/bottom_footer.php");
		break;
		case 'device_choose':
		include_once("./include/top_header.php");

		device_choose();

		include_once ("./include/bottom_footer.php");
		break;
	default:
		include_once("./include/top_header.php");

		template();

		include_once("./include/bottom_footer.php");
		break;
}

/* --------------------------
    The Save Function
   -------------------------- */

function form_save() {

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_post("pollerserver_id"));
	/* ==================================================== */
 
	if (isset($_POST["save_component"])) {
		$redirect_back = false;
		$save["id"] = $_POST["template_id"];
    $save["name"] = form_input_validate($_POST["name"], "name", "", true, 3);
    $save["hostname"] = form_input_validate($_POST["hostname"], "hostname", "", false, 3);
    $save["backup_poller_id"] = form_input_validate($_POST["backup_poller_id"], "backup_poller_id", "", false, 3);
    $save["aktive"] = form_input_validate($_POST["aktive"], "aktive", "", true, 3);
    $save["availability_method"] = form_input_validate($_POST["availability_method"], "availability_method", "", true, 3);
    $save["ping_method"] = form_input_validate($_POST["ping_method"], "ping_method", "", true, 3);
    $save["ping_port"] = form_input_validate($_POST["ping_port"], "ping_port", "", true, 3);
    $save["ping_timeout"] = form_input_validate($_POST["ping_timeout"], "ping_timeout", "", true, 3);
    $save["ping_retries"] = form_input_validate($_POST["ping_retries"], "ping_retries", "", true, 3);
   
   
      $test_poller = test_poller_server($_POST["hostname"],$_POST["name"]);
		if (!is_error_message()) { 
        if ($test_poller)  $pollerserver_id = sql_save($save, "poller_server","id");
       		if ($pollerserver_id) {
            raise_message(1);
             header("Location: multipollerserver.php?action=edit&id=" . (empty($pollerserver_id) ? $_POST["template_id"] : $pollerserver_id));
	    		}else{
            raise_message(2);
          header("Location: multipollerserver.php?action=template_edit&id=" . (empty($pollerserver_id) ? $_POST["template_id"] : $pollerserver_id).(empty($pollerserver_id) ? "&hostname=".$_POST["hostname"] : $hostname).(empty($pollerserver_id) ? "&name=".$_POST["name"] : $name));
			    }
     }else{ 
      header("Location: multipollerserver.php?action=template_edit&id=" . (empty($pollerserver_id) ? $_POST["template_id"] : "0"));
	  }
	}
}

/* ------------------------
    The "actions" function
   ------------------------ */

function form_actions() {

	global $colors, $ds_actions,$ds_choose_server;

	/* if we are to save this form, instead of display it */
	if (isset($_POST["selected_items"])) {
		$selected_items = unserialize(stripslashes($_POST["selected_items"]));

		if ($_POST["drp_action"] == "1") { /* delete */
			$pollerserver_template_datas = db_fetch_assoc("select id from poller_server where " . array_to_sql_or($selected_items, "id") . " and aktive = ''");

			if (sizeof($pollerserver_template_datas) > 0) {
  			foreach ($pollerserver_template_datas as $pollerserver_template_data) {
  			#	db_execute("delete from poller_server where id=" . $pollerserver_template_data["id"]);
  			# Master kann nicht gelöscht werden.
  			db_execute("delete from poller_server where id=" . $pollerserver_template_data["id"] ." AND id != '2'",false);
  			}
			}
  		db_execute("delete from poller_server where " . array_to_sql_or($selected_items, "id")." and aktive = ''");

			}elseif ($_POST["drp_action"] == "2") { /* disable */
        for ($i=0;($i<count($selected_items));$i++) {
  				/* ================= input validation ================= */
  				input_validate_input_number($selected_items[$i]);
  				/* ==================================================== */
          db_execute("update poller_server set  aktive = '' where id =".$selected_items[$i]."");
          }
      }elseif ($_POST["drp_action"] == "3") { /* enable */
        for ($i=0;($i<count($selected_items));$i++) {
  				/* ================= input validation ================= */
  				input_validate_input_number($selected_items[$i]);
  				/* ==================================================== */
          	db_execute("update poller_server set  aktive = 'on' where id =".$selected_items[$i]."");
          }
      }elseif ($_POST["drp_action"] >= "10") { /* Pollererver */
        for ($i=0;($i<count($selected_items));$i++) {
  				/* ================= input validation ================= */
  				input_validate_input_number($selected_items[$i]);
  				/* ==================================================== */
  				
  				$poller_server_id = pollername_to_pollerid($_POST["pollerservername"]);
  				db_execute("update host set  poller_id = $poller_server_id where id =".$selected_items[$i]."");
  				update_poller_item($selected_items[$i], $poller_server_id);
          }
      }
    
		header("Location: multipollerserver.php");
		exit;
	}

	/* setup some variables */
	$ds_list = ""; $i = 0;

	/* loop through each of the graphs selected on the previous page and get more info about them */
	while (list($var,$val) = each($_POST)) {
		if (ereg("^chk_([0-9]+)$", $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */
			
			// Device_Choose selection
			if ($_GET["action"] == "device_choose") {
					$ds_list .= "<li>" . db_fetch_cell("select description from host where id=" . $matches[1]) . "<br>";
			}else{
			$ds_list .= "<li>" . db_fetch_cell("select name from poller_server where id=" . $matches[1]) . "<br>";
			}
			$ds_array[$i] = $matches[1];
			$i++;
		}
		
	}

	include_once("./include/top_header.php");

if ($_GET["action"] == "device_choose") {
		html_start_box("<strong>" . $ds_choose_server{$_POST["drp_action"]} . "</strong>", "60%", $colors["header_panel"], "3", "center", "");
	}else{
		html_start_box("<strong>" . $ds_actions{$_POST["drp_action"]} . "</strong>", "60%", $colors["header_panel"], "3", "center", "");
	}



	print "<form action='multipollerserver.php?action=device_choose ' method='post'>\n";

	if (isset($ds_array) && sizeof($ds_array)) {
		if ($_POST["drp_action"] == "1") { /* delete */
			
			print "	<tr>
					<td class='textArea' bgcolor='#" . $colors["form_alternate1"]. "'>
						<p><b> Note the Masterserver could not deleted! </b></p>
						<p>When you click \"Continue\", the following Poller Server(s) will be deleted if there are not AKTIVE.</p>
						<p><ul>$ds_list</ul></p>
					</td>
				</tr>\n
				";
			$save_html = "<input type='button' value='Cancel' onClick='window.history.back()'>&nbsp;<input type='submit' value='Continue' title='Delete Data Template(s)'>";
		}elseif ($_POST["drp_action"] == "2") { /* Disable Devices */
			print "	<tr>
						<td class='textArea' bgcolor='#" . $colors["form_alternate1"]. "'>
						<p>To disable the following Poller Server(s), click \"Continue\".</p>
						<p><ul>$ds_list</ul></p>
					</td>
				</tr>\n
				";
			$save_html = "<input type='button' value='Cancel' onClick='window.history.back()'>&nbsp;<input type='submit' value='Continue' title='Disable Data Template(s)'>";
    }elseif ($_POST["drp_action"] == "3") { /* Enable Devices */
			print "	<tr>
						<td class='textArea' bgcolor='#" . $colors["form_alternate1"]. "'>
						<p>To enable the following Poller Server(s), click \"Continue\".</p>
						<p><ul>$ds_list</ul></p>
					</td>
				</tr>\n
				";
			$save_html = "<input type='button' value='Cancel' onClick='window.history.back()'>&nbsp;<input type='submit' value='Continue' title='Enable Data Template(s)'>";

		}elseif ($_POST["drp_action"] >= "10") { /* Choose the Pollerserver*/
			print "	<tr>
						<td class='textArea' bgcolor='#" . $colors["form_alternate1"]. "'>
						<p>The following devices become to ".$ds_choose_server{$_POST["drp_action"]}." Pollerserver, click \"Continue\".</p>
						<p><ul>$ds_list</ul></p>
					</td>
				</tr>\n
				<input type='hidden' name='pollerservername' value='" . $ds_choose_server{$_POST["drp_action"]} . "'>
				";
			$save_html = "<input type='button' value='Cancel' onClick='window.history.back()'>&nbsp;<input type='submit' value='Continue' title='Choose to Pollerserver'>";
		}
		else{
		print "<tr><td bgcolor='#" . $colors["form_alternate1"]. "'><span class='textError'>You must select at least one data template.</span></td></tr>\n";
		$save_html = "<input type='button' value='Return' onClick='window.history.back()'>";
	 }
  }
	print "	<tr>
			<td align='right' bgcolor='#eaeaea'>
				<input type='hidden' name='action' value='actions'>
				<input type='hidden' name='selected_items' value='" . (isset($ds_array) ? serialize($ds_array) : '') . "'>
				<input type='hidden' name='drp_action' value='" . $_POST["drp_action"] . "'>
				$save_html
			</td>
		</tr>
		";
	
	html_end_box();

	include_once("./include/bottom_footer.php");
}

/* ----------------------------
    template - Poller Server Templates edit
   ---------------------------- */
function template_edit() {
	global $colors;
$pollerserver = db_fetch_row("select * from poller_server where id=" . $_GET["id"]."",false);

if (!empty($_GET["id"])) {
		?>
		<table width="100%" align="center">
			<tr>
				<td class="textInfo" colspan="2">
					<?php print htmlspecialchars($pollerserver["name"]);?> (<?php print htmlspecialchars($pollerserver["hostname"]);?>)
				</td>
			</tr>
          <?php  
          if ($pollerserver["availability_method"] == AVAIL_PING)  {
					/* create new ping socket for host pinging */
					$ping = new Net_Ping;
					$ping->host = $pollerserver;
					$ping->port = $pollerserver["ping_port"];

					/* perform the appropriate ping check of the host */
					if ($ping->ping($pollerserver["availability_method"], $pollerserver["ping_method"],
						$pollerserver["ping_timeout"], $pollerserver["ping_retries"])) {
          	$pollerserver_down = false;
						$color     = "#000000";
					}else{
						$pollerserver_down = true;
						$color     = "#ff0000";
					}
          ?>
					<tr>
				      <td class="textHeader">Ping Results<br>
    					<span style="font-size: 10px; font-weight: normal; color: <?php print $color; ?>; font-family: monospace;">
              <?php print $ping->ping_response; ?>
              <?php print "Port:".$ping->port; ?>
              <?php print "Host:".$ping->host["hostname"]; ?>
    					</span>
    				  </td>
    				  <td class="textInfo" valign="top">
    				  	<span style="color: #c16921;">*</span><a href="<?php print htmlspecialchars("multipollerserver.php?action=device_choose");?>">Choose Devices for Pollerserver</a><br>
    				  </td>
          </tr>
      <?php } 
            $ifaktive = "1";
            if (isset($_GET["hostname"])) {
                  $pollerserver_name = gethostbyaddr($_GET["hostname"]);
                 }else{
                   $pollerserver_name = gethostbyaddr($pollerserver["hostname"]);
                 }
                 
             if (is_ip($pollerserver_name) == FALSE){            
                $pollerserver_name = split_on_dot($pollerserver_name);
                $message_text = "Pollername does not match with DNS request! <br>Poller-Server will be disabled!";
                }else{
                $message_text = "IP does not match with DNS request! <br>Poller-Server will be disabled!";
               }
              
              $poller_name_from_ip = strstr(htmlspecialchars($pollerserver_name),htmlspecialchars($pollerserver["name"]));
              if ($poller_name_from_ip == FALSE){
                  $color     = "#ff0000";
               ?>
              <tr>
                  <td class="textHeader">Name Results <?php print $pollerserver_name; ?><br>
        					<span style="font-size: 12px; font-weight: normal; color: <?php print $color; ?>; font-family: monospace;">
        					<?php print $message_text; ?>
                  </span>
                  </td>
              </tr> 
             <?php                  
                  }// $poller_name_from_ip == FALSE 
              ?>
    </table>
  <?php 
	  
    $header_label = "[edit: " . $pollerserver["name"] . "]";
}else{
	
	
	?>
	
		<table width="100%" align="center">


          <?php
       
		$pollerserver["host"] = $_GET["host"];
		$pollerserver["hostname"] = $_GET["hostname"];
		if (!isset($_GET["availability_method"]))     $pollerserver["availability_method"] = AVAIL_PING;
		if (!isset($_GET["ping_port"]))     $pollerserver["ping_port"] = 23;
		if (!isset($_GET["ping_timeout"]))  $pollerserver["ping_timeout"] = 400;
		if (!isset($_GET["ping_method"]))   $pollerserver["ping_method"] = 2;
		if (!isset($_GET["ping_retries"]))   $pollerserver["ping_retries"] = 2;


					/* create new ping socket for host pinging */
					$ping = new Net_Ping;
					$ping->host = $pollerserver;
					$ping->port = $pollerserver["ping_port"];
 
					/* perform the appropriate ping check of the host */
					if ($ping->ping($pollerserver["availability_method"], $pollerserver["ping_method"],
						$pollerserver["ping_timeout"], $pollerserver["ping_retries"])) {
          	$pollerserver_down = false;
						$color     = "#000000";
					}else{
						$pollerserver_down = true;
						$color     = "#ff0000";
					}
          ?>
					<tr>
				      <td class="textHeader">Ping Results<br>
    					<span style="font-size: 10px; font-weight: normal; color: <?php print $color; ?>; font-family: monospace;">
              <?php print $ping->ping_response; ?>
              <?php print "Port:".$ping->port; ?>
              <?php print "Host:".$ping->host["hostname"]; ?>
    					</span>
    				  </td>
          </tr>
      <?php  
            $ifaktive = "1";
            
            if (isset($_GET["hostname"])) {
                  $pollerserver_name = gethostbyaddr($_GET["hostname"]);
                 }else{
                   $pollerserver_name = gethostbyaddr($pollerserver["hostname"]);
                 }
                 
             if (is_ip($pollerserver_name) == FALSE){            
                $pollerserver_name = split_on_dot($pollerserver_name);
                }else{
                $message_text = "IP does not match with DNS request!";
                 $color     = "#ff0000";
               }

               ?>
              <tr>
                  <td class="textHeader">Test-Results for pollerserver : <br>
        					<span style="font-size: 12px; font-weight: normal; ?>; font-family: monospace;">
                  The ip <?php print $_GET["hostname"]; ?><br>
                  </span>
                  Returns the follow hostname: <br>
        					<span style="font-size: 12px; font-weight: normal; color: green; ?>; font-family: monospace;">
                  <?php print $pollerserver_name; ?><br>
                  </span>
                  Pleace turn it on field "Name for Pollerserver" for work correctly!<br>
        					<span style="font-size: 12px; font-weight: normal; color: <?php print $color; ?>; font-family: monospace;">
        					<?php print $message_text; ?>
                  </span>
                  </td>
              </tr> 
             <?php                  
                #  }// $poller_name_from_ip == FALSE 
              ?>
    </table>
	<?php 
	
	
		$header_label = "[new]";
		
		
		$_POST['name'] = $pollerserver_name;
		
    if (!isset($_GET["ping_port"]))     $pollerserver["ping_port"] = 23;
    if (!isset($_GET["ping_timeout"]))  $pollerserver["ping_timeout"] = 400;
    if (!isset($_GET["ping_method"]))   $pollerserver["ping_method"] = 2;
    if (!isset($_GET["ping_retries"]))   $pollerserver["ping_retries"] = 1;
	}

  html_start_box("<strong>Poller Server</strong> $header_label", "100%", $colors["header"], "3", "center", "");



   $form_array = array(
     "pollerserver_general_header" => array(
			"friendly_name" => "Genaral Pollerserver Options",
			"method" => "spacer",
			),
    "name" => array(
			"method" => "textbox",
			"friendly_name" => "Name for Pollerserver",
			"description" => "The name is the HOSTNAME about the Server .",
		  "value" =>  (isset($_GET["name"]) ? $_POST["name"] : $pollerserver["name"]),
		  "default" => "< leave blank in this step! >",
      "max_length" => "250",
			),
     "hostname" => array(
			"method" => "textbox",
			"friendly_name" => "IP for Poller Server <span style='font-size: 13px; font-weight: normal; color: red; font-family: monospace;'>*</span>" ,
			"description" => "The IP given to this Poller Server.",
		  "value" => (isset($_GET["hostname"]) ? $_GET["hostname"] : $pollerserver["hostname"]),
      "max_length" => "250",
			),
	"backup_poller_id" => array(
			"method" => "drop_sql",
			"friendly_name" => "Backup Poller Server",
			"description" => "Choose one backup pollerserver for the aktive pollerserer.",
			"value" => (isset($_GET["backup_poller_id"]) ? $_GET["backup_poller_id"] : $pollerserver["backup_poller_id"]),
			"none_value" => "None",
			"sql" => 'select id,name from poller_server WHERE name NOT LIKE "'.$pollerserver["name"].'" ORDER by name',
		),
	"aktive" => array(
			"friendly_name" => "Poller Server Active",
			"method" => "checkbox",
			"default" => "off",
			"description" => "Check to activate the Poller-Server.",
	    "value" => (isset($_GET["aktive"]) ? $_GET["aktive"] : $pollerserver["aktive"]),
			"flags" => "",
		),
	"pollerserver_ping_header" => array(
			"friendly_name" => "Ping Options",
			"method" => "spacer",
			),
  	"availability_method" => array(
			"method" => "drop_array",
			"friendly_name" => "Downed Device Detection",
			"description" => "The method Cacti will use to determine if a pollerserver is available for polling. ",
			"value" => (isset($_GET["availability_method"]) ? $_GET["availability_method"] : $pollerserver["availability_method"]),
       "array" => array(3=>"Ping")     
			),
     "ping_method" => array(
			"method" => "drop_array",
			"friendly_name" => "Ping Method",
			"description" => "The type of ping packet to sent. ",
			"value" => (isset($_GET["ping_method"]) ? $_GET["ping_method"] : $pollerserver["ping_method"]),
      "array" => array(1=>"ICMP Ping",3=>"TCP Ping",2=>"UDP Ping")
			),
     "ping_port" => array(
			"method" => "textbox",
			"friendly_name" => "Ping Port",
			"description" => "TCP or UDP port to attempt connection..",
		  "value" => (isset($_GET["ping_port"]) ? $_GET["ping_port"] : $pollerserver["ping_port"]),
      "max_length" => "250",
			),
     "	ping_timeout" => array(
			"method" => "textbox",
			"friendly_name" => "Ping Timeout Value",
			"description" => "The timeout value to use for host ICMP and UDP pinging.",
		  "value" => (isset($_GET["ping_timeout"]) ? $_GET["ping_timeout"] : $pollerserver["ping_timeout"]),
      "max_length" => "250",
			),
     "ping_retries" => array(
			"method" => "textbox",
			"friendly_name" => "Ping Retry Count",
			"description" => "After an initial failure, the number of ping retries Cacti will attempt before failing.",
		  "value" => (isset($_GET["ping_retries"]) ? $_GET["ping_retries"] : $pollerserver["ping_retries"]),
      "max_length" => "250",
			),
    "template_id" => array(
		  "method" => "hidden",
		  "value" => (isset($_GET["id"]) ? $_GET["id"] : $pollerserver["id"]),
		),
    "save_component" => array(
		  "method" => "hidden",
		  "value" => (isset($_GET["id"]) ? $_GET["id"] : $pollerserver["id"]),
		),
     );  
    
    
draw_edit_form(
		array(
			"config" => array(),
			"fields" => $form_array
			)
		);

	html_end_box();
  form_save_button("multipollerserver.php", "return");
 
	if ($_GET["name"] == "< leave blank in this step! >"){
		header("Location: multipollerserver.php?action=template_edit".(empty($pollerserver_id) ? "&hostname=".$_GET["hostname"] : $pollerserver["hostname"])."&name=".$_POST["name"]);
	}
}


/* Choose an device */
function device_choose() {
global $colors, $ds_choose_server;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request("page"));
	/* ==================================================== */

	/* clean up search string */
	if (isset($_REQUEST["filter"])) {
		$_REQUEST["filter"] = sanitize_search_string(get_request_var("filter"));
	}

	/* clean up sort_column string */
	if (isset($_REQUEST["sort_column"])) {
		$_REQUEST["sort_column"] = sanitize_search_string(get_request_var("sort_column"));
	}

	/* clean up sort_direction string */
	if (isset($_REQUEST["sort_direction"])) {
		$_REQUEST["sort_direction"] = sanitize_search_string(get_request_var("sort_direction"));
	}

	/* if the user pushed the 'clear' button */
	if (isset($_REQUEST["clear_x"])) {
		kill_session_var("sess_data_template_current_page");
		kill_session_var("sess_data_template_filter");
		kill_session_var("sess_data_template_sort_column");
		kill_session_var("sess_data_template_sort_direction");

		unset($_REQUEST["page"]);
		unset($_REQUEST["filter"]);
		unset($_REQUEST["sort_column"]);
		unset($_REQUEST["sort_direction"]);
	}

	/* remember these search fields in session vars so we don't have to keep passing them around */
	load_current_session_value("page", "sess_data_template_current_page", "1");
	load_current_session_value("filter", "sess_data_template_filter", "");
	load_current_session_value("sort_column", "sess_data_template_sort_column", "description");
	load_current_session_value("sort_direction", "sess_data_template_sort_direction", "ASC");


	html_start_box("<strong>Choose Devices for Pollerserver</strong>", "100%", $colors["header"], "3", "center");
	html_end_box();
	
	/* form the 'where' clause for our main sql query */
	$sql_where = "where (host.description like '%%" . get_request_var_request("filter") . "%%')";

	/* print checkbox form for validation */
	print "<form name='chk' method='post' action='multipollerserver.php?action=device_choose'>\n";


	html_start_box("", "100%", $colors["header"], "3", "center", "");


	$total_rows = db_fetch_cell("SELECT
		COUNT(host.id)
		FROM host
		$sql_where");


	$host_list = db_fetch_assoc("SELECT DISTINCT
		host.id,
		host.description AS device_name,
		host.hostname,
		host.poller_id
		FROM host
		INNER JOIN `poller_item` ON  `poller_item`.`host_id` = `host`.`id`
		$sql_where
		ORDER BY " . get_request_var_request("sort_column") . " " . get_request_var_request("sort_direction") .
		" LIMIT " . (read_config_option("num_rows_device")*(get_request_var_request("page")-1)) . "," . read_config_option("num_rows_device"));

	/* generate page list */
	$url_page_select = get_page_list(get_request_var_request("page"), MAX_DISPLAY_PAGES, read_config_option("num_rows_device"), $total_rows, "multipollerserver.php?filter=" . get_request_var_request("filter"));

	$nav = "<tr bgcolor='#" . $colors["header"] . "'>
		<td colspan='7'>
			<table width='100%' cellspacing='0' cellpadding='0' border='0'>
				<tr>
					<td align='left' class='textHeaderDark'>
						<strong>&lt;&lt; "; if (get_request_var_request("page") > 1) { $nav .= "<a class='linkOverDark' href='" . htmlspecialchars("multipollerserver.php?filter=" . get_request_var_request("filter") . "&page=" . (get_request_var_request("page")-1)) . "'>"; } $nav .= "Previous"; if (get_request_var_request("page") > 1) { $nav .= "</a>"; } $nav .= "</strong>
					</td>\n
					<td align='center' class='textHeaderDark'>
						Showing Rows " . ((read_config_option("num_rows_device")*(get_request_var_request("page")-1))+1) . " to " . ((($total_rows < read_config_option("num_rows_device")) || ($total_rows < (read_config_option("num_rows_device")*get_request_var_request("page")))) ? $total_rows : (read_config_option("num_rows_device")*get_request_var_request("page"))) . " of $total_rows [$url_page_select]
					</td>\n
					<td align='right' class='textHeaderDark'>
						<strong>"; if ((get_request_var_request("page") * read_config_option("num_rows_device")) < $total_rows) { $nav .= "<a class='linkOverDark' href='" . htmlspecialchars("multipollerserver.php?filter=" . get_request_var_request("filter") . "&page=" . (get_request_var_request("page")+1)) . "'>"; } $nav .= "Next"; if ((get_request_var_request("page") * read_config_option("num_rows_device")) < $total_rows) { $nav .= "</a>"; } $nav .= " &gt;&gt;</strong>
					</td>\n
				</tr>
			</table>
		</td>
	</tr>\n";
	
		
		print $nav;
		
		$display_text = array(
		"server_name" => array("Device Name", "ASC"),
		"hostname" => array("Device IP", "ASC"),
		"poller_server" => array("Pollerserver", "ASC"));

	html_header_sort_checkbox($display_text, get_request_var_request("sort_column"), get_request_var_request("sort_direction"), false);


	$i = 0;
	if (sizeof($host_list) > 0) {
		foreach ($host_list as $host) {
			form_alternate_row_color($colors["alternate"],$colors["light"],$i, 'line' . $host["id"]);
			form_selectable_cell((strlen(get_request_var_request("filter")) ? eregi_replace("(" . preg_quote(get_request_var_request("filter")) . ")", "<span style='background-color: #F8D93D;'>\\1</span>", htmlspecialchars($host["device_name"])) : htmlspecialchars($host["device_name"])) , $host["id"]);
			form_selectable_cell((strlen($_REQUEST["filter"]) ? eregi_replace("(" . preg_quote($_REQUEST["filter"]) . ")", "<span style='background-color: #F8D93D;'>\\1</span>", $host["hostname"]) : $host["hostname"]), $host["id"]);
			form_selectable_cell((strlen($_REQUEST["filter"]) ? eregi_replace("(" . preg_quote($_REQUEST["filter"]) . ")", "<span style='background-color: #F8D93D;'>\\1</span>", pollerid_to_pollername($host["poller_id"])) : pollerid_to_pollername($host["poller_id"])), $host["id"]);
			form_checkbox_cell($host["server_name"], $host["id"]);
			form_end_row();
			$i++;
		}
		/* put the nav bar on the bottom as well */
		print $nav;
	}else{
		print "<tr><td><em>No Pollerserver </em></td></tr>\n";
	}
	html_end_box(false);

	/* draw the dropdown containing a list of available actions for this form */
	draw_actions_dropdown($ds_choose_server);

	print "</form>\n";
} //device_choose




function template() {
	global $colors, $ds_actions;

if (is_fallbackmode() == TRUE) { // fallback_poller_id = 1
		html_start_box("<strong>The plugin multipollerserver is at fallbackmode</strong>", "100%", $colors["header"], "3", "center");
		print "<tr><td>Note the plugin is in fallbackmode. This means the used cactiversion is not compatible to the multipollerplugin or is not enabled for this version.</td></tr>\n";
		print "<tr><td>Cacti is now working in singelserver mode, so all devices are processed of the masterserver. </td></tr>\n";
		print "<tr><td>All settings where saved on system if i checked the details of the new cactiversion and fix the plugin the it give an new version how enabled the plugin.</td></tr>\n";
		html_end_box();
	}else{
	

		/* ================= input validation ================= */
		input_validate_input_number(get_request_var_request("page"));
		/* ==================================================== */

		/* clean up search string */
		if (isset($_REQUEST["filter"])) {
			$_REQUEST["filter"] = sanitize_search_string(get_request_var("filter"));
		}

		/* clean up sort_column string */
		if (isset($_REQUEST["sort_column"])) {
			$_REQUEST["sort_column"] = sanitize_search_string(get_request_var("sort_column"));
		}

		/* clean up sort_direction string */
		if (isset($_REQUEST["sort_direction"])) {
			$_REQUEST["sort_direction"] = sanitize_search_string(get_request_var("sort_direction"));
		}

		/* if the user pushed the 'clear' button */
		if (isset($_REQUEST["clear_x"])) {
			kill_session_var("sess_data_template_current_page");
			kill_session_var("sess_data_template_filter");
			kill_session_var("sess_data_template_sort_column");
			kill_session_var("sess_data_template_sort_direction");

			unset($_REQUEST["page"]);
			unset($_REQUEST["filter"]);
			unset($_REQUEST["sort_column"]);
			unset($_REQUEST["sort_direction"]);
		}

		/* remember these search fields in session vars so we don't have to keep passing them around */
		load_current_session_value("page", "sess_data_template_current_page", "1");
		load_current_session_value("filter", "sess_data_template_filter", "");
		load_current_session_value("sort_column", "sess_data_template_sort_column", "name");
		load_current_session_value("sort_direction", "sess_data_template_sort_direction", "ASC");


		html_start_box("<strong>Poller Server Templates</strong>", "100%", $colors["header"], "3", "center", "multipollerserver.php?action=template_edit");

		?>

		
		<tr bgcolor="#<?php print $colors["panel"];?>">
			<td>
			<form name="form_data_template" action="multipollerserver.php">
				<table width="100%" cellpadding="0" cellspacing="0">
					<tr>
						<td nowrap style='white-space: nowrap;' width="50">
							Search:&nbsp;
						</td>
						<td width="1">
							<input type="text" name="filter" size="40" value="<?php print htmlspecialchars(get_request_var_request("filter"));?>">
						</td>
						<td nowrap style='white-space: nowrap;'>
							&nbsp;<input type="submit" value="Go" title="Set/Refresh Filters">
							<input type="submit" name="clear_x" value="Clear" title="Clear Filters">
						</td>
					</tr>
				</table>
				<input type='hidden' name='page' value='1'>
			</form>
			</td>

		</tr>
		
		
		<?php

		html_end_box();

		?>
			<table width="100%" align="center">
				<tr>
					<td class="textInfo" valign="center">
						<span style="color: #c16921;">*</span><a href="<?php print htmlspecialchars("multipollerserver.php?action=device_choose");?>">Choose Devices for Pollerserver</a>
					</td>
				</tr>
				</table>
				
		<?php
		update_hostcount();
		/* form the 'where' clause for our main sql query */
		$sql_where = "where (poller_server.name like '%%" . get_request_var_request("filter") . "%%')";

		/* print checkbox form for validation */
		print "<form name='chk' method='post' action='multipollerserver.php'>\n";

		html_start_box("", "100%", $colors["header"], "3", "center", "");

		$total_rows = db_fetch_cell("SELECT
			COUNT(poller_server.id)
			FROM poller_server
			$sql_where");

	   $pollerserver_list = db_fetch_assoc("SELECT 
	    poller_server.id,
	    poller_server.name AS server_name,
	    poller_server.hostname,
	    poller_server.backup_poller_id,
	    poller_server.poller_lastrun,
	    poller_server.hostcount,
	    poller_server.aktive
	    FROM poller_server
	    $sql_where
			ORDER BY " . get_request_var_request("sort_column") . " " . get_request_var_request("sort_direction") .
			" LIMIT " . (read_config_option("num_rows_device")*(get_request_var_request("page")-1)) . "," . read_config_option("num_rows_device"));

		/* generate page list */
		$url_page_select = get_page_list(get_request_var_request("page"), MAX_DISPLAY_PAGES, read_config_option("num_rows_device"), $total_rows, "multipollerserver.php?filter=" . get_request_var_request("filter"));

		$nav = "<tr bgcolor='#" . $colors["header"] . "'>
			<td colspan='7'>
				<table width='100%' cellspacing='0' cellpadding='0' border='0'>
					<tr>
						<td align='left' class='textHeaderDark'>
							<strong>&lt;&lt; "; if (get_request_var_request("page") > 1) { $nav .= "<a class='linkOverDark' href='" . htmlspecialchars("multipollerserver.php?filter=" . get_request_var_request("filter") . "&page=" . (get_request_var_request("page")-1)) . "'>"; } $nav .= "Previous"; if (get_request_var_request("page") > 1) { $nav .= "</a>"; } $nav .= "</strong>
						</td>\n
						<td align='center' class='textHeaderDark'>
							Showing Rows " . ((read_config_option("num_rows_device")*(get_request_var_request("page")-1))+1) . " to " . ((($total_rows < read_config_option("num_rows_device")) || ($total_rows < (read_config_option("num_rows_device")*get_request_var_request("page")))) ? $total_rows : (read_config_option("num_rows_device")*get_request_var_request("page"))) . " of $total_rows [$url_page_select]
						</td>\n
						<td align='right' class='textHeaderDark'>
							<strong>"; if ((get_request_var_request("page") * read_config_option("num_rows_device")) < $total_rows) { $nav .= "<a class='linkOverDark' href='" . htmlspecialchars("multipollerserver.php?filter=" . get_request_var_request("filter") . "&page=" . (get_request_var_request("page")+1)) . "'>"; } $nav .= "Next"; if ((get_request_var_request("page") * read_config_option("num_rows_device")) < $total_rows) { $nav .= "</a>"; } $nav .= " &gt;&gt;</strong>
						</td>\n
					</tr>
				</table>
			</td>
			
		</tr>\n";

			
		print $nav;

		$display_text = array(
			"server_name" => array("Pollerserver Name", "ASC"),
			"hostname" => array("Pollerserver IP", "ASC"),
			"backup_server_name" => array("Backup Pollerserver Name", "ASC"),
	 		"poller_lastrun" => array("Last run", "ASC"),
	 		"hostcount" => array("Hosts", "ASC"),
			"aktive" => array("Status", "ASC"));

		html_header_sort_checkbox($display_text, get_request_var_request("sort_column"), get_request_var_request("sort_direction"), false);

		$i = 0;
		if (sizeof($pollerserver_list) > 0) {
			foreach ($pollerserver_list as $pollerserver) {
				form_alternate_row_color($colors["alternate"],$colors["light"],$i, 'line' . $pollerserver["id"]);
				form_selectable_cell("<a class='linkEditMain' href='" . htmlspecialchars("multipollerserver.php?action=template_edit&id=" . $pollerserver["id"]) . "'>" . (strlen(get_request_var_request("filter")) ? eregi_replace("(" . preg_quote(get_request_var_request("filter")) . ")", "<span style='background-color: #F8D93D;'>\\1</span>", htmlspecialchars($pollerserver["server_name"])) : htmlspecialchars($pollerserver["server_name"])) . "</a>", $pollerserver["id"]);
				form_selectable_cell((strlen($_REQUEST["filter"]) ? eregi_replace("(" . preg_quote($_REQUEST["filter"]) . ")", "<span style='background-color: #F8D93D;'>\\1</span>", $pollerserver["hostname"]) : $pollerserver["hostname"]), $pollerserver["id"]);
				form_selectable_cell((strlen($_REQUEST["filter"]) ? eregi_replace("(" . preg_quote($_REQUEST["filter"]) . ")", "<span style='background-color: #F8D93D;'>\\1</span>", pollerid_to_pollername($pollerserver["backup_poller_id"])) : pollerid_to_pollername($pollerserver["backup_poller_id"])), $pollerserver["id"]);
				form_selectable_cell((strlen($_REQUEST["filter"]) ? eregi_replace("(" . preg_quote($_REQUEST["filter"]) . ")", "<span style='background-color: #F8D93D;'>\\1</span>", $pollerserver["poller_lastrun"]) : date_time($pollerserver["poller_lastrun"])), $pollerserver["id"]);
				form_selectable_cell((strlen($_REQUEST["filter"]) ? eregi_replace("(" . preg_quote($_REQUEST["filter"]) . ")", "<span style='background-color: #F8D93D;'>\\1</span>", $pollerserver["hostcount"]) : $pollerserver["hostcount"]), $pollerserver["id"]);
				form_selectable_cell((($pollerserver["aktive"] == "on") ? "Active" : "Disabled"), $pollerserver["id"]);
				form_checkbox_cell($pollerserver["server_name"], $pollerserver["id"]);
				form_end_row();
				$i++;
			}

			/* put the nav bar on the bottom as well */
			print $nav;
		}else{
			print "<tr><td><em>No Pollerserver Templates</em></td></tr>\n";
		}
		html_end_box(false);

		/* draw the dropdown containing a list of available actions for this form */
		draw_actions_dropdown($ds_actions);

		print "</form>\n";
	}	// fallbackmode
}// template





