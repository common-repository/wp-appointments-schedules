<?php
/*
+----------------------------------------------------------------+
|										|
|	WordPress 2.6 Plugin: WP-Appointments-Scheduler				|
|	Copyright (c) 2008 Prashanth Balakrishnan				|
|										|
|	File Written By:							|
|	- Prashanth Balakrishnan						|
|	- http://www.oreginaldesigns.com					|
|										|
|	File Information:							|
|	- Manage Schedules							|
|	- wp-content/plugins/wp-appointments-schedules/as-appointments.php	|
|										|
+----------------------------------------------------------------+
*/


### Check Whether User Can Manage Polls
if(!current_user_can('manage_as')) {
   die('Access Denied');
}
require_once("as-management-js.php");
$base_name = plugin_basename('wp-appointments-schedules/as-appointments.php');
$base_page = 'admin.php?page='.$base_name;

### Form Processing 
if(isset($_POST['chosen_date']) && isset($_POST['schedule_list'])){
   if(isset($_POST['save'])){		
      if(isset($_POST['modified_appts'])){
         foreach($_POST['modified_appts'] as $mod_appt){
            if(trim($_POST['appt_'.$mod_appt])==""){ //THe existing appointment was deleted
               if($wpdb->query($wpdb->prepare("DELETE FROM a USING $wpdb->as_appointments a INNER JOIN $wpdb->as_schedules s ON a.SCHEDULE_ID=s.ID WHERE a.APPOINTMENT_DATE=%s AND a.APPOINTMENT_TIME=%s AND s.NAME=%s", $_POST['chosen_date'], $mod_appt.":00", $_POST['schedule_list']))===FALSE)
                  $message = "Could not delete appointment. Not quite sure what happened there. You could try it again.";
               else
                  $message = "Appointment Deleted.";
			}
            else if(preg_match("/^[\w\s-_\.]+$/", trim($_POST['appt_'.$mod_appt]))){ //An appointment was modified
               $old_appt_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->as_appointments a INNER JOIN  $wpdb->as_schedules s ON a.SCHEDULE_ID=s.ID WHERE a.APPOINTMENT_DATE=%s AND a.APPOINTMENT_TIME=%s AND s.NAME=%s", $_POST['chosen_date'], $mod_appt.":00", $_POST['schedule_list']));
               if($old_appt_exists > 0){ // Update an appointment if one exists for that date and time
			      if($wpdb->query($wpdb->prepare("UPDATE $wpdb->as_appointments a, $wpdb->as_schedules s SET a.APPOINTMENT_NAME=%s WHERE a.SCHEDULE_ID=s.ID AND a.APPOINTMENT_DATE=%s AND a.APPOINTMENT_TIME=%s AND s.NAME=%s",$_POST['appt_'.$mod_appt], $_POST['chosen_date'], $mod_appt.":00", $_POST['schedule_list']))===FALSE)
                     $message = "Failed to update the ".$_POST['appt_'.$mod_appt]." appointment! Make sure you don't have another appointment with the same title on this day.";
			      else
			         $message = "Appointment Updated.";
               }
               else if($old_appt_exists == 0){ // Insert a new appointment if date/time slot is empty
                  if($wpdb->query($wpdb->prepare("INSERT INTO $wpdb->as_appointments (SCHEDULE_ID, APPOINTMENT_NAME, APPOINTMENT_DATE, APPOINTMENT_TIME) SELECT s.ID, %s, %s, %s FROM $wpdb->as_schedules s WHERE s.NAME=%s",$_POST['appt_'.$mod_appt], $_POST['chosen_date'], $mod_appt.":00", $_POST['schedule_list']))===FALSE)                  	
                     $message = "Failed to create the ".$_POST['appt_'.$mod_appt]." appointment! Make sure you don't have another appointment with the same title on this day.";
                  else
                     $message = "Appointment Created.";
               }
            }
			else
               $message = "Operation failed. Not quite sure what happened there. Make sure you have valid characters in the appointment name.<br/>";
         }
      }
   }
   else if(isset($_POST['open'])){ // If schedule is supposed to be opened up to allow appointments to be made for that day
      if($wpdb->query($wpdb->prepare("INSERT INTO $wpdb->as_open_schedules(SCHEDULE_ID, OPEN_DATE) SELECT ID, '".$_POST['chosen_date']."' FROM $wpdb->as_schedules WHERE NAME='".$_POST['schedule_list']."'"))===FALSE)
         $message = "Could not open schedule.";
         
   }
   else if(isset($_POST['close'])){ // If schedule is supposed to be closed for appointments for that day
      if($wpdb->query($wpdb->prepare("DELETE FROM os USING $wpdb->as_open_schedules os INNER JOIN $wpdb->as_schedules s ON s.ID=os.SCHEDULE_ID WHERE s.NAME LIKE '".$_POST['schedule_list']."'"))===FALSE)
         $message = "Unable to close schedule.";
   }
?>
   <div class="message"><?php echo $message; ?></div>               
<?php   
}
?>
<div class="as_section">
   <h2>Manage Appointments</h2>
   <form name="choose_schedule" id="choose_schedule" action='<?php echo $base_page; ?>' method="post">
<?php
   $schedules = $wpdb->get_results("SELECT NAME FROM $wpdb->as_schedules ORDER BY NAME ASC");
