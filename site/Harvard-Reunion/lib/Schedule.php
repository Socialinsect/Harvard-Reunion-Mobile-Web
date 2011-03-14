<?php

/**
  */
includePackage('Calendar');

class Schedule {
  private $scheduleId = '';
  private $scheduleConfig = array();
  private $startDate = null;
  private $endDate = null;
  private $attendee = null;
  private $timezone = null;

  function __construct() {
    $this->timezone = new DateTimeZone(
      $GLOBALS['siteConfig']->getVar('LOCAL_TIMEZONE', Config::LOG_ERRORS | Config::EXPAND_VALUE));
  
    $scheduleConfigs = array();
    $configFile = realpath_exists(SITE_CONFIG_DIR.'/schedule/feeds.ini');
    if ($configFile) {
      $scheduleConfigs = parse_ini_file($configFile, true);
    }

    $this->attendee = new Attendee();
    $this->scheduleId = $this->attendee->getGraduationClass();
    
    if (isset($scheduleConfigs[$this->scheduleId])) {
      $this->scheduleConfig = $scheduleConfigs[$this->scheduleId];
      
      $this->startDate = $this->getDateTimeForDate($this->getConfigValue('START_DATE', ''));
      $this->endDate   = $this->getDateTimeForDate($this->getConfigValue('END_DATE', ''));
    }
  }
  
  private function getDateTimeForDate($date) {
    return new DateTime($date.' 00:00:00', $this->timezone);
  }
  
  private function getConfigValue($key, $default=null) {
    return isset($this->scheduleConfig[$key]) ? $this->scheduleConfig[$key] : $default;
  }

  public function getScheduleId() {
    return $this->scheduleId;
  }
  
  public function getAttendee() {
    return $this->attendee;
  }
  
  public function getReunionNumber() {
    return $this->getConfigValue('REUNION_NUMBER', '0');
  }
  
  public function getFacebookGroupName() {
    return $this->getConfigValue('FACEBOOK_GROUP_NAME', '');
  }
  public function getFacebookGroupId() {
    return $this->getConfigValue('FACEBOOK_GROUP_ID', '');
  }
  
  public function getTwitterHashTag() {
    return $this->getConfigValue('TWITTER_HASHTAG', '');
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
    
    $controller = CalendarDataController::factory($controllerClass, $this->scheduleConfig);
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
