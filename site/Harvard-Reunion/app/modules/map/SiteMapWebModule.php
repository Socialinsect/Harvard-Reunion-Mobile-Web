<?php

/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

/**
  * @package Module
  * @subpackage Map
  */

includePackage('Maps');
require_once realpath(LIB_DIR.'/Maps/ArcGISParser.php');

class SiteMapWebModule extends MapWebModule {
  protected $schedule = null;
  
  protected function getSchedule() {
    if (!$this->schedule) {
      $user = $this->getUser('HarvardReunionUser');
      $this->schedule = new Schedule($user);
    }
    
    return $this->schedule;
  }
  
  protected function getBookmarkCookie() {
    $schedule = $this->getSchedule();

    return $this->configModule.'bookmarks_'.$schedule->getScheduleId();
  }


  public function getBuildingDataById($buildingId) {
    $buildingData = array();
  
    if (!$this->feeds) {
      $this->feeds = $this->loadFeedData();
    }
    
    $dataController = null;
    foreach ($this->feeds as $feedIndex => $feedData) {
      if ($feedData['TITLE'] == 'Search Results') {
        $dataController = MapDataController::factory($feedData['CONTROLLER_CLASS'], $feedData);
        $dataController->setCategory($feedIndex);
        break;
      }
    }
    
    if ($dataController) {
      $feature = $dataController->getFeature($buildingId);
      
      $description = $feature->getDescription();
      $labels = array(
        'Building Name',
        'Address',
        'City',
        'State',
      );
      foreach ($description as $field) {
        if (in_array($field['label'], $labels)) {
          $buildingData[$field['label']] = $field['title'];
        }
      }
      
      $geometry = $feature->getGeometry();
      $coords = $geometry->getCenterCoordinate();
      $dataProjection = $dataController->getProjection();
      $projector = new MapProjector();
      $projector->setSrcProj($dataProjection);
      $buildingData['coords'] = $projector->projectPoint($geometry->getCenterCoordinate());
    }
    
    return $buildingData;
  }
  
  protected function initializeMapElements($mapElement, $imgController, $imageWidth, $imageHeight) {
    if (!$imgController->isStatic() && $this->pagetype == 'compliant' && $this->page != 'fullscreen') {
      // shrink dynamic map height to make it easier to scroll page
      if ($this->platform == 'bbplus') {
        $imageHeight = 100;
      } else {
        $imageHeight = 180;
      }
    }
    
    parent::initializeMapElements($mapElement, $imgController, $imageWidth, $imageHeight);
  }

  protected function detailURLForBookmark($aBookmark) {
    parse_str($aBookmark, $params);
    if (isset($params['eventId'])) {
      return $this->buildBreadcrumbURL('detail', $params, true);
    }
    
    return parent::detailURLForBookmark($aBookmark);
  }

  protected function generateBookmarkOptions($cookieID) {
    if (isset($this->args['eventId'])) {
      // replace location detail page with event detail page
      $cookieParams = array(
        'eventId' => $this->args['eventId'],
        'start'   => $this->getArg('start', time()),
      );
      $cookieID = http_build_query($cookieParams, null, '&');
    }
    
    parent::generateBookmarkOptions($cookieID);
  }
  
  protected function getTitleForBookmark($aBookmark) {
    parse_str($aBookmark, $params);
    if (isset($params['eventId'], $params['start'])) {
      $eventInfo = $this->getEventDetails($params['eventId'], $params['start']);
      
      if (isset($eventInfo['location'], $eventInfo['location']['title'])) {
        return array($eventInfo['title'], $eventInfo['location']['title']);
      } else {
        return array($eventInfo['title']);
      }
    }
    
    return parent::getTitleForBookmark($aBookmark);
  }
 
