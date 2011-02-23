<?php

class Reunion {
  private $timezone = null;
  private $start = null;
  private $end = null;
  private $eventConfigs = array();
  private $attendee = null;

  function __construct() {
    $this->timezone = new DateTimeZone(
      $GLOBALS['siteConfig']->getVar('LOCAL_TIMEZONE', Config::LOG_ERRORS | Config::EXPAND_VALUE));
  
    $configFile = realpath_exists(SITE_CONFIG_DIR.'/feeds/schedule.ini');
    if ($configFile) {
      $this->eventConfigs = parse_ini_file($configFile, true);
    }
    
    $this->attendee = new Attendee();
  }
  
  private function getConfigValue($key, $default=null) {
    $event = $this->attendee->getGraduationClass();
    if (isset($this->eventConfigs[$event], $this->eventConfigs[$event][$key])) {
      return $this->eventConfigs[$event][$key];
    }
    
    return $default;
  }
  
  public function getAttendee() {
    return $this->attendee;
  }
  
  public function getNumber() {
    return $this->getConfigValue('REUNION_NUMBER', '0');
  }
  
  public function getFacebookGroup() {
    return $this->getConfigValue('FACEBOOK_GROUP', '0');
  }
  
  public function getTwitterHashTag() {
    return $this->getConfigValue('TWITTER_HASHTAG', '0');
  }
  
  private function getDateTimeForDate($date) {
    return new DateTime($date.' 00:00:00', $this->timezone);
  }
  public function getStartDate() {
    if (!$this->start) {
      $this->start = $this->getDateTimeForDate($this->getConfigValue('START_DATE', ''));
    }
    return $this->start;
  }
  public function getEndDate() {
    if (!$this->end) {
      $this->end = $this->getDateTimeForDate($this->getConfigValue('END_DATE', ''));
    }
    return $this->end;
  }
  
  public function getDateDescription() {
    $start = $this->getStartDate();
    $end = $this->getEndDate();
    
    $startMonth = $start->format('M');
    $startDay   = $start->format('j');
    $endMonth   = $end->format('M');
    $endDay     = $end->format('j');
    
    if ($startMonth == $endMonth) {
      return "$startMonth $startDay-$endDay";
    } else {
      return "$startMonth $startDay-$endMonth $endDay";
    }
  }
}

class Attendee {  
  public function getGraduationClass() {
    return 2001;
  }
  
  public function getFullName() {
    return 'John Smith';
  }
}
