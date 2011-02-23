<?php

/**
  */
require_once realpath(LIB_DIR.'/DateTimeUtils.php');

/**
  */
require_once realpath(LIB_DIR.'/ICalendar.php');

class Reunion {
  private $timezone = null;
  private $startDate = null;
  private $endDate = null;
  private $eventConfig = array();
  private $attendee = null;

  function __construct() {
    $this->timezone = new DateTimeZone(
      $GLOBALS['siteConfig']->getVar('LOCAL_TIMEZONE', Config::LOG_ERRORS | Config::EXPAND_VALUE));
  
    $eventConfig = array();
    $configFile = realpath_exists(SITE_CONFIG_DIR.'/feeds/schedule.ini');
    if ($configFile) {
      $eventConfigs = parse_ini_file($configFile, true);
    }

    $this->attendee = new Attendee();
    $event = $this->attendee->getGraduationClass();

    if (isset($eventConfigs[$event])) {
      $this->eventConfig = $eventConfigs[$event];
      
      $this->startDate = $this->getDateTimeForDate($this->getConfigValue('START_DATE', ''));
      $this->endDate   = $this->getDateTimeForDate($this->getConfigValue('END_DATE', ''));
    }
  }
  
  private function getDateTimeForDate($date) {
    return new DateTime($date.' 00:00:00', $this->timezone);
  }
  
  private function getConfigValue($key, $default=null) {
    return isset($this->eventConfig[$key]) ? $this->eventConfig[$key] : $default;
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
  public function getStartDate() {
    return $this->startDate;
  }
  public function getEndDate() {
    return $this->endDate;
  }
  
  public function getDateDescription() {
    
    $startMonth = $this->startDate->format('M');
    $startDay   = $this->startDate->format('j');
    $endMonth   = $this->endDate->format('M');
    $endDay     = $this->endDate->format('j');
    
    if ($startMonth == $endMonth) {
      return "$startMonth $startDay-$endDay";
    } else {
      return "$startMonth $startDay-$endMonth $endDay";
    }
  }
  
  public function getEventFeed() {
    $controllerClass = $this->getConfigValue('CONTROLLER_CLASS', 'CalendarDataController');
    
    $controller = CalendarDataController::factory($controllerClass, $this->eventConfig);
    $controller->setDebugMode($GLOBALS['siteConfig']->getVar('DATA_DEBUG', Config::LOG_ERRORS | Config::EXPAND_VALUE));

    $endDate = new DateTime($this->endDate->format('Y-m-d').' 00:00:00 +1 day', $this->timezone);
    $controller->setStartDate($this->startDate);
    $controller->setEndDate($endDate);
    
    return $controller;
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
