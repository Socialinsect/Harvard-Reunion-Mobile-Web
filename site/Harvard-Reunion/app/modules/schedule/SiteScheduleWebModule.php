<?php
/**
  * @package Module
  * @subpackage Schedule
  */

includePackage('Maps');
  
define('SCHEDULE_BOOKMARKS_COOKIE_PREFIX', 'ScheduleBookmarks_');
define('SCHEDULE_BOOKMARKS_COOKIE_DURATION', 160 * 24 * 60 * 60);

class SiteScheduleWebModule extends WebModule {
  protected $id = 'schedule';
  protected $schedule = null;
  protected $bookmarks = array();

  private function getCookieNameForEvent($event) {
    return SCHEDULE_BOOKMARKS_COOKIE_PREFIX.$event;
  }

  private function getBookmarks($scheduleId) {
    $cookieName = $this->getCookieNameForEvent($scheduleId);
    
    if (!isset($this->bookmarks[$cookieName])) {
      if (isset($_COOKIE[$cookieName])) {
        $this->bookmarks[$cookieName] = array_unique(explode(',', $_COOKIE[$cookieName]));
      } else {
        $this->bookmarks[$cookieName] = array();
      }
    }
    
    return $this->bookmarks[$cookieName];
  }
  
  private function setBookmarks($scheduleId, $bookmarks) {
    $cookieName = $this->getCookieNameForEvent($scheduleId);
    
    setcookie($cookieName, implode(',', array_unique($bookmarks)), 
      time() + SCHEDULE_BOOKMARKS_COOKIE_DURATION, COOKIE_PATH);

    $this->bookmarks[$cookieName] = $bookmarks;
  }

  private function isBookmarked($scheduleId, $eventId) {
    return in_array($eventId, $this->getBookmarks($scheduleId));
  }
  
  private function checkToggleBookmark($scheduleId, $eventId) {
    if ($this->getArg('toggleBookmark')) {
      $bookmarks = array_fill_keys($this->getBookmarks($scheduleId), true);
    
      if (isset($bookmarks[$eventId])) {
        unset($bookmarks[$eventId]);
      } else {
        $bookmarks[$eventId] = true;
      }
      $this->setBookmarks($scheduleId, array_keys($bookmarks));
        
      $args = $this->args;
      unset($args['toggleBookmark']);
      $this->redirectTo($this->page, $args);
    }
  }
  
  private function valueForType($type, $value) {
    $valueForType = $value;
  
    switch ($type) {
      case 'datetime':
        $allDay = $value instanceOf DayRange;
        $sameAMPM = date('a', $value->get_start()) == date('a', $value->get_end());
        $sameDay = false;
        if ($value->get_end() && $value->get_end() != $value->get_start()) {
          $startDate = intval(date('Ymd', $value->get_start()));
          $endDate = intval(date('Ymd', $value->get_end()));
          
          $sameDay = $startDate == $endDate;
          if (!$sameDay) {
            $endIsBefore5am = intval(date('H', $value->get_end())) < 5;
            if ($endIsBefore5am && ($endDate - $startDate == 1)) {
              $sameDay = true;
            }
          }
        }
        $valueForType = date("l, F j", $value->get_start());
        if ($allDay) {
          if (!$sameDay) {
            $valueForType .= date(" - l, F j", $value->get_end());
          }
        } else {
          $valueForType .= ($sameDay ? '<br/>' : ', ').date('g:i', $value->get_start());
          if (!$sameAMPM) {
            $valueForType .= date('a', $value->get_start());
          }
          if (!$sameDay) {
            $valueForType .= date(" - l, F j, ", $value->get_end());
          } else if ($sameAMPM) {
            $valueForType .= '-';
          } else {
            $valueForType .= ' - ';
          }
          $valueForType .= date("g:ia", $value->get_end());
        }
        break;

      case 'url':
        $valueForType = preg_replace(
          array(';http://([^/]+)/$;', ';http://;'), 
          array('\1',                 ''), $value);
        break;
        
      case 'phone':
        // add the local area code if missing
        if (preg_match('/^\d{3}-\d{4}/', $value)) {
          $valueForType = $this->getSiteVar('LOCAL_AREA_CODE').$value;
        }
        $valueForType = str_replace('-', '-&shy;', str_replace('.', '-', $value));
        break;
      
      case 'email':
        $valueForType = str_replace('@', '@&shy;', $value);
        break;
    }
    
    return $valueForType;
  }
  
