<?php
/*
+----------------------------------------------------------------+
|											                        |
|	WordPress 2.6 Plugin: WP-Appointments-Scheduler				 	|
|	Copyright (c) 2008 Prashanth Balakrishnan					    |
|											                        |
|	File Written By:								                |
|	- Prashanth Balakrishnan						             	|
|	- http://www.oreginaldesigns.com		             			|
|										                        	|
|	File Information:				                				|
|	- Manage Schedules				                				|
|	- wp-content/plugins/wp-appointments-schedules/as-schedules.php	|
|											                        |
+----------------------------------------------------------------+
*/


### Check Whether User Can Manage Polls
if(!current_user_can('manage_as')) {
	die('Access Denied');
}
require_once("as-management-js.php");
$base_name = plugin_basename('wp-appointments-schedules/as-schedules.php');
$base_page = 'admin.php?page='.$base_name;

### Form Processing 
if(!empty($_POST['create']) && trim($_POST['new_schedule_name'])!=""){
   //$wpdb->show_errors();
   $newScheduleName = "";
   if(preg_match("/^[\w-_\.]+$/", trim($_POST['new_schedule_name'])))
      $newScheduleName = trim($_POST['new_schedule_name']);
   if(intval($_POST['start_hr']) <= 12 && intval($_POST['start_hr']) >=0)
      $start_hour = $_POST['start_ampm']=="pm"
                    ?intval($_POST['start_hr'])+12
                    :$_POST['start_hr'];
   else
      $start_hour = "00";
   $start_min  = intval($_POST['start_min']) >=0 && intval($_POST['start_min']) < 60
                 ?$_POST['start_min']
                 :"00";
   
   if(intval($_POST['end_hr']) <= 12 && intval($_POST['end_hr']) >=0)
      $end_hour = $_POST['end_ampm']=="pm"
                  ?intval($_POST['end_hr'])+12
                  :$_POST['end_hr'];
   else
      $end_hour = "00";
   $end_min    = intval($_POST['end_min']) >=0 && intval($_POST['end_min']) < 60
                 ?$_POST['end_min']
                 :"00";
                 
   $interval   = intval($_POST['interval']);
   
   $start_time = strtotime($start_hour.":".$start_min);
   $end_time   = strtotime($end_hour.":".$end_min);
   if($newScheduleName != ""){
	   if($end_time > $start_time && $interval > 0 && $interval<=60){
	      if($wpdb->query($wpdb->prepare("INSERT INTO $wpdb->as_schedules(NAME, 
	                                   START_TIME, END_TIME, TIME_INTERVAL, 
	                                   NOTIFY, CONFIRMATION_TEXT) VALUES(%s, %s, %s, %s, %s, %s)", 
	                                   $_POST['new_schedule_name'], 
	                                   $start_hour.":".$start_min.":00", 
	                                   $end_hour.":".$end_min.":00", 
	                                   $interval, 
	                                   $_POST['new_schedule_notify'],
	                                   $_POST['new_schedule_confirmation']))===FALSE)
	         echo "Error inserting the appointment. Make sure you don't have the same appointment scheduled at a different time.<br/>";
	   } 
	   else
	      echo "The end-time specified should be later than the start-time of the schedule.<br/>";
   }
   else{
      echo "Invalid character in schedule name OR schedule name already used. Valid characters are alpha-numeric characters, underscores and hyphens.";
   }
}
else if(!empty($_POST['delete']) && is_numeric($_POST['schedule_id'])){
   if($wpdb->query($wpdb->prepare("DELETE FROM $wpdb->as_schedules WHERE ID = %d",
                                 $_POST['schedule_id']))===FALSE)
      echo "Could not delete the specified appointment. Not quite sure what happened there. Try deleting it once more.<br/>";
}
?>
<div class="as_section">
<h2>Manage Schedules</h2>
</div>
<div class="as_section_heading" class="as_section">
   <div class="schedule_col1">Schedule Name</div>
   <div class="schedule_col2">Start/End</div>
   <div class="schedule_col3">Interval(min)</div>
   <div class="schedule_col4">Contact Info</div>
   <div class="schedule_col5">Confirmation text</div>
   <div class="schedule_col6">Actions</div>
