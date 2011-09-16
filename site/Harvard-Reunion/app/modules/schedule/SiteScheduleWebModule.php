<?php

/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

/**
  * @package Module
  * @subpackage Schedule
  */

includePackage('Maps');

class SiteScheduleWebModule extends WebModule {
  protected $id = 'schedule';
  protected $user = null;
  protected $schedule = null;
  
  protected function getCategory($categories) {
    $category = $this->schedule->getDefaultCategory();
    if (!isset($categories[$category])) {
      $category = 'mine';
    }
    
    $categoryCookieName = $this->configModule.'category_'.$this->schedule->getScheduleId();
    
    if (isset($this->args['category'], $categories[$this->args['category']])) {
      $category = $this->args['category'];
      
      // Remember cookie
      $expires = time() + Kurogo::getOptionalSiteVar('TAB_COOKIE_LIFESPAN', 3600);
      setCookie($categoryCookieName, $category, $expires, COOKIE_PATH);
      
    } else if (isset($_COOKIE[$categoryCookieName], $categories[$_COOKIE[$categoryCookieName]])) {
      $category = $_COOKIE[$categoryCookieName];
    }
    
    return $category;
  }
  
  protected function getBookmarkCookie() {
    return $this->configModule.'bookmarks_'.$this->schedule->getScheduleId();
  }
  
