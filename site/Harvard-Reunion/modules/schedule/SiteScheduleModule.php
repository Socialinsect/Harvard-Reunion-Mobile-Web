<?php
/**
  * @package Module
  * @subpackage Schedule
  */

/**
  */
require_once realpath(LIB_DIR.'/DateTimeUtils.php');

/**
  */
require_once realpath(LIB_DIR.'/ICalendar.php');

/**
  * @package Module
  * @subpackage Schedule
  */
class SiteScheduleModule extends Module {
  protected $id = 'schedule';
  protected $feeds = null;
  protected $hasFeeds = true;
  protected $timezone;
  protected $feedFields = array(
    'CACHE_LIFETIME'   => 'Cache lifetime (seconds)', 
    'CONTROLLER_CLASS' => 'Controller Class',
    'PARSER_CLASS'     => 'Parser Class',
    'EVENT_CLASS'      => 'Event Class',
    'START_DATE'       => 'Start date (YYYY-MM-DD)',
    'END_DATE'         => 'End date (YYYY-MM-DD)',
  );
  
  protected function getFeeds() {
    if (!isset($this->feeds)) {
      $this->feeds = $this->loadFeedData();
    }
    
    return $this->feeds;
  }

  public function getDefaultFeed() {
    $feeds = $this->getFeeds();
    if ($indexes = array_keys($feeds)) {
      return current($indexes);
    }
  }
  
  protected function getFeedTitle($id) {
    $feeds = $this->getFeeds();
    if (isset($feeds[$id])) {
      return $feeds[$id]['TITLE'];
    } else {
      throw new Exception("Error getting calendar title for index $index");
    }
  }
  
  public function getFeed($id) {
    $feeds = $this->getFeeds();
    if (isset($feeds[$id])) {
      $feedData = $feeds[$id];
      if (!isset($feedData['CONTROLLER_CLASS'])) {
        $feedData['CONTROLLER_CLASS'] = 'CalendarDataController';
      }
      $controller = CalendarDataController::factory($feedData['CONTROLLER_CLASS'], $feedData);
      $controller->setDebugMode($this->getSiteVar('DATA_DEBUG'));
      
      $start = new DateTime($feedData['START_DATE'].' 00:00:00', $this->timezone);
      $end   = new DateTime($feedData['END_DATE'].' 00:00:00 +1 day', $this->timezone);
      
      $controller->setStartDate($start);
      $controller->setEndDate($end);
      
      return $controller;
    } else {
      throw new Exception("Error getting calendar feed for '$id'");
    }
  }
  
  private function valueForType($type, $value) {
    $valueForType = $value;
  
    switch ($type) {
      case 'datetime':
        if ($value instanceOf DayRange) {
          $valueForType = strval($value);
        } else {
          $valueForType = date("D M j", $value->get_start());
          if ($value->get_end() && $value->get_end()!=$value->get_start()) {
            if (date('Ymd', $value->get_start()) != date('Ymd', $value->get_end())) {
              $valueForType .= date(' g:i', $value->get_start());
              if (date('a', $value->get_start()) != date('a', $value->get_end())) {
                $valueForType .= date(' a', $value->get_start());
              }
        
              $valueForType .= date(" - D M j g:i a", $value->get_end());
            } else {
              $valueForType .= "<br/>" . date('g:i', $value->get_start()) . date("-g:i a", $value->get_end());
            }
          } else {
            $valueForType .= "<br/>" . date('g:i a', $value->get_start());
          }
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
      if ($iCalEvent->get_end() - $iCalEvent->get_start() == -1) {
        return date('g:ia', $iCalEvent->get_start());
      } else {
        return date('g:ia', $iCalEvent->get_start()).' - '.date('g:ia', $iCalEvent->get_end());
      }
    } else {
      return strval($iCalEvent->get_range());
    }
  }
  
  private function detailURL($id, $iCalEvent, $addBreadcrumb=true, $noBreadcrumbs=false) {
    $args = array(
      'id'      => $id,
      'eventId' => $iCalEvent->get_uid(),
      'time'    => $iCalEvent->get_start()
    );
  
    if ($noBreadcrumbs) {
      return $this->buildURL('detail', $args);
    } else {
      return $this->buildBreadcrumbURL('detail', $args, $addBreadcrumb);
    }
  }

  protected function initialize() {
    $this->timezone = new DateTimeZone($this->getSiteVar('LOCAL_TIMEZONE'));
    //$this->schedule = new Schedule();
  }

  protected function initializeForPage() {
    switch ($this->page) {
      case 'help':
        break;

      case 'index':
        $day  = $this->getArg('day', 'all');
        $id   = $this->getArg('id', $this->getDefaultFeed());
        $feed = $this->getFeed($id);
        
        $iCalEvents = $feed->items(0);
        
        $eventDays = array();
        $days = array();
        foreach($iCalEvents as $iCalEvent) {
          $date = date('Y-m-d', $iCalEvent->get_start());
          $showThisDate = $day == 'all' || $day == $date;
          
          if (!isset($eventDays[$date])) {
            $dateString = date('l, F j, Y', $iCalEvent->get_start());
            
            if ($showThisDate) {
              $eventDays[$date] = array(
                'title'  => $dateString,
                'events' => array(),
              );
            }
            $days[$date] = $dateString;
          }
          
          if ($showThisDate) {
            $subtitle = $this->timeText($iCalEvent, true);
            if ($briefLocation = $iCalEvent->get_location()) {
              $subtitle .= " | $briefLocation";
            }

            $eventDays[$date]['events'][] = array(
              'url'      => $this->detailURL($id, $iCalEvent),
              'title'    => $iCalEvent->get_summary(),
              'subtitle' => $subtitle,
            );
          }
        }
        
        $this->assign('title',     $this->getFeedTitle($id));
        $this->assign('day',       $day);        
        $this->assign('days',      $days);        
        $this->assign('eventDays', $eventDays);        
        break;
              
      case 'detail':  
        $fieldConfig = $this->loadWebAppConfigFile('schedule-detail', 'detailFields');
        $id = $this->getArg('id', $this->getDefaultFeed());
        
        $feed = $this->getFeed($id);
        
        $time = $this->getArg('time', time());
        $event = $feed->getItem($this->getArg('eventId'), $time);
        if (!$event) {
          throw new Exception("Event not found");
        }
        //error_log(print_r($event, true));
        
        // build the list of attributes
        $allKeys = array_keys($fieldConfig);
        $sections = array();
        foreach ($fieldConfig as $key => $info) {
          $field = array(
            'key' => $key,
          );
          
          $value = $event->get_attribute($key);
          if (empty($value)) { continue; }

          if (isset($info['label'])) {
            $field['label'] = $info['label'];
          }
          
          if (isset($info['class'])) {
            $field['class'] = $info['class'];
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
            $field['title'] = implode(', ', $fieldValues);
          
          } else {
            if (isset($info['type'])) {
              $field['title'] = $this->valueForType($info['type'], $value);
              $field['url']   = $this->urlForType($info['type'], $value);
            } else {
              $field['title'] = nl2br($value);
            }
          }
          
          if (!isset($sections[$info['section']])) {
            $sections[$info['section']] = array();
          }
          
          $sections[$info['section']][] = $field;
        }        

        $this->assign('sections', $sections);
        //error_log(print_r($sections, true));
        break;
    }
  }
}