?>
   <select class="text" name="schedule_list" id="schedule_list">
   <option value="">Choose a schedule</option>
<?php
   foreach($schedules as $schedule){
?>
      <option value="<?php echo $schedule->NAME; ?>" <?php if(isset($_POST['schedule_list']) && $_POST['schedule_list']==$schedule->NAME) echo "selected=\"selected\""; ?> ><?php echo $schedule->NAME; ?></option>
<?php
   }
?>
   </select>
   <input type="text" class="text" name="chosen_date" id="chosen_date" readonly="readonly" value="<?php if(isset($_POST["chosen_date"])) echo $_POST["chosen_date"]; else echo date(Y).'-'.date(m).'-'.date(j); ?>" />
   
   <input type="hidden" name="popupCalendarDate" id="popupCalendarDate_event" value="<?php echo date(Y).'-0-0'; ?>" />
   <img src="<?php echo WP_PLUGIN_URL.'/wp-appointments-schedules/images/date.png'; ?>" width="16" height="16" id="showPopupCalendarImage_event" title="Date selector" alt="Date Selector Tool" />
<?php
   inputdate("chosen_date");
?>
   <input type="submit" class="button-secondary" name="submit" id="submit" value="Go" />
   </form>
</div>
<?php
if(!empty($_POST['schedule_list']) && trim($_POST['schedule_list'])!=""){ //A schedule and a date is selected
   $open_schedules = $wpdb->get_row("SELECT COUNT(s.ID) AS OPENCOUNT FROM $wpdb->as_open_schedules os INNER JOIN $wpdb->as_schedules s on s.ID=os.SCHEDULE_ID WHERE s.NAME LIKE '".trim($_POST['schedule_list'])."' AND os.OPEN_DATE='".$_POST['chosen_date']."'"); 
   if($open_schedules->OPENCOUNT==0){
   //Display form to open schedule
?>
   <form name="open_schedule" action="<?php echo $base_page; ?>" method="post">
      <input type="hidden" name="schedule_list" value="<?php  echo $_POST['schedule_list']; ?>" />
      <input type="hidden" name="chosen_date" value="<?php echo $_POST['chosen_date']; ?>" />
      You have not opened up this date to allow appointments to be scheduled. If you would like to allow users to setup appointments click on the "Open Schedule" button.
      <br/><input type="SUBMIT" name="open" value="Open Schedule"/>
   </form>
<?php
   }
   else{ //Particular day's schedules has already been opened
?>
      <form name="update_appointment" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" method="post">
      <div class="as_section_heading" class="as_section">
         <h2><?php echo $_POST['schedule_list']." (".$_POST['chosen_date'].")"; ?></h2>
      </div>
	 
<?php
      $time_row = $wpdb->get_row("SELECT START_TIME, END_TIME, TIME_INTERVAL FROM $wpdb->as_schedules WHERE NAME='".$_POST['schedule_list']."'");
	  $time     = strtotime($time_row->START_TIME);
      $interval = $time_row->TIME_INTERVAL;
      while($time <= strtotime($time_row->END_TIME)){
         $time_arr = getdate($time);
         $hr  = $time_arr['hours']<10?"0".$time_arr['hours']:$time_arr['hours'];
         $min = $time_arr['minutes']<10?"0".$time_arr['minutes']:$time_arr['minutes'];         
?>
            <div class="as_section">
            <div class="appointment_time">
               <label><?php echo $hr.":".$min; ?></label>
            </div>
<?php
            $curr_appointments = $wpdb->get_row("SELECT a.ID as APPOINTMENT_ID, APPOINTMENT_NAME FROM $wpdb->as_appointments a INNER JOIN $wpdb->as_schedules s ON a.SCHEDULE_ID=s.ID WHERE s.NAME='".$_POST['schedule_list']."' AND a.APPOINTMENT_TIME='".$hr.":".$min.":00' and a.APPOINTMENT_DATE='".$_POST['chosen_date']."'");
            $current = "NONE";
            if($current_id = $curr_appointments->APPOINTMENT_ID)
               $current = $curr_appointments->APPOINTMENT_NAME;
?>
            <div class="appointment_name">
               <input type="text" class="text" name="<?php echo "appt_".$hr.":".$min; ?>" value="<?php if($current!="NONE") echo $current; ?>" onkeyup="javascript:checkForNewText(this, '<?php echo "modified_".$hr.":".$min;?>')"/>
            </div>
            <div class="action" id="<?php echo "action_".$hr.":".$min; ?>">
               <input type="checkbox" id="<?php echo "modified_".$hr.":".$min; ?>" name="modified_appts[]"  value="<?php echo $hr.":".$min; ?>" />
            </div>		
            </div>
<?php
         $time += intval($interval)*60;
      }//end foreach($hrs as $hr)
?>
      <div class="as_section">
      <div class="action">
         <input type="hidden" name="schedule_list" value="<?php  echo $_POST['schedule_list']; ?>" />
         <input type="hidden" name="chosen_date" value="<?php echo $_POST['chosen_date']; ?>" />
         <input type="submit" name="save" value="Save" /><input type="submit" name="close" value="Close Schedule" />
      </div>
      </div>
      </form>
<?php
	} //end else
} //end if(!empty($_POST['schedule_list']) && trim($_POST['schedule_list'])!="")
?>
