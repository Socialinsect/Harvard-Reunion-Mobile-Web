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
  private $facebook = null;
  private $twitter = null;
  
  const ID_SEPARATOR = ':';

  static private function getScheduleConfigFile() {
    static $configFile = null;
    
    if (!$configFile) {
      $configFile = ConfigFile::factory('schedule', 'site');
    }

    return $configFile;
  }

  static private function getScheduleConfigs() {
    $configFile = self::getScheduleConfigFile();
    
    return $configFile ? $configFile->getSectionVars() : array();
  }
  
  static private function getScheduleConfig($scheduleId) {
    $configFile = self::getScheduleConfigFile();
    if ($configFile) {
      $config = $configFile->getSection($scheduleId);
      if ($config) {
        return $config;
      }
    }
    
    return array();
  }

  function __construct($user) {
    $this->timezone = new DateTimeZone($GLOBALS['siteConfig']->getVar('LOCAL_TIMEZONE'));
  
    
    $this->attendee = $user;
    $this->scheduleId = self::getScheduleIdFromYearAndCollegeIndex(
      $this->attendee->getGraduationClass(), $this->attendee->getCollegeIndex());

    $scheduleConfig = self::getScheduleConfig($this->scheduleId);
    
    if ($scheduleConfig) {
      $this->scheduleConfig = $scheduleConfig;

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

  //
  // Config settings
  //
  
  static private function getYearAndCollegeIndexFromId($scheduleId) {
    return explode(self::ID_SEPARATOR, $scheduleId);
  }
  
  static private function getScheduleIdFromYearAndCollegeIndex($year, $collegeIndex=0) {
    return $year.self::ID_SEPARATOR.$collegeIndex;
  }
  
  
  static public function getAllReunionYears() {
    $scheduleConfigs = self::getScheduleConfigs();
    
    $reunionYears = array();
    foreach ($scheduleConfigs as $scheduleId => $config) {
      list($year, $collegeIndex) = self::getYearAndCollegeIndexFromId($scheduleId);
      
      if (isset($reunionYears[$year])) {
        $reunionYears[$year]['separate'] = true;
      } else {
        $reunionYears[$year] = array(
          'year'     => $year,
          'number'   => $config['REUNION_NUMBER'],
          'separate' => false,
        );
      }
    }
    
    return array_values($reunionYears);
  }
  
  static public function reunionClassesAreSeparate($year) {
    $scheduleConfigs = self::getScheduleConfigs();
    
    return isset($scheduleConfigs[$year.self::ID_SEPARATOR.'1']);
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
  
  public function getReunionTitle() {
    return $this->getConfigValue('REUNION_TITLE', '');
  }
  
  public function getFacebookGroupName() {
    return $this->getConfigValue('FACEBOOK_GROUP_NAME', '');
  }
  
  private function getFacebookGroupId() {
    return $this->getConfigValue('FACEBOOK_GROUP_ID', '');
  }
  private function getFacebookGroupIsOldGroup() {
    return $this->getConfigValue('FACEBOOK_GROUP_OLD', 0);
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
    if (isset($this->startDate, $this->endDate)) {
      $startMonth = $this->startDate->format('M');
      $startDay   = $this->startDate->format('j');
      $endMonth   = $this->endDate->format('M');
      $endDay     = $this->endDate->format('j');
      
      if ($startMonth == $endMonth) {
        return "$startMonth $startDay-$endDay";
      } else {
        return "$startMonth $startDay-$endMonth $endDay";
      }
    } else {
      return '';
    }
  }
  
  //
  // Feeds
  //
  
  public function getEventFeed() {
    $controllerClass = $this->getConfigValue('CONTROLLER_CLASS', 'CalendarDataController');
    
    $controller = CalendarDataController::factory($controllerClass, $this->scheduleConfig);
    $controller->setDebugMode($GLOBALS['siteConfig']->getVar('DATA_DEBUG'));

    $endDate = new DateTime($this->endDate->format('Y-m-d').' 00:00:00 +1 day', $this->timezone);
    $controller->setStartDate($this->startDate);
    $controller->setEndDate($endDate);
    
    return $controller;
  }

  public function getFacebookFeed() {
    if (!$this->facebook) {
      $this->facebook = new FacebookGroup($this->getFacebookGroupId(), $this->getFacebookGroupIsOldGroup());
    }
    
    return $this->facebook;
  }
  
  public function getTwitterFeed() {
    if (!$this->twitter) {
      $this->twitter = new TwitterHashtag($this->getTwitterHashTag());
    }
    
    return $this->twitter;
  }
}
