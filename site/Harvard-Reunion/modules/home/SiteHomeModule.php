<?php
/**
  * @package Module
  * @subpackage Home
  */

/**
  * @package Module
  * @subpackage Home
  */
class SiteHomeModule extends HomeModule {
  private $schedule = null;

  static public function loadReunionConfig() {
    $data = array();
    
    $feedConfigFile = realpath_exists(SITE_CONFIG_DIR.'/feeds/reunion.ini');
    if ($feedConfigFile) {
      $data = parse_ini_file($feedConfigFile, true);
    }
    
    return $data;
  }

  protected function initialize() {
    $this->schedule = new Schedule();
  }

  protected function initializeForPage() {
    $user = $this->schedule->getAttendee();    
  
    switch ($this->page) {
      case 'index':
        // TODO: get from backend
        $userInfo = array(
          'fullname' => $user->getFullName(),
          'class' => $user->getGraduationClass(),
        );

        $scheduleInfo = array(
          'year'  => $this->schedule->getReunionNumber(),
          'dates' => $this->schedule->getDateDescription(),
        );
        
        $socialInfo = array(
          'facebook' => array(
            'name' => $this->schedule->getFacebookGroup(),
            'url'  => '',
          ),
          'twitter' => array(
            'name' => $this->schedule->getTwitterHashTag(),
            'url'  => '',
          ),
          'recent' => null,
        );
        
        $socialInfo['recent'] = array(
          'type'    => 'twitter',
          'message' => 'big group going 2 John Harvard\'s in the Garage, everyone welcome',
          'author'  => 'Katarina Ragulin',
          'age'     => '12 min',
        );
        
        $this->assign('userInfo',     $userInfo);
        $this->assign('scheduleInfo', $scheduleInfo);
        $this->assign('socialInfo',   $socialInfo);
        break;
        
     case 'search':
        break;
    }
    
    parent::initializeForPage();
  }
}
