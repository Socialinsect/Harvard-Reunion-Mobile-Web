<?php

class Reunion {
  $eventConfigs = array();

  function __construct() {
    $configFile = realpath_exists(SITE_CONFIG_DIR.'/feeds/schedule.ini');
    if ($configFile) {
      $this->eventConfigs = parse_ini_file($configFile, true);
    }
    
  }
  
  public function getAllFeeds() {
    return $this->eventConfigs;
  }
  
  public function getFeed($event) {
    return isset($this->eventConfigs[$event]) ? $this->eventConfigs[$event] : false;
  }
}
