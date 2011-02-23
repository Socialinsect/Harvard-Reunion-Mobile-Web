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


  protected function initializeForPage() {
    
  
    switch ($this->page) {
      case 'index':
        // TODO: get from backend
        $attendee = 'John Smith';

        $reunionInfo = array(
          'year'  => '10th',
          'dates' => 'May 27-29',
          'class' => '2001',
        );      
        
        $facebookGroup = array(
          'name' => 'Harvard-Radcliff \'01',
          'url'  => '',
        );
        $twitterTag = array(
          'name' => '#hr10th',
          'url'  => '',
        );
        
        $recentPost = array(
          'message' => 'big group going 2 John Harvard\'s in the Garage, everyone welcome',
          'author'  => 'Katarina Ragulin',
          'delta'   => '12 mins ago',
        );
        
        $this->assign('reunionInfo',   $reunionInfo);
        $this->assign('attendee',      $attendee);
        $this->assign('facebookGroup', $facebookGroup);
        $this->assign('twitterTag',    $twitterTag);
        $this->assign('recentPost',    $recentPost);
        break;
        
     case 'search':
        break;
    }
    
    parent::initializeForPage();
  }
}
