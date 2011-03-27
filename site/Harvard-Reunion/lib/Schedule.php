<?php

/**
  */
includePackage('Calendar');

class Schedule {
  private $user = null;
  private $year = 'error';
  private $collegeIndex = 0;
  private $scheduleId = '';
  private $scheduleConfig = array();
  private $startDate = null;
  private $endDate = null;
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
    $config = array();

    $configFile = self::getScheduleConfigFile();
    if ($configFile) {
      
      $defaultConfig = $configFile->getSection('default');
      if ($defaultConfig) {
        $config = array_merge($config, $defaultConfig);
      }
    
      $scheduleConfig = $configFile->getSection($scheduleId);
      if ($scheduleConfig) {
        $config = array_merge($config, $scheduleConfig);
      }
    }
    
    return $config;
  }

  // Can take either a user object or a year and college index
  function __construct(/* polymorphic */) {
    $args = func_get_args();
    if (is_object($args[0])) {
      $this->user = $args[0];
      $this->year = $this->user->getGraduationClass();
      $this->collegeIndex = $this->user->getCollegeIndex();
    } else {
      $this->year = $args[0];
      $this->collegeIndex = intval($args[1]);
    }
    
    $this->timezone = new DateTimeZone(Kurogo::getSiteVar('LOCAL_TIMEZONE'));
    
    $this->scheduleId = self::getScheduleIdFromYearAndCollegeIndex($this->year, $this->collegeIndex);

    $scheduleConfig = self::getScheduleConfig($this->scheduleId);
    
    if ($scheduleConfig) {
      $this->scheduleConfig = $scheduleConfig;

      $this->startDate = $this->getDateTimeForDate($this->getConfigValue('START_DATE', ''));
      $this->endDate   = $this->getDateTimeForDate($this->getConfigValue('END_DATE', ''));

      $dbFile = $this->getConfigValue("ATTENDANCE_DB");
      $this->attendanceDb = new db(array('DB_TYPE'=>'sqlite', 'DB_FILE'=>$dbFile));
    }
  }
  
  private function getDateTimeForDate($date) {
    return new DateTime($date.' 00:00:00', $this->timezone);
  }
  
  private static function argVal($array, $key, $default=null) {
    return isset($array[$key]) ? $array[$key] : $default;
  }
  
  private function getConfigValue($key, $default=null) {
    return self::argVal($this->scheduleConfig, $key, $default);
  }

  //
  // Config settings
  //
  
  static private function getYearAndCollegeIndexFromId($scheduleId) {
    return explode(self::ID_SEPARATOR, $scheduleId);
  }
  
  static public function getScheduleIdFromYearAndCollegeIndex($year, $collegeIndex=0) {
    return $year.self::ID_SEPARATOR.$collegeIndex;
  }
  
  
  static public function getAllReunionYears() {
    $scheduleConfigs = self::getScheduleConfigs();
    
    $reunionYears = array();
    foreach ($scheduleConfigs as $scheduleId => $config) {
      if ($scheduleId == 'default') { continue; }
      
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
  
  public function getInfo() {
    $info = array(
      'paragraphs' => $this->getConfigValue('ABOUT_TEXT', array()),
      'sections'   => array(),
    );
    
    $sectionKeys   = $this->getConfigValue('SECTION_KEYS',   array());
    $sectionTitles = $this->getConfigValue('SECTION_TITLES', array());
    foreach ($sectionKeys as $i => $key) {
      $title =  isset($sectionTitles[$i]) ? $sectionTitles[$i] : '';
      if ($key && $title) {
        $info['sections'][$key] = array(
          'title' => $title,
          'links' => array(),
        );
      }
    }
    
    $linkArrays = array(
      'title'    => $this->getConfigValue('LINKS_TITLES',    array()),
      'subtitle' => $this->getConfigValue('LINKS_SUBTITLES', array()),
      'url'      => $this->getConfigValue('LINKS_URLS',      array()),
      'class'    => $this->getConfigValue('LINKS_CLASSES',   array()),
    );
    $sections = $this->getConfigValue('LINKS_SECTION', array());
    
    foreach ($linkArrays['title'] as $index => $title) {
      $link = array();
      foreach (array_keys($linkArrays) as $key) {
        if (isset($linkArrays[$key][$index]) && $linkArrays[$key][$index]) {
          $value = $linkArrays[$key][$index];
          
          $test = $this->getConfigValue($value, false);
          if ($test) {
            $link[$key] = $test;
          } else {
            $link[$key] = $value;
          }
        }
      }
      
      if ($link && isset($sections[$index], $info['sections'][$sections[$index]])) {
        $info['sections'][$sections[$index]]['links'][] = $link;
      }
    }
    $info['sections'] = array_values($info['sections']);
    
    return $info;
  }
  
  public function getEventFeed() {
    $controllerClass = $this->getConfigValue('CONTROLLER_CLASS', 'CalendarDataController');
    
    $controller = CalendarDataController::factory($controllerClass, $this->scheduleConfig);
    $controller->setDebugMode(Kurogo::getSiteVar('DATA_DEBUG'));

    $endDate = new DateTime($this->endDate->format('Y-m-d').' 00:00:00 +1 day', $this->timezone);
    $controller->setStartDate($this->startDate);
    $controller->setEndDate($endDate);
    
    return $controller;
  }

  // Takes a User object and returns an array of Harris or Harvard event IDs.
  public function getRegisteredEvents() {
    // If there's no user, there are no registered events.
    if (!$this->isAuthenticatedUser()) {
      return array();
    }
    
    if (isset($this->registeredEvents)) {
      return $this->registeredEvents;
    }
    
    $sql = "SELECT users_events.event_id FROM users, users_events WHERE " .
           "users.user_id=users_events.user_id AND users.email=?";
    $result = $this->attendanceDb->query($sql, array($this->user->getEmail()));

    $eventIds = array();
    foreach($result->fetchAll() as $eventInfo) {
      $eventIds[] = $eventInfo['event_id'];
    }
    $this->registeredEvents = $eventIds;

    return $this->registeredEvents;
  }
  
  private function isAuthenticatedUser() {
    return (is_object($this->user) && get_class($this->user) == 'HarrisReunionUser');
  }
  
  public function isRegisteredForEvent($event) {
    if ($this->isAuthenticatedUser()) {
      $harrisEventID = $event->get_attribute("Event ID");
      
      return in_array($harrisEventID, $this->getRegisteredEvents());
    }
    
    return false;
  }
  
  public function othersRegisteredForEvent($event) {
    if (!$this->isAuthenticatedUser()) {
      return array();
    }
    
    $sql = "SELECT u.prefix, u.first_name, u.last_name, u.suffix, u.class_year ".
           "FROM users u, users_events ue WHERE u.user_id=ue.user_id AND " .
           "ue.event_id=? AND u.email<>? order by u.first_name, u.last_name";
    $harrisEventID = $event->get_attribute("Event ID");
    $email = $this->user->getEmail();
    $result = $this->attendanceDb->query($sql, array($harrisEventID, $email));
    
    return $result->fetchAll();
  }
  
  public function allAttendees() {
    if (!$this->isAuthenticatedUser()) {
      return array();
    }
    
    $sql = "SELECT u.prefix, u.first_name, u.last_name, u.suffix, u.class_year ".
           "FROM users u order by u.first_name, u.last_name";
    $result = $this->attendanceDb->query($sql);
    
    return $result->fetchAll();
  }  
  
  public function getEventInfo($event) {
    $info = array();
    
    //
    // Simple fields
    //
    $simpleFields = array(
      'title'       => 'summary',
      'datetime'    => 'datetime',
      'categories'  => 'categories',
      'description' => 'Details',
      'url'         => 'url',
      'phone'       => 'Phone',
      'email'       => 'Email',
    );
    foreach ($simpleFields as $key => $attribute) {
      $value = $event->get_attribute($attribute);
      if ($value) {
        $info[$key] = $value;
      } else {
        $info[$key] = null;
      }
    }
    
    //
    // Location
    //
    $location = array();
    $locationTitle = $event->get_attribute('Location Name');
    if ($locationTitle) {
      $location['title'] = $locationTitle;
    }
    $locationBuildingID = $event->get_attribute('Building ID');
    if ($locationBuildingID) {
      $location['building'] = $locationBuildingID;
      
      $mapModule = WebModule::factory('map');
      
      $address = array();
      $buildingInfo = $mapModule->getBuildingDataById($locationBuildingID);
      if (isset($buildingInfo['attributes'], $buildingInfo['attributes']['Address'])) {
        $address['street'] = mb_convert_case($buildingInfo['attributes']['Address'], MB_CASE_TITLE);
        
        if (isset($buildingInfo['attributes']['City'])) {
          $address['city'] = mb_convert_case($buildingInfo['attributes']['City'], MB_CASE_TITLE);
        }
        if (isset($buildingInfo['attributes']['State'])) {
          $address['state'] = $buildingInfo['attributes']['State'];
        }
      }
      if ($address) {
        $location['address'] = $address;
      }
      //error_log(print_r($buildingInfo, true));
    }
    $trumbaLocation = $event->get_attribute('location');
    if ($trumbaLocation) {
      if (preg_match('/^([\-\.0-9]+),([\-\.0-9]+)$/', $trumbaLocation, $matches)) {
        $location['latlon'] = array($matches[1], $matches[2]);
      }
    }
    $multipleLocations = $event->get_attribute('Multiple Locations');
    if (strtolower($multipleLocations) == 'yes') {
      $location['multiple'] = true;
    }
    if ($location) {
      $info['location'] = $location;
    } else {
      $info['location'] = null;
    }
    
    //
    // Registration
    //
    $registrationRequired = $event->get_attribute('Registration Required');
    if (strtolower($registrationRequired) == 'yes') {
      $info['registration'] = array(
        'url' => 'http://alumni.harvard.edu/',
      );
      $fee = $event->get_attribute('Registration Fee');
      if ($fee) {
        $info['registration']['fee'] = $fee;
      }
      $url = $event->get_attribute('Registration URL');
      if ($url) {
        $info['registration']['url'] = $url;
      }
    } else {
      $info['registration'] = null;
    }
    
    return $info;
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
