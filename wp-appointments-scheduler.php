<?php
/*
Plugin Name: Appointments Scheduler
Plugin URI: http://www.oreginaldesigns.com
Description: This wordpress plugin will let you create and manage schedules. 
			 You can add, delete, and manage appointments for each schedule.
			 Individual schedules can be displayed on the website and may be
			 enabled to allow online reservations by registered users. 
Version: 1.5
Author: Prashanth Balakrishnan
Author URI: http://www.oreginaldesigns.com
*/


/*  
	Copyright 2008  Prashanth Balakrishnan  (email : pbalakri@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.   

    You should have received a copy of the GNU General Public License         
    along with this program; if not, write to the Free Software               
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA 
*/


### Load WP-Config File If This File Is Called Directly
if (!function_exists('add_action')) {
   $wp_root = '../../..';
   if (file_exists($wp_root.'/wp-load.php'))
      require_once($wp_root.'/wp-load.php');
   else 
      require_once($wp_root.'/wp-config.php');
}


### Use WordPress 2.6 Constants
if (!defined('WP_CONTENT_DIR')) 
   define( 'WP_CONTENT_DIR', ABSPATH.'wp-content');
   
if (!defined('WP_CONTENT_URL'))
   define('WP_CONTENT_URL', get_option('siteurl').'/wp-content');

if (!defined('WP_PLUGIN_DIR')) 
   define('WP_PLUGIN_DIR', WP_CONTENT_DIR.'/plugins');
   
if (!defined('WP_PLUGIN_URL')) 
   define('WP_PLUGIN_URL', WP_CONTENT_URL.'/plugins');

### Create Text Domain For Translations
add_action('init', 'ac_textdomain');
function ac_textdomain() {
   if (!function_exists('wp_print_styles')) {
      load_plugin_textdomain('wp-appointments-schedules', 'wp-content/plugins/wp-appointment-schedules');
   }
   else {
      load_plugin_textdomain('wp-appointments-schedules', false, 'wp-appointments-schedules');
   }
}

### Appointments Table Name
global $wpdb;
$wpdb->as_schedules = $wpdb->prefix.'as_schedules';
$wpdb->as_appointments = $wpdb->prefix.'as_appointments';
$wpdb->as_open_schedules = $wpdb->prefix.'as_open_schedules';

### Function: Appointments Administration Menu
add_action('admin_menu', 'as_menu');

function as_menu() {
   if (function_exists('add_menu_page'))
      add_menu_page(__('Appointments', 'wp-appointments-schedules'), 
                    __('Scheduler', 'wp-appointments-schedules'), 
                       'manage_as', 'wp-appointments-schedules/as-schedules.php');
   if (function_exists('add_submenu_page')) {
      add_submenu_page('wp-appointments-schedules/as-schedules.php', 
                        __('Manage Schedules', 'wp-appointments-schedules'), 
                        __('Manage Schedules', 'wp-appointments-schedules'), 
                        'manage_as', 'wp-appointments-schedules/as-schedules.php');
      add_submenu_page('wp-appointments-schedules/as-schedules.php', 
                        __('Manage Appointments', 'wp-appointments-schedules'), 
                        __('Manage Appointments', 'wp-appointments-schedules'), 
                        'manage_as', 
                        'wp-appointments-schedules/as-appointments.php');
   }
}

