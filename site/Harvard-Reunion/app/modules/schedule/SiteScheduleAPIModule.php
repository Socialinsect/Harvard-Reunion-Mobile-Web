<?php


/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

class SiteScheduleAPIModule extends APIModule {
  protected $id = 'schedule';
  
  private function formatEventResponse($schedule, $event, $brief=false) {
    if ($brief) {
      $info = $schedule->getBriefEventInfo($event);
    } else {
      $info = $schedule->getEventInfo($event);
    }
    
    $info['start']  = $event->get_start();
    $info['end']    = $event->get_end();
    $info['allday'] = $event->get_attribute('datetime') instanceOf DayRange;
    unset($info['datetime']);
    
    return $info;
  }
  
  public function initializeForCommand() {
    $user = $this->getUser('HarvardReunionUser');
    $schedule = new Schedule($user);

    switch ($this->command) {
      case 'categories':
        $responseCategories = array();

        $categories = $schedule->getEventCategories();
        foreach ($categories as $id => $title) {
          $responseCategories[] = array(
            'id'    => $id,
            'title' => $title,
          );
        }

        $response = array(
          'total'        => count($responseCategories),
          'returned'     => count($responseCategories),
          'displayField' => 'title',
          'results'      => $responseCategories,
        );

        $this->setResponse($response);
        $this->setResponseVersion(1);
        break;
        
      case 'defaultCategory':
        $this->setResponse($schedule->getDefaultCategory());
        $this->setResponseVersion(1);
        break;
      
      case 'events':
        $eventResponse = array();
        $category = $this->getArg('category', $schedule->getAllEventsCategory());
        
        $events = $schedule->getEvents($category);

        foreach($events as $event) {
          $eventResponse[] = $this->formatEventResponse($schedule, $event, true);
        }

        $response = array(
          'total'        => count($eventResponse),
          'returned'     => count($eventResponse),
          'displayField' => 'title',
          'results'      => $eventResponse,
        );
        
        $this->setResponse($response);
        $this->setResponseVersion(1);
        break;

      case 'detail':
        $response = array();
      
        $id    = $this->getArg('id');
        $start = $this->getArg('start', time());
        
        $event = $schedule->getEvent($id, $start);
        if (!$event) {
          $error = new KurogoError(
            5,
            'Invalid Request',
            'Invalid id parameter supplied');
          $this->throwError($error);
        }
        
        $response = $this->formatEventResponse($schedule, $event);
        
        $this->setResponse($response);
        $this->setResponseVersion(1);
        break;

      case 'attendees':
        $this->setResponse($schedule->getAllAttendees());
        $this->setResponseVersion(1);
        break;

      default:
        $this->invalidCommand();
        break;
    }
  }
}


