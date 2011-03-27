<?php

class AttendeesWebModule extends WebModule
{
  protected $id='attendees';
  protected function initializeForPage() {
    $user = $this->getUser('HarvardReunionUser');
    $schedule = new Schedule($user);
    $this->assign('attendees', $schedule->getAllAttendees());
  }
}
