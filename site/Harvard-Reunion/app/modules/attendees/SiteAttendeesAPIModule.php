<?php
/**
  * @package Module
  * @subpackage About
  */

class SiteAttendeesAPIModule extends APIModule {
  protected $id = 'attendees';    

  public function initializeForCommand() {
    $user = $this->getUser('HarvardReunionUser');
    $schedule = new Schedule($user);

    switch ($this->command) {
      case 'all':
        $this->setResponse($schedule->getAllAttendees());
        $this->setResponseVersion(1);
        break;

      default:
        $this->invalidCommand();
        break;
    }
  }
}
