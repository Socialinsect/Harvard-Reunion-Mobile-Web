<?php
/**
  * @package Module
  * @subpackage About
  */

class SiteAboutAPIModule extends APIModule {
  protected $id = 'about';    

  public function initializeForCommand() {
    switch ($this->command) {
      case 'info':
        $year = $this->getArg('year');
        $collegeIndex = $this->getArg('collegeIndex', 0);
        
        $schedule = new Schedule($year, $collegeIndex);

        $this->setResponse($schedule->getInfo());
        $this->setResponseVersion(1);
        break;
    }        
  }
}