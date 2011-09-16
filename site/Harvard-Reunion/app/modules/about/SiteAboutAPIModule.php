<?php

/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

/**
  * @package Module
  * @subpackage About
  */

class SiteAboutAPIModule extends APIModule {
  protected $id = 'about';    

  public function initializeForCommand() {
    $user = $this->getUser('HarvardReunionUser');
    $schedule = new Schedule($user);

    switch ($this->command) {
      case 'info':
        $this->setResponse($schedule->getInfo());
        $this->setResponseVersion(1);
        break;

      default:
        $this->invalidCommand();
        break;
    }
  }
}