  private function urlForType($type, $value) {
    $urlForType = null;
  
    switch ($type) {
      case 'url':
        $urlForType = str_replace("http://http://", "http://", $value);
        if (strlen($urlForType) && !preg_match('/^http\:\/\//', $urlForType)) {
          $urlForType = 'http://'.$urlForType;
        }
        break;
        
      case 'phone':
        // add the local area code if missing
        if (preg_match('/^\d{3}-\d{4}/', $value)) {
          $urlForType = $this->getSiteVar('LOCAL_AREA_CODE').$value;
        }
    
        // remove all non-word characters from the number
        $urlForType = 'tel:1'.preg_replace('/\W/', '', $value);
        break;
        
      case 'email':
        $urlForType = "mailto:$value";
        break;
    }
    
    return $urlForType;
  }

  private function timeText($event, $timeOnly=false) {
    if ($timeOnly) {
      $sameAMPM = date('a', $event->get_start()) == date('a', $event->get_end());
    
      $timeString = date(' g:i', $event->get_start());
      if (!$sameAMPM) {
        $timeString .= date('a', $event->get_start());
      }
      $timeString .= ($sameAMPM ? '-' : ' - ').date("g:ia", $event->get_end());
      
      return $timeString;
    } else {
      return strval($event->get_range());
    }
  }
  
  private function detailURL($event, $addBreadcrumb=true, $noBreadcrumbs=false) {
    $args = array(
      'eventId' => $event->get_uid(),
      'start'   => $event->get_start()
    );
  
    if ($noBreadcrumbs) {
      return $this->buildURL('detail', $args);
    } else {
      return $this->buildBreadcrumbURL('detail', $args, $addBreadcrumb);
    }
  }

  private function titleForAttendeeCount($event) {
    $attendeeCount = $this->schedule->getAttendeeCountForEvent($event);
    if ($this->schedule->isRegisteredForEvent($event)) {
      $otherCount = $attendeeCount - 1;
      return "$otherCount other".($otherCount == 1 ? '' : 's').' attending';
    }

    // We're not attending, just these people
    return "$attendeeCount ".($attendeeCount == 1 ? 'person' : 'people').' attending';
  }


  protected function initialize() {
    $user = $this->getUser('HarvardReunionUser');
    $this->schedule = new Schedule($user);
  }
  
  protected function eventMatchesCategory($event, $category) {
    if ($category == 'mine') {
      return 
        $this->isBookmarked($this->schedule->getScheduleId(), $event->get_uid()) ||
        $this->schedule->isRegisteredForEvent($event);
    } else {
      return $this->schedule->eventMatchesCategory($event, $category);
    }
  }

