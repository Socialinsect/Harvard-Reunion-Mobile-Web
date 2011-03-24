<?php
/**
  * @package Module
  * @subpackage About
  */

require_once('SiteAboutAPIModule.php');

class SiteAboutWebModule extends AboutWebModule {

  protected function initializeForPage() {
    $user = $this->getUser('HarvardReunionUser');
    
    switch ($this->page) {
      case 'index':
        $feeds = $this->loadFeedData();
        $year = $user->getGraduationClass();
        $collegeIndex = $user->getCollegeIndex();
        
        $this->assign('info', SiteAboutAPIModule::getInfoForUser($feeds, $year, $collegeIndex));
        break;
    }
  }
}
