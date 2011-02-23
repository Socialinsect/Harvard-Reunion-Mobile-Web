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
  static public function loadReunionConfig() {
    $data = array();
    
    $feedConfigFile = realpath_exists(SITE_CONFIG_DIR.'/feeds/reunion.ini');
    if ($feedConfigFile) {
      $data = parse_ini_file($feedConfigFile, true);
    }
    
    return $data;
  }

  protected function initialize() {
    $this->reunion = new Reunion();
  }

  protected function initializeForPage() {
    $user = $this->reunion->getAttendee();    
  
    switch ($this->page) {
      case 'index':
        // TODO: get from backend
        $userInfo = array(
          'fullname' => $user->getFullName(),
          'class' => $user->getGraduationClass(),
        );

        $reunionInfo = array(
          'year'  => $this->reunion->getNumber(),
          'dates' => $this->reunion->getDateDescription(),
        );
        
        $socialInfo = array(
          'facebook' => array(
            'name' => $this->reunion->getFacebookGroup(),
            'url'  => '',
          ),
          'twitter' => array(
            'name' => $this->reunion->getTwitterHashTag(),
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
        
        $this->assign('userInfo',    $userInfo);
        $this->assign('reunionInfo', $reunionInfo);
        $this->assign('socialInfo',  $socialInfo);
        break;
        
     case 'search':
        break;
    }
    
    parent::initializeForPage();
  }
}