  protected function generateTabForKey($tabKey, $feature, $dataController, &$tabJavascripts) {
    if ($tabKey == 'event') {
      // event information already set if this is an event page
      return isset($this->args['eventId']);
    } else {
      return parent::generateTabForKey($tabKey, $feature, $dataController, $tabJavascripts);
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
  
  private function getEventDetails($eventId, $start) {
    $schedule = $this->getSchedule();
    
    $event = $schedule->getEvent($eventId, $start);
    if (!$event) {
      throw new Exception("Event '$eventId' not found");
    }
    //error_log(print_r($event, true));
    $eventInfo = $schedule->getEventInfo($event);
    $registered = $schedule->isRegisteredForEvent($event);
    //error_log(print_r($eventInfo, true));
    
    $eventAddress = '';
    if ($eventInfo['location']) {
      if (strtoupper($eventInfo['title']) == 'TBA') {
        $eventInfo['title'] = 'Location '.$eventInfo['title'];
      }
      if (isset($eventInfo['location']['address'])) {
        $parts = array();
        if (isset($eventInfo['location']['address']['street'])) {
          $parts[] = $eventInfo['location']['address']['street'];
        }
        if (isset($eventInfo['location']['address']['city'])) {
          $parts[] = $eventInfo['location']['address']['city'];
        }
        if (isset($eventInfo['location']['address']['state'])) {
          $parts[] = $eventInfo['location']['address']['state'];
        }
        if ($parts) {
          $eventAddress = implode(', ', $parts);
        }
      }
    }
    
    $eventRegistration = null;
    if ($eventInfo['registration']) {
      if ($registered) {
        $eventRegistration = '<p class="register confirmed">Registration Confirmed</p>';
      } else {
        $eventRegistration = '<p class="register">Registration Required'.
          (isset($eventInfo['registration']['fee']) ? ' ('.$eventInfo['registration']['fee'].')' : '').
          '</p>';
      }
    }

    $eventLinks = array(
      array(
        'title' => 'More event info',
        'url' => $this->buildURLForModule('schedule', 'detail', array(
          'eventId' => $eventId,
          'start'   => $start,
        )),
      ),
    );
    
    $this->assign('eventTitle',        $eventInfo['title']);
    $this->assign('eventDate',         $this->valueForType('datetime', $eventInfo['datetime']));
    $this->assign('eventLocation',     self::argVal($eventInfo['location'], 'title', ''));
    $this->assign('eventAddress',      $eventAddress);
    $this->assign('eventRegistration', $eventRegistration);
    $this->assign('eventLinks',        $eventLinks);
    
    return $eventInfo;
  }

  protected function initializeForPage() {
    $callParent = true;
  
    switch ($this->page) {
      case 'detail':
        $eventId = $this->getArg('eventId');
        $start   = $this->getArg('start', time());
  
        if ($eventId) {
          $this->setPageTitle('Event Location');
        
          $eventInfo = $this->getEventDetails($eventId, $start);
          
          if (isset($eventInfo['location'], $eventInfo['location']['building'])) {
            // Figure out the full Harvard campus category
            if (!$this->feeds) {
              $this->feeds = $this->loadFeedData();
            }
            foreach ($this->feeds as $id => $feed) {
              if ($feed['TITLE'] == 'Search Results') {
                $this->args['category'] = $id;
                $this->args['featureindex'] = $eventInfo['location']['building'];
                break;
              }
            }
          } else if (isset($eventInfo['location'], $eventInfo['location']['latlon'])) {
            $this->args['lat'] = $eventInfo['location']['latlon'][0];
            $this->args['lon'] = $eventInfo['location']['latlon'][1];
            
          } else {
            throw new Exception("Event '$eventId' not found");
          }
          
          $this->args['title'] = $eventInfo['title'];
          if (isset($eventInfo['location'], $eventInfo['location']['title'])) {
            $this->args['address'] = $eventInfo['location']['title'];
          }
  
          if (!isset($this->args['featureindex']) && !isset($this->args['group'])) {
            // Event cookie with no building
            $cookieParams = array(
              'eventId' => $this->args['eventId'],
              'start' => $this->args['start'],
            );
            $cookieID = http_build_query($cookieParams, null, '&');
            $this->generateBookmarkOptions($cookieID);
          }
        }
        break;
      
      case 'search':
        if (isset($this->args['filter'])) {
          $searchTerms = $this->args['filter'];
          $mapSearch = $this->getSearchClass();
          $searchResults = $mapSearch->searchCampusMap($searchTerms);
  
          $lastResultArgs = array();
          $places = array();
          $eventPlaces = array();
          
          foreach ($searchResults as $result) {
            $lastResultArgs = shortArrayFromMapFeature($result);
            $place = array(
                'title'    => $result->getTitle(),
                'subtitle' => $result->getSubtitle(),
                'url'      => $this->buildBreadcrumbURL('detail', $lastResultArgs),
                'class'    => 'place',
            );
            $places[] = $place;
          }
  
          $user = $this->getUser('HarvardReunionUser');
          $schedule = new Schedule($user);
  
          $events = $schedule->searchEvents($searchTerms);
          foreach ($events as $event) {
            $eventInfo = $schedule->getEventInfo($event);
            if ($eventInfo['location'] && !self::argVal($eventInfo['location'], 'multiple', false) &&
                (isset($eventInfo['location']['latlon']) || isset($eventInfo['location']['building']))) {
              $lastResultArgs = array(
                'eventId' => $eventInfo['id'],
                'start'   => $event->get_start(),
              );
              
              $place = array(
                'title'    => $eventInfo['title'],
                'subtitle' => self::argVal($eventInfo['location'], 'title', ''),
                'url'      => $this->buildBreadcrumbURL('detail', $lastResultArgs),
                'class'    => 'event',
              );
              if (strtoupper($place['subtitle']) == 'TBA' && isset($eventInfo['location']['address'])) {
                $parts = array();
                if (isset($eventInfo['location']['address']['street'])) {
                  $parts[] = $eventInfo['location']['address']['street'];
                }
                if (isset($eventInfo['location']['address']['city'])) {
                  $parts[] = $eventInfo['location']['address']['city'];
                }
                if (isset($eventInfo['location']['address']['state'])) {
                  $parts[] = $eventInfo['location']['address']['state'];
                }
                if ($parts) {
                  $place['subtitle'] = implode(', ', $parts);
                }
              }
              
              $eventPlaces[] = $place;
            }
          }
          
          if ((count($places) + count($eventPlaces)) == 1) {
            $this->redirectTo('detail', $lastResultArgs);
          }
          
          $this->assign('searchTerms', $searchTerms);
          $this->assign('places',      $places);
          $this->assign('eventPlaces', $eventPlaces);
          
        } else {
          $this->redirectTo('index');
        }
        
        $callParent = false;
        break;
      
      case 'bookmarks':
        $feedGroups = array();
        $places = array();
        $events = array();

        foreach ($this->getBookmarks() as $aBookmark) {
          if ($aBookmark) { // prevent counting empty string
            $titles = $this->getTitleForBookmark($aBookmark);
            $subtitle = count($titles) > 1 ? $titles[1] : null;
            $item = array(
              'title'    => $titles[0],
              'subtitle' => $subtitle,
              'url'      => $this->detailURLForBookmark($aBookmark),
            );
            
            parse_str($aBookmark, $params);
            if (isset($params['group'])) {
              $feedGroups[] = $item;
            
            } else if (isset($params['eventId'])) {
              $events[] = $item;
              
            } else {
              $places[] = $item;
            }
          }
        }
        $this->assign('groupAlias', $this->getOptionalModuleVar('GROUP_ALIAS_PLURAL', 'Campuses'));
        $this->assign('groups', $feedGroups);
        $this->assign('places', $places);
        $this->assign('events', $events);
        
        $callParent = false;
        break;
    }
    
    if ($callParent) {
      parent::initializeForPage();
    }
  }
}
