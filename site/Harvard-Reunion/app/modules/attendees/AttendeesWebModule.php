<?php

class AttendeesWebModule extends WebModule
{
  protected $id='attendees';
  protected function initializeForPage() {
    $user = $this->getUser('HarvardReunionUser');
    $schedule = new Schedule($user);
    
    $test = array("label" => "John Doe");
    $attendeeNames = array();
    foreach($schedule->getAllAttendees() as $attendee) {
      $name = $attendee['first_name']." ".$attendee['last_name'];
      $attendeeNames[] = array("title" => $name); 
    }

    $this->assign('attendees', $attendeeNames);

  }
}