### Function: Create appointment and schedule tables
function create_as_tables() {
   global $wpdb;
   if(@is_file(ABSPATH.'/wp-admin/upgrade-functions.php'))
      include_once(ABSPATH.'/wp-admin/upgrade-functions.php'); 
   elseif(@is_file(ABSPATH.'/wp-admin/includes/upgrade.php'))
      include_once(ABSPATH.'/wp-admin/includes/upgrade.php');
   else
      die('We have problem finding your \'/wp-admin/upgrade-functions.php\' and \'/wp-admin/includes/upgrade.php\'');
      
   // Create Appointment Scheduler Tables (3 Tables)
   $charset_collate = '';
   if($wpdb->supports_collation()) {
      if(!empty($wpdb->charset)) {
         $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
      }
      if(!empty($wpdb->collate)) {
         $charset_collate .= " COLLATE $wpdb->collate";
      }
   }
   $create_table = array();
   
   // Table containing a list of all schedules 
   $create_table['as_schedules'] =      "CREATE TABLE $wpdb->as_schedules (".
                                        "id INT(11) NOT NULL AUTO_INCREMENT,".
                                        "name VARCHAR(200) NOT NULL DEFAULT '',".
                                        "start_time TIME,".
                                        "end_time TIME,".
                                        "time_interval VARCHAR(2) NOT NULL,".
                                        "notify VARCHAR(100) NOT NULL DEFAULT '',".
                                        "confirmation_text TEXT NOT NULL,".
                                        "date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,".
                                        "UNIQUE KEY(name), ".
                                        "PRIMARY KEY (id)) $charset_collate;";
   
   // Table containing a list of appointments
   $create_table['as_appointments'] =   "CREATE TABLE $wpdb->as_appointments (".
                                        "id INT(11) NOT NULL AUTO_INCREMENT,".
                                        "schedule_id INT(11) NOT NULL DEFAULT '0',".
                                        "appointment_name VARCHAR(250) DEFAULT '',".
                                        "appointment_time TIME,".
                                        "appointment_date DATE,".
                                        "FOREIGN KEY(schedule_id) REFERENCES ".$wpdb->as_schedules."(id)".
                                        " ON UPDATE CASCADE ON DELETE CASCADE,".
                                        "PRIMARY KEY (id),".
                                        "UNIQUE (schedule_id, appointment_date, appointment_name),".
                                        "UNIQUE (schedule_id, appointment_date, appointment_time)".
                                        ") $charset_collate;";
   
   // Table containing a list of open schedules
   $create_table['as_open_schedules'] = "CREATE TABLE $wpdb->as_open_schedules (".
                                        "id int(11) NOT NULL AUTO_INCREMENT,".
                                        "schedule_id int(11) NOT NULL DEFAULT '0',".
                                        "open_date DATE,".
                                        "FOREIGN KEY(schedule_id) REFERENCES ".$wpdb->as_schedules."(id)".
                                        " ON UPDATE CASCADE ON DELETE CASCADE,".
                                        "UNIQUE KEY(schedule_id, open_date),".
                                        "PRIMARY KEY(id)".
                                        ") $charset_collate;";
	
   maybe_create_table($wpdb->as_schedules, $create_table['as_schedules']);
   maybe_create_table($wpdb->as_appointments, $create_table['as_appointments']);
   maybe_create_table($wpdb->as_open_schedules, $create_table['as_open_schedules']);
	
   // Set 'manage_as' Capabilities To Administrator	
   $role = get_role('administrator');
   if(!$role->has_cap('manage_as')) {
      $role->add_cap('manage_as');
   }
} // End function create_as_tables()

### Function: Displays Appointment Scheduler header
function as_header() {
   if(@file_exists(TEMPLATEPATH.'/styles.css')) {
?>
      <link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri()."/css/styles.css"; ?>" type="text/css" media="screen" />	
<?php
   } 
   else{
?>
      <link rel="stylesheet" href="<?php echo WP_PLUGIN_URL."/wp-appointments-schedules/css/styles.css"; ?>" type="text/css" media="screen" />	
<?php
   }
} // End function as_header()

