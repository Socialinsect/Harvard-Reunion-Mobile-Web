<?php

/****************************************************************
 *
 *  Copyright 2011 The President and Fellows of Harvard College
 *  Copyright 2011 Modo Labs Inc.
 *
 *****************************************************************/

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
  private $eventController = null;
  private $allEvents = null;
  private $facebook = null;
  private $twitter = null;
  private $foursquare = null;
  private $eventCategoryConfig = null;
  private $geocodeCache = null;
  
  const ID_SEPARATOR = ':';

  static private function getScheduleConfigFile() {
    static $configFile = null;
    
    if (!$configFile) {
      $configFile = ConfigFile::factory('schedule', 'site');
    }

    return $configFile;
  }

  static public function getScheduleConfigs() {
    $configFile = self::getScheduleConfigFile();
    
    return $configFile ? $configFile->getSectionVars() : array();
  }
  
  static private function getScheduleConfig($scheduleId) {
    $config = array();

    $configFile = self::getScheduleConfigFile();
    if ($configFile) {
      
      $defaultConfig = $configFile->getOptionalSection('default');
      if (!$defaultConfig) {
        $defaultConfig = array();
      }
    
      $scheduleConfig = $configFile->getOptionalSection($scheduleId);
      if ($scheduleConfig) {
        $config = array_merge($defaultConfig, $scheduleConfig);
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
      try { 
        $this->attendanceDb = new db(array('DB_TYPE'=>'sqlite', 'DB_FILE'=>$dbFile));
      }
      catch (Exception $e) {
        error_log("Could not open database file for ".$this->year." : ".$e->getMessage());
        $this->attendanceDb = null;
      }
      
      // Google Analytics Event category (accessed with $smarty.const.GA_EVENT_CATEGORY)
      // Should already have single quotes escaped (if any)
      define('GA_EVENT_CATEGORY', $this->getReunionNumber().'th Reunion');
    }
  }
  
  public static function userHasReunion($user) {
    $scheduleId = self::getScheduleIdFromYearAndCollegeIndex(
      $user->getGraduationClass(), $user->getCollegeIndex());
      
    $scheduleConfig = self::getScheduleConfig($scheduleId);
    
    return !empty($scheduleConfig);
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
  
  
  public function getReunionConfig() {
    return array(
      'title'              => $this->getReunionTitle(),
      'number'             => $this->getReunionNumber(),
      'year'               => $this->year,
      'startDate'          => $this->getConfigValue('START_DATE', ''),
      'endDate'            => $this->getConfigValue('END_DATE', ''),
      'classWebsite'       => $this->getConfigValue('CLASS_WEBSITE_URL', ''),
      'calendarURL'        => $this->getConfigValue('BASE_URL', ''),
      'facebookGroupName'  => $this->getFacebookGroupName(),
      'facebookGroupId'    => $this->getFacebookGroupId(),
      'facebookGroupIsOld' => $this->getFacebookGroupIsOldGroup(),
      'twitterHashtag'     => $this->getTwitterHashTag(),
    );
  }
  
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
      'title'      => $this->getConfigValue('LINKS_TITLES',       array()),
      'subtitle'   => $this->getConfigValue('LINKS_SUBTITLES',    array()),
      'url'        => $this->getConfigValue('LINKS_URLS',         array()),
      'linkTarget' => $this->getConfigValue('LINKS_LINK_TARGETS', array()),
      'class'      => $this->getConfigValue('LINKS_CLASSES',      array()),
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

  private function getEventFeed() {
    if (!$this->eventController) {
      $controllerClass = $this->getConfigValue('CONTROLLER_CLASS', 'CalendarDataController');
      
      $this->eventController = CalendarDataController::factory($controllerClass, $this->scheduleConfig);
      $this->eventController->setDebugMode(Kurogo::getSiteVar('DATA_DEBUG'));
      
    } else {
      // reset any filters which might have been applied to the controller
      $this->eventController->removeAllFilters();
    }
    
    // set time range to full schedule duration padded by 1 day beforehand and 3 days after
    // Yes, there are events both before an after the official reunion dates.  Sigh.
    $startDate = new DateTime($this->startDate->format('Y-m-d').' 00:00:00 -1 day', $this->timezone);
    $endDate   = new DateTime(  $this->endDate->format('Y-m-d').' 00:00:00 +3 day', $this->timezone);
    $this->eventController->setStartDate($startDate);
    $this->eventController->setEndDate($endDate);
    
    
    return $this->eventController;
  }
  
  private function getAllEvents() {
    if (!$this->allEvents) {
      $feed = $this->getEventFeed();
      $this->allEvents = $feed->items(0);
    }
    return $this->allEvents;
  }
  
  public function getEvents($category='all') {
    $events = $this->getAllEvents();
    
    foreach($events as $i => $event) {
      if (!$this->eventMatchesCategory($event, $category)) {
        unset($events[$i]);
      }
    }
    
    return $events;
  }
  
  public function searchEvents($query) {
    $feed = $this->getEventFeed();
    $feed->addFilter('search', $query);
    $events = $feed->items(0);
    $feed->removeFilter('search');
    
    return $events;
  }
  
  public function getDefaultCategory() {
    $categories = $this->getEventCategories();
    if (count($categories) == 1 && isset($categories['all'])) {
      return 'all';
    }
    return 'reunion';
  }
  
  public function getAllEventsCategory() {
    return 'all';
  }
  
  private function getEventCategoryConfig() {
    if (!$this->eventCategoryConfig) {
      $this->eventCategoryConfig = array(
        'reunion' => array(
          'title' => $this->getReunionNumber().'th Reunion Events',
          'match' => false,
          'anon'  => true,
        ),
        'other' => array(
          'title' => 'Other Events',
          'match' => 'Other',
          'anon'  => true,
        ),
        'rainbow' => array(
          'title' => 'Rainbow Group: ages 18mos-3yrs',
          'match' => 'Rainbow Group',
          'anon'  => false,
        ),
        'canary' => array(
          'title' => 'Canary Group: ages 4-5yrs',
          'match' => 'Canary Group',
          'anon'  => false,
        ),
        'grape' => array(
          'title' => 'Grape Group: ages 6-8 yrs',
          'match' => 'Grape Group',
          'anon'  => false,
        ),
        'red' => array(
          'title' => 'Red Group: ages 9-10 yrs',
          'match' => 'Red Group',
          'anon'  => false,
        ),
        'blue' => array(
          'title' => 'Blue Group: ages 11-12 yrs',
          'match' => 'Blue Group',
          'anon'  => false,
        ),
        'green' => array(
          'title' => 'Green Group: ages 13-14',
          'match' => 'Green Group',
          'anon'  => false,
        ),
        'youngadult' => array(
          'title' => 'Young Adult Group: ages 15+ yrs',
          'match' => 'Young Adult Group',
          'anon'  => false,
        ),
        'night' => array(
          'title' => 'Night Program',
          'match' => 'Night Program',
          'anon'  => false,
        ),
      );
    }
    
    return $this->eventCategoryConfig;
  }
    
  private function getAllEventCategories() {
    $categoryConfig = $this->getEventCategoryConfig();
    
    $categories = array();
    foreach ($categoryConfig as $key => $config) {
      if ($this->isAuthenticatedUser() || $config['anon']) {
        $categories[$key] = $config['title'];
      }
    }
    return $categories;
  }
  
  private function eventMatchesCategory($event, $category) {
    if ($category == 'all') {
      return true; // All events, skip checks
    }
  
    $categoryConfig = $this->getEventCategoryConfig();

    $trumbaCategories = $event->get_attribute('categories');
    
    $eventCategory = 'reunion'; // default if no match
    foreach ($trumbaCategories as $trumbaCategory) {
      if ($trumbaCategory == 'Official') { continue; } // skip trumba default
    
      $foundMatch = false;
      foreach ($categoryConfig as $key => $config) {
        if ($config['match'] && stripos($trumbaCategory, $config['match']) !== false) {
          $eventCategory = $key;
          $foundMatch = true;
          break;
        }
      }
      if ($foundMatch) { break; }
    }
    
    return $category == $eventCategory;
  }
  
  public function getEventCategories() {
    $categories = $skipCategories = $this->getAllEventCategories();
    $events = $this->getAllEvents();
    
    // Figure out which categories have no events
    foreach ($events as $event) {
      foreach ($skipCategories as $category => $name) {
        if ($this->eventMatchesCategory($event, $category)) {
          unset($skipCategories[$category]);
        }
      }
      if (empty($skipCategories)) { break; } // all categories valid
    }
    
    // Remove empty categories
    foreach ($categories as $category => $name) {
      if (isset($skipCategories[$category])) {
        unset($categories[$category]);
      }
    }
    
    if (count($categories) > 1) {
      // Show all events if there is more than one category and
      // fewer than 100 total events (so we don't overwhelm devices)
      $eventCountThreshhold = 100;
      switch ($GLOBALS['deviceClassifier']->getPagetype()) {
        case 'compliant':
          $eventCountThreshhold = 100;
          break;
        case 'touch':
          $eventCountThreshhold = 50;
          break;
        case 'basic':
          $eventCountThreshhold = 25;
          break;
      }

      if (count($events) < $eventCountThreshhold) {
        $categories['all'] = 'All Events';
      }
    } else if (!count($categories)) {
      $categories['all'] = 'All Events';
    }
    
    return $categories;
  }
  
  // Takes a User object and returns an array of Harris or Harvard event IDs.
  public function getRegisteredEvents() {
    // If there's no user, there are no registered events.
    if (!$this->isAuthenticatedUser() || !$this->attendanceDb) {
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
  
  public function getAttendeesRegisteredForEvent($event) {
    if (!$this->isAuthenticatedUser() || !$this->attendanceDb) {
      return array();
    }
    
    $sql = "SELECT u.prefix, u.first_name, u.last_name, u.suffix, u.class_year ".
           "FROM users u, users_events ue WHERE u.user_id=ue.user_id AND " .
           "ue.event_id=? order by u.last_name, u.first_name";
    $harrisEventID = $event->get_attribute("Event ID");
    $result = $this->attendanceDb->query($sql, array($harrisEventID));
    
    $attendees = $result->fetchAll();
    
    foreach ($attendees as $i => $attendee) {
      $attendees[$i]['display_name'] = $this->formatAttendeeName($attendee);
    }
    
    return $attendees;
  }

  public function getAttendeeCountForEvent($event) {
    // Yes, it's not efficient. We can fix it if it becomes a problem.
    return count($this->getAttendeesRegisteredForEvent($event));
  }
  
  public function getAllAttendees() {
    if (!$this->isAuthenticatedUser() || !$this->attendanceDb) {
      return array();
    }
    
    $sql = "SELECT u.prefix, u.first_name, u.last_name, u.suffix, u.class_year ".
           "FROM users u order by u.last_name, u.first_name";
    $result = $this->attendanceDb->query($sql);
    
    $attendees = $result->fetchAll();
    
    foreach ($attendees as $i => $attendee) {
      $attendees[$i]['display_name'] = $this->formatAttendeeName($attendee);
    }
    
    return $attendees;
  }
  
  private function formatAttendeeName($attendee) {
    $parts = array();
    if (isset($attendee['last_name'])) {
      $parts[] = $attendee['last_name'];
    }
    if (isset($attendee['first_name'])) {
      $parts[] = $attendee['first_name'];
    }
    
    $parts = array(implode(', ', $parts));
    if (isset($attendee['suffix'])) {
      $parts[] = $attendee['suffix'];
    }
    
    return implode(' ', $parts);
  }
  
  public function getAttendeeFirstLetterGroups($attendees) {
    $countThreshhold = 100;
    switch ($GLOBALS['deviceClassifier']->getPagetype()) {
      case 'compliant':
        $countThreshhold = 100;
        $maxGroupSize = 75;
        break;
      case 'touch':
        $countThreshhold = 50;
        $maxGroupSize = 35;
        break;
      case 'basic':
        $countThreshhold = 25;
        $maxGroupSize = 20;
        break;
    }
    
    if (count($attendees) <= $countThreshhold) {
      return false; // attendees will fit on one page
    }
    
    $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    
    $firstLetterGroups = array();
    for ($i = 0; $i < strlen($letters); $i++) {
      $firstLetterGroups[substr($letters, $i, 1)] = array();
    }
    
    foreach ($attendees as $i => $attendee) {
      $firstLetter = strtoupper(substr($attendee['display_name'], 0, 1));
      if (!isset($firstLetterGroups[$firstLetter])) {
        $firstLetterGroups[$firstLetter] = array();
      }
      $firstLetterGroups[$firstLetter][] = $attendee;
    }
    
    $letterGroups = array();
    foreach ($firstLetterGroups as $letter => $a) {
      $i = count($letterGroups)-1;
      
      $currentCount    = $i < 0 ? 0 : count($letterGroups[$i]['entries']);
      $nextLetterCount = $i < 0 ? 0 : count($a);
      $totalCount      = $currentCount + $nextLetterCount;
        
      if ($i < 0 || ($currentCount > 0 && $nextLetterCount > 0 && $totalCount > $maxGroupSize)) {
        $letterGroups[] = array(
          'letters' => array(),
          'entries' => array(),
        );
        $i++;
      }
      
      $letterGroups[$i]['letters'][] = $letter;
      $letterGroups[$i]['entries'] = array_merge($letterGroups[$i]['entries'], $a);
      
    }
    
    $groups = array();
    foreach ($letterGroups as $i => $group) {
      $first = reset($group['letters']);
      $last = end($group['letters']);
      
      if ($first == $last) {
        $groups[$first] = array(
          'title' => $first,
          'count' => count($group['entries']),
        );
      } else {
        $groups["$first-$last"] = array(
          'title' => "$first - $last",
          'count' => count($group['entries']),
        );
      }
    }
    unset($letterGroups);
    
    return $groups;
  }
  
  public function getAttendeesForLetterRange($attendees, $range) {
    $parts = explode('-', $range);
    if (count($parts) && strlen(trim($parts[0]))) {
      $first = trim($parts[0]);
      $last = count($parts) == 1 ? $first : trim($parts[1]);
      
      $results = array();
      foreach ($attendees as $attendee) {
        $title = strtoupper(substr($attendee['display_name'], 0, 1));
        if (strcmp($first, $title) <= 0 && strcmp($title, $last) <= 0) {
          $results[] = $attendee;
        }
      }
      
      return $results;
    }
    
    return array();
  }
  
  public function getEvent($eventId, $start) {
    $feed = $this->getEventFeed();
    return $feed->getItem($eventId, $start);
  }
  
  public function getBriefEventInfo($event) {
    $info = array(
      'id'        => $event->get_uid(),
      'attending' => $this->isRegisteredForEvent($event),
    );
    
    //
    // Simple fields
    //
    $simpleFields = array(
      'title'       => 'summary',
      'datetime'    => 'datetime',
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
    // Registration (used by iPad UI)
    //
    $info['registration'] = null;
    
    $registrationRequired = $event->get_attribute('Registration Required');
    if (strtolower($registrationRequired) == 'yes') {
      $info['registration'] = array(
        'url'        => '',
        'fee'        => '',
        'registered' => $info['attending'],
      );
      $fee = $event->get_attribute('Registration Fee');
      if ($fee) {
        $info['registration']['fee'] = $fee;
      }
      $url = $event->get_attribute('Registration URL');
      if ($url) {
        $info['registration']['url'] = $url;
      }
    }
    
    return $info;
  }
  
  public function getEventInfo($event) {
    $info = $this->getBriefEventInfo($event);
    
    //
    // Additional simple fields
    //
    $simpleFields = array(
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
    // Categories
    //
    $info['category'] = null;
    
    $categories = $this->getEventCategories();
    foreach ($categories as $category => $title) {
      if ($this->eventMatchesCategory($event, $category)) {
        $info['category'] = $category;
        break;
      }
    }
    
    //
    // Location
    //
    $info['location'] = null;
    
    $placeTitle = '';
    $locationTitle = $event->get_attribute('Location Name');
    $locationBuildingID = $event->get_attribute('Building ID');
    $trumbaLocation = $event->get_attribute('location');
    $foursquareId = $event->get_attribute('Foursquare Place');
    if ($locationTitle || $locationBuilding || $trumbaLocation) {
      $location = array(
        'title'        => null,
        'building'     => null,
        'latlon'       => null,
        'address'      => array(
          'street'     => null,
          'city'       => null,
          'state'      => null,
        ),
        'multiple'     => false,
        'foursquareId' => null,
      )
      ;
      if ($locationTitle) {
        $location['title'] = $locationTitle;
        $placeTitle = $locationTitle;
      }
      
      if ($foursquareId) {
        //$location['foursquareId'] = $foursquareId;
      }
      
      $multipleLocations = $event->get_attribute('Multiple Locations');
      if (strtolower($multipleLocations) == 'yes') {
        $location['multiple'] = true;
      
      } else {
        // Only check these fields if there is only one location
        if ($locationBuildingID) {
          $location['building'] = $locationBuildingID;
          
          $mapModule = WebModule::factory('map');
          
          $buildingInfo = $mapModule->getBuildingDataById($locationBuildingID);
          if (isset($buildingInfo['Address'])) {
            $location['address']['street'] = mb_convert_case($buildingInfo['Address'], MB_CASE_TITLE);
            
            if (isset($buildingInfo['City'])) {
              $location['address']['city'] = mb_convert_case($buildingInfo['City'], MB_CASE_TITLE);
            }
            if (isset($buildingInfo['State'])) {
              $location['address']['state'] = $buildingInfo['State'];
            }
          }
          
          if (isset($buildingInfo['Building Name'])) {
            $placeTitle = mb_convert_case($buildingInfo['Building Name'], MB_CASE_TITLE);
          }
          
          if ($buildingInfo['coords']) {
            $location['latlon'] = array_values($buildingInfo['coords']);
          }
          //error_log(print_r($buildingInfo, true));
        }
        
        if ($trumbaLocation) {
          $location['latlon'] = $this->googleGeocode($trumbaLocation);
        }
      }
      
      $info['location'] = $location;
    }
    
    //
    // Attendees
    //
    $info['attendees'] = array();
    if ($event->get_attribute('Event ID')) {
      $info['attendees'] = $this->getAttendeesRegisteredForEvent($event);
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
  
  public function getFoursquareFeed() {
    if (!$this->foursquare) {
      $this->foursquare = new Foursquare();
    }
    
    return $this->foursquare;
  }
  
  protected function googleGeocode($location) {
    $data = null;
  
    if (!$this->geocodeCache) {
      $this->geocodeCache = new DiskCache(CACHE_DIR."/Geocode", PHP_INT_MAX, TRUE);
    }
    
    $cacheName = urlencode($location);
    
    if ($this->geocodeCache->isFresh($cacheName)) {
      $data = $this->geocodeCache->read($cacheName);
      
    } else {
      $url = 'https://maps.googleapis.com/maps/api/geocode/json?'.http_build_query(array(
        'address' => $location,
        'sensor'  => 'false',
      ));
      $contents = file_get_contents($url);
      
      if ($data = json_decode($contents, true)) {
        if ($this->geocodeResultsGetLatLon($data)) {
          $this->geocodeCache->write($data, $cacheName);
        } else {
          error_log("Bad result from '{$url}':\n{$contents}");
          $data = null;
        }
      }
    }
    
    return $this->geocodeResultsGetLatLon($data);
  }
  
  protected function geocodeResultsGetLatLon($data) {
    if (isset($data,
              $data['results'],
              $data['results'][0],
              $data['results'][0]['geometry'],
              $data['results'][0]['geometry']['location'],
              $data['results'][0]['geometry']['location']['lat'],
              $data['results'][0]['geometry']['location']['lng'])) {
      return array(
        $data['results'][0]['geometry']['location']['lat'],
        $data['results'][0]['geometry']['location']['lng'],
      );
    }
    return null;
  }
}