  protected function initializeForPage() {    
    $scheduleId = $this->schedule->getScheduleId();

    switch ($this->page) {
      case 'help':
        break;

      case 'index':
        $category  = $this->getArg('category', $this->schedule->getDefaultCategory());
        
        $feed = $this->schedule->getEventFeed();
        
        $events = $feed->items(0);
        
        $categories = $this->schedule->getEventCategories();
        $categories['mine'] = 'My Schedule';
        
        $eventDays = array();
        foreach($events as $event) {
          $date = date('Y-m-d', $event->get_start());
          
          $showThisEvent = $this->eventMatchesCategory($event, $category);
          
          if ($showThisEvent) {
            if (!isset($eventDays[$date])) {
              $eventDays[$date] = array(
                'title'      => date('l, F j, Y', $event->get_start()),
                'events'     => array(),
              );
            }
            
            $eventInfo = array(
              'url'      => $this->detailURL($event),
              'title'    => $event->get_summary(),
              'subtitle' => $this->timeText($event, true),
            );
            if ($this->isBookmarked($scheduleId, $event->get_uid())) {
              $eventInfo['class'] = 'bookmarked';
            }         
            
            if ($this->schedule->isRegisteredForEvent($event)) {
              $eventInfo['class'] = 'bookmarked';
            }
            
            $eventDays[$date]['events'][] = $eventInfo;
          }
        }
        
        $this->assign('category',   $category);        
        $this->assign('categories', $categories);        
        $this->assign('eventDays',  $eventDays);        
        break;
              
      case 'detail':
        $eventId    = $this->getArg('eventId');
        $start      = $this->getArg('start', time());
        
        $this->checkToggleBookmark($scheduleId, $eventId);
        
        $feed = $this->schedule->getEventFeed();      
        $event = $feed->getItem($eventId, $start);
        if (!$event) {
          throw new Exception("Event not found");
        }
        //error_log(print_r($event, true));
        $info = $this->schedule->getEventInfo($event);
        $registered = false;
        $requiresRegistration = false;
        $bookmarked = $this->isBookmarked($scheduleId, $eventId);
        //error_log(print_r($info, true));

        $sections = array();
        
        // Info
        $locationSection = array();
        if ($info['location']) {
          $location = array(
            'title' => self::argVal($info['location'], 'title', ''),
          );
          if (strtoupper($location['title']) == 'TBA') {
            $location['title'] = 'Location '.$location['title'];
          }
          if (isset($info['location']['address'])) {
            $parts = array();
            if (isset($info['location']['address']['street'])) {
              $parts[] = $info['location']['address']['street'];
            }
            if (isset($info['location']['address']['city'])) {
              $parts[] = $info['location']['address']['city'];
            }
            if (isset($info['location']['address']['state'])) {
              $parts[] = $info['location']['address']['state'];
            }
            if ($parts) {
              $location['subtitle'] = implode(', ', $parts);
            }
          }
          if (isset($info['location']['building']) || isset($info['location']['latlon'])) {
            $location['url'] = $this->buildURLForModule('map', 'detail', array(
              'eventId' => $eventId,
              'start'   => $start,
            ));
            $location['class'] = 'map';
          }
          $locationSection[] = $location;
        }
        if ($locationSection) {
          $sections['location'] = $locationSection; 
        }
        
        $registrationSection = array();
        if ($info['registration']) {
          $requiresRegistration = true;
          $registration = array(
            'title' => '<div class="icon"></div>Registration Required',
            'class' => 'external register',
          );
          
          if ($info['registration']['registered']) {
            $registered = true;
            
            // No a tag so we need to wrap in a div
            $registration['title'] = '<div class="register confirmed"><div class="icon"></div>Registration Confirmed</div>';
            
          } else {
            if (isset($info['registration']['url'])) {
              $printableURL = preg_replace(
                array(';http://([^/]+)/$;', ';http://;'), 
                array('\1',                 ''), $info['registration']['url']);
    
              $registration['url'] = $info['registration']['url'];
              $registration['subtitle'] = 'Register online at '.$printableURL;
            }
            if (isset($info['registration']['fee'])) {
              $registration['title'] .= ' ('.$info['registration']['fee'].')';
            }
          }
          $registrationSection[] = $registration;
        }

        if (isset($info['attendees']) && count($info['attendees'])) {
          $registrationSection[] = array(
            'title' => $this->titleForAttendeeCount($event),
            'url'   => $this->buildBreadcrumbURL('attendees', array(
              'eventId' => $eventId,
              'start'   => $start,
            )),
          );
        }
        if ($registrationSection) {
          $sections['registration'] = $registrationSection; 
        }
        
        // Other fields
        $fieldConfig = $this->loadPageConfigFile('detail', 'detailFields');
        foreach ($fieldConfig as $key => $fieldInfo) {
          if (isset($info[$key])) {
            $type = self::argVal($fieldInfo, 'type', 'text');
            $section = self::argVal($fieldInfo, 'section', 'misc');
            $label = self::argVal($fieldInfo, 'label', '');
            $class = self::argVal($fieldInfo, 'class', '');
            
            $title = $this->valueForType($type, $info[$key]);
            $url = $this->urlForType($type, $info[$key]);

            $item = array();

            if ($label) {
              $item['title'] = $label;
              $item['subtitle'] = $title;
            } else {
              $item['title'] = $title;
            }

            if ($url) {
              $item['url'] = $url;
            }

            if ($class) {
              $item['class'] = $class;
            }
            
            if (!isset($sections[$section])) {
              $sections[$section] = array();
            }
            $sections[$section][] = $item;
          }          
        }
        //error_log(print_r($sections, true));
        
        $latitude = 0;
        $longitude = 0;
        if (isset($info['location']['latlon'])) {
          list($latitude, $longitude) = $info['location']['latlon'];
        }
        
        // Checkins
        $checkedIn = false;
        $checkinThresholdStart = $event->get_start() - 60*15;
        $checkinThresholdEnd = $event->get_end() + 60*15;
        
        // debugging:
        $checkinThresholdStart = time() - ($event->get_end() - $event->get_start()) - 60*15;
        $checkinThresholdEnd = $checkinThresholdStart + ($event->get_end() - $event->get_start()) + 60*15;
        
        if (isset($info['location'], $info['location']['fbPlaceId'])) {
          $facebook = $this->schedule->getFacebookFeed();
          if (!$facebook->needsLogin()) {
            $checkedIn = $facebook->isCheckedIn($info['location']['fbPlaceId'], $checkinThresholdStart);
          }
          
          if ($checkedIn) { 
            $this->assign('fbCheckedIn', true);
          } else {
            $this->assign('fbCheckinURL', $this->buildBreadcrumbURL('checkin', array(
              'service'    => 'facebook',
              'eventURL'   => FULL_URL_PREFIX.ltrim(
                $this->buildBreadcrumbURL($this->page, $this->args, false), '/'),
              'eventTitle' => isset($info['location']['title']) ? $info['location']['title'] : $info['title'],
              'place'      => $info['location']['fbPlaceId'],
              'latitude'   => $latitude, 
              'longitude'  => $longitude
            ), false));
          }
        }
        
        $checkedIn = false;
        if (isset($info['location'], $info['location']['fqPlaceId'])) {        
          $foursquare = $this->schedule->getFoursquareFeed();
          if (!$foursquare->needsLogin()) {
            $checkedIn = $foursquare->isCheckedIn($info['location']['fqPlaceId'], $checkinThresholdStart);
          }
          
          if ($checkedIn) { 
            $this->assign('fqCheckedIn', true);
          } else {
            $this->assign('fqCheckinURL', $this->buildBreadcrumbURL('checkin', array(
              'service'    => 'foursquare',
              'eventURL'   => FULL_URL_PREFIX.ltrim(
                $this->buildBreadcrumbURL($this->page, $this->args, false), '/'),
              'eventTitle' => isset($info['location']['title']) ? $info['location']['title'] : $info['title'],
              'place'      => $info['location']['fqPlaceId'],
              'latitude'   => $latitude, 
              'longitude'  => $longitude
            ), false));
          }
        }
        
        $cookieName = $this->getCookieNameForEvent($scheduleId);
        $this->addInlineJavascript(
          "var COOKIE_PATH = '".COOKIE_PATH."';".
          "var COOKIE_DURATION = '".SCHEDULE_BOOKMARKS_COOKIE_DURATION."';");
        $this->addOnLoad("setBookmarkStates('$cookieName', '$eventId');");

        $this->assign('eventId',              $eventId);
        $this->assign('eventTitle',           $info['title']);
        $this->assign('eventDate',            $this->valueForType('datetime', $info['datetime']));
        $this->assign('sections',             $sections);
        $this->assign('bookmarked',           $bookmarked);
        $this->assign('registered',           $registered);
        $this->assign('requiresRegistration', $requiresRegistration);
        $this->assign('cookieName',           $this->getCookieNameForEvent($scheduleId));
        //error_log(print_r($sections, true));
        break;
        
      case 'attendees':
        $eventId = $this->getArg('eventId');
        $start   = $this->getArg('start', time());
        
        $feed = $this->schedule->getEventFeed();      
        $event = $feed->getItem($eventId, $start);
        if (!$event) {
          throw new Exception("Event not found");
        }
        //error_log(print_r($event, true));
        $info = $this->schedule->getEventInfo($event);
        
        $attendees = array();
        foreach ($info['attendees'] as $attendee) {
          if ($attendee['display_name']) {
            $attendees[] = array(
              'title' => $attendee['display_name'],
            );
          }
        }
        
        $this->assign('eventId',    $eventId);
        $this->assign('eventTitle', $info['title']);
        $this->assign('eventDate',  $this->valueForType('datetime', $info['datetime']));        
        $this->assign('attendees',  $attendees);        
        break;
        
      case 'checkin':
        $service = $this->getArg('service');
        $eventURL = $this->getArg('eventURL');
        
        $this->assign('eventTitle', $this->getArg('eventTitle'));
        $this->assign('service',    $service);
        $this->assign('cancelURL',  $eventURL);
        $this->assign('hiddenArgs', array(
          'service'   => $service,
          'place'     => $this->getArg('place'),
          'latitude'  => $this->getArg('latitude'),
          'longitude' => $this->getArg('longitude'),
          'eventURL'  => $eventURL,
        ));
        break;
      
      case 'addCheckin':
        $service   = $this->getArg('service');
        $place     = $this->getArg('place');
        $message   = $this->getArg('message');
        $latitude  = $this->getArg('latitude');
        $longitude = $this->getArg('longitude');
        $eventURL  = $this->getArg('eventURL');
        
        $coords = null;
        if ($latitude && $longitude) {
          $coords = array($latitude, $longitude);
        }
          
        if ($service == 'facebook' && $place) {
          $facebook = $this->schedule->getFacebookFeed();
          if ($facebook->needsLogin()) {
            $loginURL = $facebook->getLoginURL();
            header("Location: $loginURL");
            exit();
          }
          $facebook->addCheckin($place, $message, $coords);
        
        } else if ($service == 'foursquare' && $latitude && $longitude) {
          $foursquare = $this->schedule->getFoursquareFeed();
          if ($foursquare->needsLogin()) {
            $loginURL = $foursquare->getLoginURL();
            header("Location: $loginURL");
            exit();
          }
          $foursquare->addCheckin($place, $message, $coords);
        }
        
        if ($eventURL) {
          header("Location: $eventURL");
          exit();
        } else {
          $this->redirectTo('index');
        }
        break;
    }
  }
  
}
