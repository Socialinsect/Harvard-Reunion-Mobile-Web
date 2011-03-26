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
        if ($value instanceOf DayRange) {
          $valueForType = strval($value);
        } else {
          $valueForType = date("l, F j", $value->get_start());
          if ($value->get_end() && $value->get_end() != $value->get_start()) {
            $startDate = intval(date('Ymd', $value->get_start()));
            $endDate = intval(date('Ymd', $value->get_end()));
            
            $sameDay = $startDate == $endDate;
            if (!$sameDay) {
              $valueForType .= ', ';
              $endIsMidnight = intval(date('His', $value->get_end())) == 0;
              if ($endIsMidnight && ($endDate - $startDate == 1)) {
                $sameDay = true;
              }
            }
            
            $sameAMPM = date('a', $value->get_start()) == date('a', $value->get_end());
          
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
          }
          $valueForType .= date("</br>g:ia", $value->get_end());
        }
        
        break;

      case 'url':
        $valueForType = str_replace("http://http://", "http://", $value);
        if (strlen($valueForType) && !preg_match('/^http\:\/\//', $valueForType)) {
          $valueForType = 'http://'.$valueForType;
        }
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

  private function timeText($iCalEvent, $timeOnly=false) {
    if ($timeOnly) {
      $sameAMPM = date('a', $iCalEvent->get_start()) == date('a', $iCalEvent->get_end());
    
      $timeString = date(' g:i', $iCalEvent->get_start());
      if (!$sameAMPM) {
        $timeString .= date('a', $iCalEvent->get_start());
      }
      $timeString .= ($sameAMPM ? '-' : ' - ').date("g:ia", $iCalEvent->get_end());
      
      return $timeString;
    } else {
      return strval($iCalEvent->get_range());
    }
  }
  
  private function detailURL($iCalEvent, $addBreadcrumb=true, $noBreadcrumbs=false) {
    $args = array(
      'eventId' => $iCalEvent->get_uid(),
      'start'   => $iCalEvent->get_start()
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

  protected function initializeForPage() {    
    $scheduleId = $this->schedule->getScheduleId();

    switch ($this->page) {
      case 'help':
        break;

      case 'index':
        $day  = $this->getArg('day', 'all');
        
        $feed = $this->schedule->getEventFeed();
        
        $iCalEvents = $feed->items(0);
        
        $eventDays = array();
        $days = array();
        foreach($iCalEvents as $iCalEvent) {
          $date = date('Y-m-d', $iCalEvent->get_start());
          $showThisDate = $day == 'all' || $day == $date;
          
          if (!isset($eventDays[$date])) {
            if ($showThisDate) {
              $eventDays[$date] = array(
                'title'      => date('l, F j, Y', $iCalEvent->get_start()),
                'events'     => array(),
              );
            }
            $days[$date] = date('l, M j', $iCalEvent->get_start());
          }
          
          if ($showThisDate) {
            $event = array(
              'url'      => $this->detailURL($iCalEvent),
              'title'    => $iCalEvent->get_summary(),
              'subtitle' => $this->timeText($iCalEvent, true),
            );
            if ($this->isBookmarked($scheduleId, $iCalEvent->get_uid())) {
              $event['class'] = 'bookmarked';
            }            
            
            $eventDays[$date]['events'][] = $event;
          }
        }
        
        $this->assign('day',       $day);        
        $this->assign('days',      $days);        
        $this->assign('eventDays', $eventDays);        
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
        
        // build the list of attributes
        $fieldConfig = $this->loadPageConfigFile('detail', 'detailFields');
        $allKeys = array_keys($fieldConfig);
        $sections = array();
        foreach ($fieldConfig as $key => $info) {
          $field = array(
            'key' => $key,
          );
          
          $value = $event->get_attribute($key);
          if (empty($value)) { continue; }

          if (isset($info['conditionalField'], $info['conditionalValue'])) {
            $condKey = $info['conditionalField'];
            $condValue = $event->get_attribute($condKey);
            
            if ($condValue != $info['conditionalValue']) {
              continue;
            }
          }

          if (isset($info['label'])) {
            $field['title'] = $info['label'];
          }
          
          if (isset($info['class'])) {
            $field['class'] = $info['class'];
          }
          
          if (!$value && isset($info['emptyValue'])) {
            $title = $info['emptyValue'];
            
          } else {
            $title = '';
            if (isset($info['prefix'])) {
              $title .= $info['prefix'];
            }
            
            if (is_array($value)) {		
              $fieldValues = array();
              foreach ($value as $item) {
                $fieldValue = '';
                $fieldValueUrl = null;
                
                if (isset($info['type'])) {
                  $fieldValue  = $this->valueForType($info['type'], $item);
                  $fieldValueUrl = $this->urlForType($info['type'], $item);
                } else {
                  $fieldValue = $item;
                }
                
                if (isset($fieldValueUrl)) {
                  $fieldValue = '<a href="'.$fieldValueUrl.'">'.$fieldValue.'</a>';
                }
                
                $fieldValues[] = $fieldValue;
              }
              $title .= implode(', ', $fieldValues);
            
            } else {
              if (isset($info['type'])) {
                $title .= $this->valueForType($info['type'], $value);
                $field['url'] = $this->urlForType($info['type'], $value);
              } else {
                $title .= nl2br($value);
              }
            }
            
            if (isset($info['suffix'])) {
              $title .= $info['suffix'];
            }
          }
          
          $hasMultipleLocations = false;
          if (isset($info['multipleLocationsField'])) {
            $hasMultipleLocations = $event->get_attribute($info['multipleLocationsField']) == 'yes';
          } 
          
          if ($hasMultipleLocations) {
            $title .= ' (multiple locations)';
          
          } else if (isset($info['trumbaWebLinkField']) || isset($info['buildingIDField'])) {
            $validQuery = false;
            $args = array(
              'title' => $event->get_attribute('summary'),
              'address' => str_replace('<br/>', ' ', $this->valueForType('datetime', $event->get_attribute('datetime')))."<br/>{$title}",
            );
            
            if (isset($info['trumbaWebLinkField'])) {
              // parse the doohickey
              $weblink = $event->get_attribute($info['trumbaWebLinkField']);
              $parts = explode(',', $weblink);
              if (count($parts) == 2) {
                $args['lat'] = $parts[0];
                $args['lon'] = $parts[1];
                $validQuery = true;
              }
            }
            
            if (isset($info['buildingIDField'])) {
              $args['building'] = $event->get_attribute($info['buildingIDField']);
              
              $buildingInfo = HarvardMapDataController::getBldgDataByNumber($args['building']);
              if (isset($buildingInfo['attributes'], $buildingInfo['attributes']['Address'])) {
                $field['subtitle'] = mb_convert_case($buildingInfo['attributes']['Address'], MB_CASE_TITLE);
                
                if (isset($buildingInfo['attributes']['City'])) {
                  $field['subtitle'] .= ', '.mb_convert_case($buildingInfo['attributes']['City'], MB_CASE_TITLE);
                }
                if (isset($buildingInfo['attributes']['State'])) {
                  $field['subtitle'] .= ', '.$buildingInfo['attributes']['State'];
                }
              }
              //error_log(print_r($buildingInfo, true));
              
              $validQuery = true;
            }
            
            if ($validQuery) {
              $field['url'] = $this->buildURLForModule('map', 'detail', $args);
            }
          }
                    
          if (isset($field['title'])) {
            $field['subtitle'] = $title;
          } else {
            $field['title'] = $title;
          }
          
          if (!isset($field['subtitle']) && isset($info['subtitle'])) {
            $field['subtitle'] = $info['subtitle'];
          }
          
          if (!isset($field['url']) && isset($info['url'])) {
            $field['url'] = $info['url'];
          }

          if (!isset($sections[$info['section']])) {
            $sections[$info['section']] = array();
          }
          
          $sections[$info['section']][] = $field;
        }
        
        $cookieName = $this->getCookieNameForEvent($scheduleId);
        $this->addInlineJavascript(
          "var COOKIE_PATH = '".COOKIE_PATH."';".
          "var COOKIE_DURATION = '".SCHEDULE_BOOKMARKS_COOKIE_DURATION."';");
        $this->addOnLoad("setBookmarkStates('$cookieName', '$eventId');");

        $this->assign('eventId',    $eventId);
        $this->assign('bookmarked', $this->isBookmarked($scheduleId, $eventId));
        $this->assign('cookieName', $this->getCookieNameForEvent($scheduleId));
        $this->assign('sections',   $sections);
        //error_log(print_r($sections, true));
        break;
    }
  }
}
