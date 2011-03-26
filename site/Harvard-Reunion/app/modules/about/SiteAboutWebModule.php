<?php
/**
  * @package Module
  * @subpackage About
  */

class SiteAboutWebModule extends AboutWebModule {

  protected function initializeForPage() {
    $user = $this->getUser('HarvardReunionUser');
    $schedule = new Schedule($user);
    
    switch ($this->page) {
      case 'index':
        $this->assign('info', $schedule->getInfo());
        break;
    }
  }
}
