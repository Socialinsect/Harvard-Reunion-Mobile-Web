<?php
/**
  * @package Module
  * @subpackage Home
  */

/**
  * @package Module
  * @subpackage Home
  */
class SiteMapWebModule extends MapWebModule {
  public function getBuildingDataById($buildingId) {
    return HarvardMapDataController::getBldgDataByNumber($buildingId);
  }

  protected function staticMapImageDimensions() {
    list($imageWidth, $imageHeight) = parent::staticMapImageDimensions();
    
    switch ($this->pagetype) {
      case 'compliant':
        if ($this->platform == 'bbplus') {
          $imageHeight = 100;
        } else {
          $imageHeight = 180;
        }
        break;
    }
    return array($imageWidth, $imageHeight);
  }
  
  protected function dynamicMapImageDimensions() {
    list($imageWidth, $imageHeight) = parent::dynamicMapImageDimensions();
    
    switch ($this->pagetype) {
      case 'compliant':
        if ($this->platform == 'bbplus') {
          $imageHeight = 100;
        } else {
          $imageHeight = 180;
        }
        break;
    }
    return array($imageWidth, $imageHeight);
  }
  
  protected function detailURLForBookmark($aBookmark) {
    parse_str($aBookmark, $params);
    if (isset($params['eventId'])) {
      return $this->buildBreadcrumbURL('detail', $params, true);
    }
    
    parent::detailURLForBookmark($aBookmark);
  }

  protected function generateBookmarkOptions($cookieID) {
    if (isset($this->args['eventId'])) {
      // replace location detail page with event detail page
      $cookieParams = array(
        'eventId' => $this->args['eventId'],
        'start' => $this->args['start'],
      );
      $cookieID = http_build_query($cookieParams);
    }
    
    parent::generateBookmarkOptions($cookieID);
  }
  
  protected function getTitleForBookmark($aBookmark) {
    parse_str($aBookmark, $params);
    if (isset($params['eventId'])) {
      $eventInfo = $this->getEventDetails($params['eventId'], self::argVal($params, 'start', time()));
      
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
    $user = $this->getUser('HarvardReunionUser');
    $schedule = new Schedule($user);
    
    $feed = $schedule->getEventFeed();
    $event = $feed->getItem($eventId, $start);
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
          (isset($info['registration']['fee']) ? ' ('.$info['registration']['fee'].')' : '').
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
    if ($this->page == 'detail') {
      $eventId = $this->getArg('eventId');
      $start   = $this->getArg('start', time());

      if ($eventId) {
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
          $cookieID = http_build_query($cookieParams);
          $this->generateBookmarkOptions($cookieID);
        }
      }

    } elseif (isset($this->args['group'])) {

    }
  
    parent::initializeForPage();
  }
}
