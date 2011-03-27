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
  protected $feeds = null;
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

  protected function initialize() {
    $user = $this->getUser('HarvardReunionUser');
    $this->schedule = new Schedule($user);
  }
  
  protected function eventMatchesCategory($category, $event) {
    if ($category == 'mine') {
      return 
        $this->isBookmarked($this->schedule->getScheduleId(), $event->get_uid()) ||
        $this->schedule->isRegisteredForEvent($event);
    }
    
    $trumbaCategories = $event->get_attribute('categories');
    
    $eventCategory = 'reunion';
    foreach ($trumbaCategories as $trumbaCategory) {
      if (stripos($trumbaCategory, 'Other') !== false) {
        $eventCategory = 'other';
        break;
      }
      if (stripos($trumbaCategory, 'Children') !== false) {
        $eventCategory = 'children';
        break;
      }
    }
    
    return $category == $eventCategory;
  }

  protected function initializeForPage() {    
    $scheduleId = $this->schedule->getScheduleId();

    switch ($this->page) {
      case 'help':
        break;

      case 'index':
        $category  = $this->getArg('category', 'reunion');
        
        $feed = $this->schedule->getEventFeed();
        
        $events = $feed->items(0);
        
        $categories = array(
          'reunion'  => $this->schedule->getReunionNumber().'th Reunion Events',
          'other'    => 'Other Events',
          'children' => 'Children\'s Events',
          'mine'     => 'My Schedule',
        );
        
        $hasOtherEvents = false;
        $hasChildrensEvents = false;

        $eventDays = array();
        foreach($events as $event) {
          $date = date('Y-m-d', $event->get_start());
          
          $showThisEvent = $this->eventMatchesCategory($category, $event);
          
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
        $registered = $this->schedule->isRegisteredForEvent($event);
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
          $registration = array(
            'title' => '<div class="icon"></div>Registration Required',
            'class' => 'external register',
          );
          
          if ($registered) {
            // No a tag so we need to wrap in a div
            $registration['title'] = '<div class="register"><div class="icon confirmed"></div>Registration Confirmed</div>';
            
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
            'title' => 'Attendees',
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
        
        $cookieName = $this->getCookieNameForEvent($scheduleId);
        $this->addInlineJavascript(
          "var COOKIE_PATH = '".COOKIE_PATH."';".
          "var COOKIE_DURATION = '".SCHEDULE_BOOKMARKS_COOKIE_DURATION."';");
        $this->addOnLoad("setBookmarkStates('$cookieName', '$eventId');");

        $this->assign('eventId',    $eventId);
        $this->assign('eventTitle', $info['title']);
        $this->assign('eventDate',  $this->valueForType('datetime', $info['datetime']));
        $this->assign('sections',   $sections);
        $this->assign('bookmarked', $bookmarked);
        $this->assign('registered', $registered);
        $this->assign('cookieName', $this->getCookieNameForEvent($scheduleId));
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
        
        // Currently a first name sort
        usort($info['attendees'], array(get_class($this), 'attendeeSort'));
        
        foreach ($info['attendees'] as $attendee) {
          $parts = array();
          if (isset($attendee['first_name'])) {
            $parts[] = $attendee['first_name'];
          }
          if (isset($attendee['last_name'])) {
            $parts[] = $attendee['last_name'];
          }
          if (isset($attendee['suffix'])) {
            $parts[] = $attendee['suffix'];
          }
          
          if (count($parts)) {
            $attendees[] = array(
              'title' => implode(' ', $parts),
            );
          }
        }
        
        $this->assign('eventId',    $eventId);
        $this->assign('eventTitle', $info['title']);
        $this->assign('eventDate',  $this->valueForType('datetime', $info['datetime']));        
        $this->assign('attendees',  $attendees);        
        break;
    }
  }
  
  private function attendeeSort($a, $b) {
    $al = self::argVal($a, 'first_name', self::argVal($a, 'first_name', ''));
    $bl = self::argVal($b, 'first_name', self::argVal($b, 'first_name', ''));
  
    return strcasecmp($al, $bl);
  }
}
