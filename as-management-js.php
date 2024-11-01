<script type="text/javascript">
   
</script>

<?php
// display input fields for a date (month, day, year)
function inputdate($chosenDate) {
   $Month_to_Text = array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
   if (!isset($GLOBALS['popupCalendarJavascriptIsLoaded'])) {
      $calendarLanguageFile = WP_PLUGIN_URL."/wp-appointments-schedules/js/lang/calendar-en.js";
?>
      <link rel="stylesheet" type="text/css" media="all" href="<?php echo WP_PLUGIN_URL."/wp-appointments-schedules/js/calendar-win2k-cold-1.css"; ?>" title="win2k-cold-1" />
      <script type="text/javascript" src="<?php echo WP_PLUGIN_URL."/wp-appointments-schedules/js/calendar.js" ?>"></script>
      <script type="text/javascript" src="<?php echo $calendarLanguageFile; ?>"></script>
      <script type="text/javascript" src="<?php echo WP_PLUGIN_URL."/wp-appointments-schedules/js/calendar-setup.js"; ?>"></script>
<?php
      $GLOBALS['popupCalendarJavascriptIsLoaded'] = TRUE;
   }
?>

<script type="text/javascript"><!--
function onSelectDate(cal) {
   var p = cal.params;
   if (cal.dateClicked) {
      cal.callCloseHandler();
      var new_date = document.getElementById("<?php echo $chosenDate; ?>");
      new_date.value = cal.date.print("%Y-%m-%e");
      document.getElementById("popupCalendarDate_event").value = cal.date.print("%Y-%m-%e");
   }
};

Calendar.setup({
        inputField     :    "popupCalendarDate_event",       // id of the input field
        ifFormat       :    "%m/%e/%Y",                      // format of the input field
        button         :    "showPopupCalendarImage_event",  // trigger for the calendar (button ID)
        align          :    "br",                            // alignment (defaults to "Bl")
        weekNumbers    :    false,
        firstDay       :    0,
        onSelect       :    onSelectDate
});
--></script>
<?php
} // end: function inputdate
?>

<script type="text/javascript">
function toggleDisplay(elemName){
	document.getElementById(elemName).style.display = (document.getElementById(elemName).style.display != 'none' ? 'none':'');
}
function checkForNewText($textbox, $elemName){	
   if($textbox.value == $textbox.defaultValue)
      document.getElementById($elemName).checked = false;
   else
      document.getElementById($elemName).checked = true;	
}
function clearText($textbox){
      if($textbox.value == $textbox.defaultValue)
         $textbox.value = "";
}
</script>