###Function: Displays a schedule as main content on the page
function displaySchedule($content){
   global $wpdb;
   global $userdata;
   //$wpdb->show_errors();
   get_currentuserinfo();

   $schedules = $wpdb->get_results("SELECT NAME, START_TIME, END_TIME, TIME_INTERVAL FROM $wpdb->as_schedules");
   $i = 0;
   foreach($schedules as $schedule){
      $pattern = "/\[\[DISPLAYSCHEDULE_".$schedule->NAME."\]\]/i";
	  if(!preg_match($pattern, $content))
	     continue;
      $scheduleNames[$i]["startTime"]      = strtotime($schedule->START_TIME);
      $scheduleNames[$i]["endTime"]        = strtotime($schedule->END_TIME);
      $scheduleNames[$i]["interval"]       = $schedule->TIME_INTERVAL;
      $scheduleNames[$i]["scheduleName"]   = $schedule->NAME;
      $i++;
   }
   if(sizeof($scheduleNames) > 0){
      if(isset($_POST['reserve']) && preg_match("/^[\w-_\.]+$/", trim($_POST['schedule_name'])) && is_user_logged_in()){ 
         if($wpdb->query($wpdb->prepare("INSERT INTO $wpdb->as_appointments (SCHEDULE_ID, APPOINTMENT_NAME, APPOINTMENT_TIME, APPOINTMENT_DATE) SELECT ID, %s, %s, %s FROM $wpdb->as_schedules WHERE NAME=%s", $userdata->user_login, $_POST['appt_time'], $_POST["chosenDate"], trim($_POST['schedule_name']))) === FALSE)
            echo "<strong>Could not reserve slot. Make sure you do not have another appointment scheduled for the same day. Please try again.</strong>";
         else{
            $schedule_results = $wpdb->get_results("SELECT NOTIFY, CONFIRMATION_TEXT FROM $wpdb->as_schedules WHERE NAME='".trim($_POST['schedule_name'])."'");            
            //Send email to the owner of the schedule if a valid email address is specified.
            foreach($schedule_results as $schedule_result){
               if(preg_match("|[a-z0-9_.-]+@[a-z0-9_.-]+(?!.*<)|i", $schedule_result->NOTIFY))
                  wp_mail($schedule_owner, "New Appointment", trim($_POST['schedule_name']." : ".trim($_POST["chosenDate"])." : ".trim($_POST['appt_time'])));
               //Send email to the person to confirm appointment creation
                wp_mail($userdata->user_email, "Appointment Confirmation", "Your ".$_POST['appt_time']." appointment on ". $_POST["chosenDate"]." has been successfully made.". $schedule_result->CONFIRMATION_TEXT);
            }
         }
      }    
?>
      <div class="as_section">
         <form name="choose_schedule" id="choose_schedule" action="<?php echo get_permalink(); ?>" method="post">
         <input type="text" class="text" name="chosenDate" id="chosenDate" readonly="readonly" value="<?php echo date("Y-m-d"); ?>" />
         <input type="hidden" name="popupCalendarDate" id="popupCalendarDate_event" value="<?php echo date(Y).'-0-0'; ?>" />
         <img src="<?php echo WP_PLUGIN_URL.'/wp-appointments-schedules/images/date.png'; ?>" width="16" height="16" id="showPopupCalendarImage_event" title="Date selector" alt="Date Selector Tool" />
<?php 
         require_once("as-management-js.php");
         inputdate("chosenDate");
?>
         <input type="submit" name="submit" id="submit" value="Go" />
         </form>
      </div>
<?php
      $time = "";
      $interval = "";
      $scheduleName = "";
	  for($i=0; $i<sizeof($scheduleNames); $i++){	  	 
         $scheduleName = $scheduleNames[$i]["scheduleName"];
	   	 $interval     = $scheduleNames[$i]["interval"];
	   	 $start_time   = $scheduleNames[$i]["startTime"];
	   	 $end_time     = $scheduleNames[$i]["endTime"];
	     if(isset($_POST["chosenDate"]))
	        $appt_date = $_POST["chosenDate"];
	     elseif(isset($_GET["chosenDate"]))
	        $appt_date = $_GET["chosenDate"];
	     else
	     	$appt_date = date("Y-m-d");
	     if(strtotime($appt_date) < strtotime(date("Y-m-d"))){
	        echo "<strong>You are attempting to schedule an appointment in the past.</strong><br/>"; 
	        $appt_date = date("Y-m-d");
	     }
	     
	     $prev_scheduled_appointment = $wpdb->get_var($wpdb->prepare("SELECT APPOINTMENT_TIME FROM $wpdb->as_appointments a, $wpdb->as_schedules s WHERE APPOINTMENT_NAME = %s AND APPOINTMENT_DATE = %s AND NAME = %s AND a.SCHEDULE_ID = s.ID", $userdata->user_login, $appt_date, $scheduleName));    
	     $open_schedule = $wpdb->get_row("SELECT COUNT(os.ID) AS OPENCOUNT FROM $wpdb->as_open_schedules os INNER JOIN $wpdb->as_schedules s ON os.SCHEDULE_ID=s.ID WHERE s.NAME LIKE '$scheduleName' AND os.OPEN_DATE ='$appt_date'");
	     if($open_schedule->OPENCOUNT==0)
	        $schedule_text = "<strong>$scheduleName ($appt_date)</strong><br/>Appointments for this day are not accepted at this time.";
	     else{
	        $schedule_text = (is_user_logged_in())?
	                         "<form name='schedule_appointment' action='".get_permalink()."' method='POST'>".
	                         "<input type='hidden' name='chosenDate' value='$appt_date' />".
	                         "<input type='hidden' name='schedule_name' value='$scheduleName' />": "";
	        $schedule_text .= "<table class='widefat'><tr><th colspan=\"3\">$scheduleName ($appt_date)</th></tr>";
	        while($start_time <= $end_time){
	           $time_arr = getdate($start_time);
	           //Append a leading zero for hours and minutes < 10 (single digits)
	           $hr  = $time_arr['hours']  <10 ? "0".$time_arr['hours']   : $time_arr['hours'];
	           $min = $time_arr['minutes']<10 ? "0".$time_arr['minutes'] : $time_arr['minutes'];
	           if(isset($_POST["chosenDate"]))
	              $date_to_display = $_POST["chosenDate"];
	           else if(isset($_GET["chosenDate"]))
	              $date_to_display = $_GET["chosenDate"];
	           else 
	              $date_to_display = date(Y)."-".date(m)."-".date(j); 
	           // Get appointment for that time, if any
	           $appointments = $wpdb->get_row("SELECT APPOINTMENT_NAME FROM $wpdb->as_appointments a INNER JOIN $wpdb->as_schedules s ON a.SCHEDULE_ID=s.ID WHERE upper(s.NAME) LIKE upper('".$scheduleName."') and a.APPOINTMENT_DATE='".$date_to_display."' AND APPOINTMENT_TIME='$hr:$min:00'");
	           $slot_availability = "Open";
	           if($appointment = $appointments->APPOINTMENT_NAME)
	              $slot_availability = "Closed";
	           $schedule_text .= "$hr:$min:00"==$prev_scheduled_appointment?"<tr style='background:#73a0c5;color:#fff;'>":"<tr>";
	           if(is_user_logged_in() && $slot_availability=="Open") // User logged in and slot is open
	              $schedule_text .= "<td><input type='radio' name='appt_time' value='$hr:$min:00' /></td>";
	           else
	              $schedule_text .= "<td>&nbsp;</td>";
	           // Display time
	           $schedule_text .= "<td>$hr:$min</td>";
	           // Display whether slot is open or closed
               $schedule_text .= "<td>$slot_availability</td>";
               $schedule_text .= "</tr>";
               //Increment time by specified minutes
               $start_time += intval($interval)*60;
	        } // End while($start_time <= $end_time))
	         
            // Show Reserve button if user is logged in
	        $schedule_text .= is_user_logged_in()?"<tr><td colspan='3'><input type='submit' name='reserve' value='Reserve' /></td></tr></table></form>":"</table>";
	     } // End else
	     $pattern = "/\[\[DISPLAYSCHEDULE_".$scheduleName."\]\]/i";
	     $content = preg_replace($pattern, $schedule_text, $content);
	  } //End for     
   } // End if($scheduleName!="")
   
   // For PHP 5
   //$content = str_ireplace('[[DISPLAYSCHEDULE_'.$scheduleName.']]',$schedule_text, $content); 
   
   return $content;
} // End function displaySchedule()

add_action('activate_wp-appointments-schedules/wp-appointments-scheduler.php', 'create_as_tables');
add_action('admin_head', 'as_header');
add_filter('the_content', 'displaySchedule');
?>