  private function valueForType($type, $value) {
    $valueForType = $value;
  
    switch ($type) {
      case 'datetime':
        $valueForType = $this->datetimeText($value, false, '<br/>');
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

  private function datetimeText($datetime, $timeOnly=false, $separator='') {
    $start = $datetime->get_start();
    $end = $datetime->get_end();
  
    $string = '';
  
    if ($end == $start) {
      if (!$timeOnly) {
        $string .= date('l, F j', $start).$separator;
      }
      $string .= date('g:ia', $start);
        
    } else {
      $allDay = $datetime instanceOf DayRange;
      
      $startDay = intval(date('Ymd', $start));
      $endDay   = intval(date('Ymd', $end));
        
      $sameDay = $startDay == $endDay;
      if (!$sameDay) {
        $endIsBefore5am = intval(date('H', $end)) < 5;
        if ($endIsBefore5am && ($endDay - $startDay == 1)) {
          $sameDay = true;
        }
      }
      
      if ($allDay) {
        if (!$timeOnly) {
          $string .= date('l, F j', $start);
          if (!$sameDay) {
            $string .= date(' - l, F j', $end);
          }
        }
        $string .= $separator;
        if ($sameDay || !$timeOnly) {
          $string .= 'All day';
        } else {
          $string .= ($endDay - $startDay).' days';
        }
        
      } else {
        $sameAMPM = date('a', $start) == date('a', $end);
  
        if (!$timeOnly) {
          $string .= date('l, F j', $start).($sameDay ? $separator : ', ');
        }
        
        $string .= date('g:i', $start);
        
        if (!$sameAMPM) {
          $string .= date('a', $start);
        }
        
        if (!$sameDay && !$timeOnly) {
          $string .= $separator.' - '.date('l, F j, ', $end);
        } else if ($sameAMPM) {
          $string .= '-';
        } else {
          $string .= ' - ';
        }
        $string .= date('g:ia', $end);
      }
    }

    return $string;
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
    $this->user = $this->getUser('HarvardReunionUser');
    $this->schedule = new Schedule($this->user);
  }

  protected function initializeForPage() {    
    $scheduleId = $this->schedule->getScheduleId();

    switch ($this->page) {
      case 'help':
        break;

      case 'index':
        $categories = array_merge(
          array('mine' => 'My Schedule'), 
          $this->schedule->getEventCategories()
        );
        $category = $this->getCategory($categories);
        
        if ($category == 'mine') {
          $events = $this->schedule->getEvents();
          foreach ($events as $i => $event) {
            if (!$this->hasBookmark($event->get_uid()) &&
                !$this->schedule->isRegisteredForEvent($event)) {
              unset($events[$i]);
            }
          }
        } else {
          $events = $this->schedule->getEvents($category);
        }
        
        $eventDays = array();
        foreach($events as $event) {
          $info = $this->schedule->getBriefEventInfo($event);
        
          $date = date('Y-m-d', $info['datetime']->get_start());
          
          if (!isset($eventDays[$date])) {
            $eventDays[$date] = array(
              'title'  => date('l, F j, Y', $info['datetime']->get_start()),
              'events' => array(),
            );
          }
          
          $eventInfo = array(
            'url'      => $this->detailURL($event),
            'title'    => $info['title'],
            'subtitle' => $this->datetimeText($info['datetime'], true),
          );
          if ($this->hasBookmark($event->get_uid())) {
            $eventInfo['class'] = 'bookmarked';
          }
          
          if ($info['attending']) {
            $eventInfo['class'] = 'bookmarked';
          }
          
          $eventDays[$date]['events'][] = $eventInfo;
        }
        
        $this->assign('category',   $category);        
        $this->assign('categories', $categories);        
        $this->assign('eventDays',  $eventDays);        
        break;
              
      case 'detail':
        $eventId = $this->getArg('eventId');
        $start   = $this->getArg('start', time());
                
        $event = $this->schedule->getEvent($eventId, $start);
        if (!$event) {
          throw new Exception("Event not found");
        }
        
        $this->generateBookmarkOptions($event->get_uid());

        //error_log(print_r($event, true));
        $info = $this->schedule->getEventInfo($event);
        $attending = $info['attending'];
        $requiresRegistration = false;
        //error_log(print_r($info, true));

        $sections = array();

        // Checkins
        if (isset($info['location'], $info['location']['foursquareId'])) { 
          $now = time();
          $checkinThresholdStart = $event->get_start() - 60*15;
          $checkinThresholdEnd = $event->get_end() + 60*15;
          
          if (Kurogo::getOptionalSiteVar('FOURSQUARE_CHECKIN_DEBUG', false)) {
            $checkinThresholdStart = time() - ($event->get_end() - $event->get_start()) - 60*15;
            $checkinThresholdEnd = $checkinThresholdStart + ($event->get_end() - $event->get_start()) + 60*15;
          }
          
          if ($now >= $checkinThresholdStart && $now <= $checkinThresholdEnd) {
            $checkin = array(
              'title' => 'foursquare checkin',
              'class' => 'fqCheckin',
              'url'   => $this->buildBreadcrumbURL('checkin', array(
                'eventId' => $eventId,
                'start'   => $start,
              ))
            );
            
            $venueCheckins = array(
              'self'    => array(),
              'friends' => array(),
              'others'  => array(),
            );
            
            $foursquare = $this->schedule->getFoursquareFeed();
            if (!$foursquare->needsLogin()) {
              $venueCheckins = $foursquare->getVenueCheckins($info['location']['foursquareId']);
            }
            $this->assign('checkins', $venueCheckins);
            
            $sections['checkin'] = array($checkin);
          }
        }
        
        // Info
        $locationSection = array();
        if ($info['location']) {
          if (self::argVal($info['location'], 'multiple', false)) {
            $location = array(
              'title' => 'Multiple locations',
            );
            $locationText = self::argVal($info['location'], 'title', '');
            if ($locationText) {
              $location['subtitle'] = $locationText;
            }

          } else {
            $location = array(
              'title' => self::argVal($info['location'], 'title', ''),
            );
            if (strtoupper($location['title']) == 'TBA') {
              $location['title'] = 'Location '.$location['title'];
            }
            if (isset($info['location']['address'])) {
              $parts = array();
              if (self::argVal($info['location']['address'], 'street')) {
                $parts[] = $info['location']['address']['street'];
              }
              if (self::argVal($info['location']['address'], 'city')) {
                $parts[] = $info['location']['address']['city'];
              }
              if (self::argVal($info['location']['address'], 'state')) {
                $parts[] = $info['location']['address']['state'];
              }
              if ($parts) {
                $location['subtitle'] = implode(', ', $parts);
              }
            }
            if (self::argVal($info['location'], 'building') || self::argVal($info['location'], 'latlon')) {
              $location['url'] = $this->buildURLForModule('map', 'detail', array(
                'eventId' => $eventId,
                'start'   => $start,
              ));
              $location['class'] = 'map';
            }
          }
          $locationSection[] = $location;
        }
        if ($locationSection) {
          $sections['location'] = $locationSection; 
        }
        
        // Registration
        $registrationSection = array();
        if ($info['registration']) {
          $requiresRegistration = true;
          $registration = array(
            'title' => 'Registration Required',
            'class' => 'external',
          );
          
          if ($info['registration']['registered']) {
            $registration['label'] = '<img class="register confirmed" src="/common/images/badge-confirmed'.$this->imageExt.'"/>';
            $registration['title'] = 'Registration Confirmed';
            
          } else {
            $registration['label'] = '<img class="register" src="/common/images/badge-register'.$this->imageExt.'"/> ';
            
            if (self::argVal($info['registration'], 'url')) {
              $printableURL = preg_replace(
                array(';http://([^/]+)/$;', ';http://;'), 
                array('\1',                 ''), $info['registration']['url']);
    
              $registration['url'] = $info['registration']['url'];
              $registration['linkTarget'] = 'reunionAlumni';
              $registration['subtitle'] = 'Register online at '.$printableURL;
            }
            if (self::argVal($info['registration'], 'fee')) {
              $registration['title'] .= ' ('.$info['registration']['fee'].')';
            }
          }
            
          if ($this->pagetype != 'basic') {
            $registration['title'] = '<div class="register">'.$registration['title'].'</div>';
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
            $type       = self::argVal($fieldInfo, 'type', 'text');
            $section    = self::argVal($fieldInfo, 'section', 'misc');
            $label      = self::argVal($fieldInfo, 'label', '');
            $class      = self::argVal($fieldInfo, 'class', '');
            $linkTarget = self::argVal($fieldInfo, 'linkTarget', '');
            
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

            if ($linkTarget) {
              $item['linkTarget'] = $linkTarget;
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
        
        $this->assign('eventId',              $eventId);
        $this->assign('eventTitle',           $info['title']);
        $this->assign('eventDate',            $this->valueForType('datetime', $info['datetime']));
        $this->assign('sections',             $sections);
        $this->assign('attending',            $attending);
        $this->assign('requiresRegistration', $requiresRegistration);
        //error_log(print_r($sections, true));
        break;
        
      case 'attendees':
        $eventId = $this->getArg('eventId');
        $start   = $this->getArg('start', time());
        $range = $this->getArg('range', null);

        $event = $this->schedule->getEvent($eventId, $start);
        if (!$event) {
          throw new Exception("Event not found");
        }
        //error_log(print_r($event, true));
        $info = $this->schedule->getEventInfo($event);
        //error_log(print_r($info, true));
        
        $allAttendees = $info['attendees'];
      
        $letterGroups = $this->schedule->getAttendeeFirstLetterGroups($allAttendees);
        if (!$letterGroups || $range) {
          $filtered = $allAttendees;
          if ($range) {
            $printableRange = implode(' - ', explode('-', $range));
            $this->setPageTitle($this->getPageTitle()." ($printableRange)");
            $this->setBreadcrumbTitle($this->getBreadcrumbTitle()." ($printableRange)");
            $this->setBreadcrumbLongTitle($this->getBreadcrumbLongTitle()." ($printableRange)");
            $filtered = $this->schedule->getAttendeesForLetterRange($allAttendees, $range);
          }
          
          $attendees = array();
          foreach ($filtered as $attendee) {
            if ($attendee['display_name']) {
              $attendees[] = array(
                'title' => $attendee['display_name'],
              );
            }
          }
          $this->assign('attendees',  $attendees);
           
        } else {
          $args = $this->args;
        
          $groups = array();
          foreach ($letterGroups as $range => $rangeInfo) {
            $args['range'] = $range;
            
            $rangeInfo['url'] = $this->buildBreadcrumbURL('attendees', $args);
            $groups[] = $rangeInfo;
          }
          
          $this->assign('groups',  $groups);
        }
        
        $this->assign('eventTitle', $info['title']);
        $this->assign('eventDate',  $this->valueForType('datetime', $info['datetime']));        
        $this->assign('authority',  $this->user->getAuthenticationAuthorityIndex());
        break;
        
      case 'checkin':
        $eventId       = $this->getArg('eventId');
        $start         = $this->getArg('start', time());
        $checkinString = $this->getArg('checkinResult', '[]');
        
        $event = $this->schedule->getEvent($eventId, $start);
        if (!$event) {
          throw new Exception("Event not found");
        }
        
        $info = $this->schedule->getEventInfo($event);
        if (isset($info['location'], $info['location']['foursquareId'])) { 
          $venue = $info['location']['foursquareId'];
          $foursquare = $this->schedule->getFoursquareFeed();
        
          $venueCheckins = $foursquare->getVenueCheckins($venue);
          if ($venueCheckins) {
            $this->assign('checkins', $venueCheckins);
          }
        
          $this->addInternalJavascript('/common/javascript/lib/utils.js');
          
          $this->addOnLoad('autoupdateContent("autoupdateHeader", "'.URL_PREFIX.$this->id.'/checkinHeaderContent?'.
              http_build_query(array('venue' => $venue), null, '&').'");');
              
          $this->addOnLoad('autoupdateContent("autoupdateContent", "'.URL_PREFIX.$this->id.'/checkinContent?'.
              http_build_query(array('venue' => $venue), null, '&').'");');
          
          $checkinResult = json_decode($checkinString, true);
          
          if ($checkinResult && !isset($checkinResult['error'])) {
            $this->addOnLoad('_gaq.push('.json_encode(array(
              '_trackEvent', GA_EVENT_CATEGORY, 'Foursquare Checkin', $info['title'],
            )).');');
          }
          
          $this->assign('eventTitle', $info['title']);
          $this->assign('hiddenArgs', array(
            'venue'     => $venue,
            'returnURL' => URL_PREFIX.ltrim($this->buildBreadcrumbURL($this->page, $this->args, false), '/'),
          ));
          $this->assign('checkinResult', $checkinResult);
        }
        break;
        
      case 'checkinHeaderContent':
        $venue = $this->getArg('venue');
        $foursquare = $this->schedule->getFoursquareFeed();
        
        $venueCheckins = $foursquare->getVenueCheckins($venue);
        if ($venueCheckins) {
          $this->assign('checkins', $venueCheckins);
        }
        break;
      
      case 'checkinContent':
        $venue = $this->getArg('venue');
        $foursquare = $this->schedule->getFoursquareFeed();
        
        $venueCheckins = $foursquare->getVenueCheckins($venue);
        if ($venueCheckins) {
          $this->assign('checkins', $venueCheckins);
        }
        break;
      
      case 'addCheckin':
        $venue     = $this->getArg('venue');
        $message   = $this->getArg('message');
        $returnURL = $this->getArg('returnURL');
        
        $foursquare = $this->schedule->getFoursquareFeed();
        $results = $foursquare->addCheckin($venue, $message);
        
        $checkinParams = array('checkinResult' => json_encode($results));
        
        
        if ($returnURL) {
          $parts = parse_url($returnURL);
          $delimiter = '&';
          if ($parts && (!isset($parts['query']) || !$parts['query'])) {
            $delimiter = '?';
          }
        
          header('Location: '.$returnURL.$delimiter.http_build_query($checkinParams, null, '&'));
          exit();
        } else {
          $this->redirectTo('index', $checkinParams);
        }
        break;
    }
  }
  
}