</div>
<div class="as_section">
<form name="new_schedule" id="new_schedule" action="<?php echo $base_page; ?>" method="post">
   <div class="schedule_col1"><input class="text" type="text" size="15" name="new_schedule_name" id="new_schedule_name" value="Enter a name" onClick="clearText(this)" onFocus="clearText(this)"/></div>
   <div class="schedule_col2">
      Start: <select name="start_hr">
         <option value="01">01</option>
         <option value="02">02</option>
         <option value="03">03</option>
         <option value="04">04</option>
         <option value="05">05</option>
         <option value="06">06</option>
         <option value="07">07</option>
         <option value="08">08</option>
         <option value="09">09</option>
         <option value="10">10</option>
         <option value="11">11</option>
         <option value="00">12</option>
      </select>
      <select name="start_min">
         <option value="00">00</option>
         <option value="15">15</option>
         <option value="30">30</option>
         <option value="45">45</option>
      </select>
      <select name="start_ampm">
         <option value="am">AM</option>
         <option value="pm">PM</option>
      </select><br/>
      End : <select name="end_hr">
         <option value="01">01</option>
         <option value="02">02</option>
         <option value="03">03</option>
         <option value="04">04</option>
         <option value="05">05</option>
         <option value="06">06</option>
         <option value="07">07</option>
         <option value="08">08</option>
         <option value="09">09</option>
         <option value="10">10</option>
         <option value="11">11</option>
         <option value="00">12</option>
      </select>
      <select name="end_min">
         <option value="00">00</option>
         <option value="15">15</option>
         <option value="30">30</option>
         <option value="45">45</option>
      </select>
      <select name="end_ampm">
         <option value="am">AM</option>
         <option value="pm">PM</option>
      </select>
   </div>
   <div class="schedule_col3">
      <select name="interval">
         <?php
            $most_freq_used_interval = $wpdb->get_var("SELECT time_interval t , count(time_interval) c FROM wp_as_schedules w group by time_interval order by c desc LIMIT 1");
            for($i=5; $i<=60; $i+=5){
		 ?>
		       <option value="<?php echo $i; ?>" <?php if($most_freq_used_interval==$i) echo "selected=\"selected\"";?>><?php echo $i; ?></option>     
		 <?php		
			}
         ?>
      </select>
   </div>
   <div class="schedule_col4"><input  class="text" type="text" name="new_schedule_notify" id="new_schedule_notify" value="Enter email, phone etc." onClick="clearText(this)" onFocus="clearText(this)" /></div>
   <div class="schedule_col5"><textarea name="new_schedule_confirmation" id="new_schedule_confirmation"></textarea></div>
   <div class="schedule_col6"><input class="button-secondary" type="submit" name="create" id="create" value="Create" /></div>
</form>
</div>
<?php
   $schedules = $wpdb->get_results("SELECT ID, NAME, START_TIME, END_TIME, TIME_INTERVAL, NOTIFY, CONFIRMATION_TEXT FROM $wpdb->as_schedules ORDER BY NAME ASC");
   foreach($schedules as $schedule){
?>
      <div class="as_section">
      <form name="<?php echo "schedule".$schedule->ID; ?>" id="<?php echo "schedule".$schedule->ID; ?>" action="<?php echo $base_page; ?>" method="post">
      <input type="hidden" name="schedule_id" value="<?php echo $schedule->ID; ?>">
      <div class="schedule_col1"><?php echo $schedule->NAME; ?></div>
      <div class="schedule_col2">
         Start: <?php echo $schedule->START_TIME ?><br/>
         End : <?php echo $schedule->END_TIME; ?>
      </div>
      <div class="schedule_col3"><?php echo $schedule->TIME_INTERVAL; ?></div>
      <div class="schedule_col4"><?php echo $schedule->NOTIFY; ?></div>
      <div class="schedule_col5"><?php echo $schedule->CONFIRMATION_TEXT; ?></div>
      <div class="schedule_col6">
         <input type="submit" class="button-secondary" name="delete" value="Delete">
      </div>
      </form>
      </div>
<?		
   }
?>
