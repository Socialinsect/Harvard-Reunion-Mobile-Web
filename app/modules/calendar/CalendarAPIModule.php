<?php

includePackage('Calendar');

class CalendarAPIModule extends APIModule
{
    const ERROR_NO_SUCH_EVENT = 50;

    protected $id = 'calendar';
    protected $vmin = 1;
    protected $vmax = 1;

    protected $timezone;
    protected $fieldConfig;

    // from CalendarWebModule
    protected function getFeeds($type) {
        if (isset($this->feeds[$type])) {
            return $this->feeds[$type];
        }

        $feeds = array();
        switch ($type) {
            case 'static':
                $feeds = $this->loadFeedData();
                break;

            case 'user':
            case 'resource':
                $typeController = $type=='user' ? 'UserCalendarListController' :'ResourceListController';
                $sectionData = $this->getModuleSection('calendar_list');
                $listController = isset($sectionData[$typeController]) ? $sectionData[$typeController] : '';
                if (strlen($listController)) {
                    $sectionData = array_merge($sectionData, array('SESSION'=>$this->getSession()));
                    $controller = CalendarListController::factory($listController, $sectionData);
                    switch ($type) {
                        case 'resource':
                            $feeds = $controller->getResources();
                            break;
                        case 'user':
                            $feeds = $controller->getUserCalendars();
                            break;
                    }
                }
                break;
            default:
                throw new Exception("Invalid feed type $type");
        }

        if ($feeds) {
            $this->feeds[$type] = $feeds;
        }

        return $feeds;
    }

    public function getDefaultFeed($type) {
        $feeds = $this->getFeeds($type);
        if ($indexes = array_keys($feeds)) {
            return current($indexes);
        }
    }

    public function getFeed($index, $type) {
        $feeds = $this->getFeeds($type);
        if (isset($feeds[$index])) {
            $feedData = $feeds[$index];
            if (!isset($feedData['CONTROLLER_CLASS'])) {
                $feedData['CONTROLLER_CLASS'] = 'CalendarDataController';
            }
            $controller = CalendarDataController::factory($feedData['CONTROLLER_CLASS'],$feedData);
            $controller->setDebugMode($this->getSiteVar('DATA_DEBUG'));
            return $controller;
        } else {
            throw new Exception("Error getting calendar feed for index $index");
        }
    }

    private function apiArrayFromEvent(ICalEvent $event) {
        foreach ($this->fieldConfig as $aField => $fieldInfo) {
            $fieldName = isset($fieldInfo['label']) ? $fieldInfo['label'] : $aField;
            $attribute = $event->get_attribute($aField);

            if ($attribute) {
                if (isset($fieldInfo['section'])) {
                    $section = $fieldInfo['section'];
                    if (!isset($result[$section])) {
                        $result[$section] = array();
                    }
                    $result[$section][$fieldName] = $attribute;

                } else {
                    $result[$fieldName] = $attribute;
                }
            }
        }

        return $result;
    }



    public function  initializeForCommand() {

        $this->timezone = new DateTimeZone($this->getSiteVar('LOCAL_TIMEZONE'));
        $this->fieldConfig = $this->getAPIConfigData('detail');

        switch ($this->command) {
            case 'calendars':

                $feeds = array();
                foreach (array('static', 'user', 'resource') as $type) {
                    $typeFeeds = $this->getFeeds($type);
                    foreach ($typeFeeds as $feedID=>$feedData) {
                        $feeds[] = array(
                            'id'    => $feedID,
                            'type'  => $type,
                            'title' => $feedData['TITLE'],
                            );
                    }
                }

                $count = count($feeds);
                $response = array(
                    'total' => $count,
                    'returned' => $count,
                    'displayField' => 'title',
                    'results' => $feeds,
                    );

                $this->setResponse($response);
                $this->setResponseVersion(1);

                break;

            case 'events':
            case 'day':
                $type     = $this->getArg('type', 'static');
                // the calendar argument needs to be urlencoded
                $calendar = $this->getArg('calendar', $this->getDefaultFeed($type));
                $current  = $this->getArg('time', time());
                if ($this->command == 'events') {
                    $startTime = $this->getArg('start', $current);
                    $start = new DateTime(date('Y-m-d H:i:s', $startTime), $this->timezone);
                    $endTime = $this->getArg('end', $current);
                    $end = new DateTime(date('Y-m-d H:i:s', $endTime), $this->timezone);

                    } else if ($this->command == 'day') {
                    $start = new DateTime(date('Y-m-d H:i:s', $current), $this->timezone);
                    $start->setTime(0, 0, 0);
                    $end = clone $start;
                    $end->setTime(23, 59, 59);
                }

                $feed = $this->getFeed($calendar, $type);
                $feed->setStartDate($start);
                $feed->setEndDate($end);
                $iCalEvents = $feed->items();

                $events = array();
                $count = 0;

                foreach ($iCalEvents as $iCalEvent) {
                    $events[] = $this->apiArrayFromEvent($iCalEvent);
                    $count++;
                }

                $response = array(
                    'total' => $count,
                    'returned' => $count,
                    'displayField' => 'title',
                    'results' => $events,
                    );

                $this->setResponse($response);
                $this->setResponseVersion(1);

                break;

            case 'detail':
                $eventID = $this->getArg('id', null);
                if (!$eventID) {
                    $error = new KurogoError(
                            5,
                            'Invalid Request',
                            'Invalid id parameter supplied');
                    $this->throwError($error);
                }

                $type = $this->getArg('type', 'static');
                $calendar = $this->getArg('calendar', $this->getDefaultFeed($type));

                $feed = $this->getFeed($calendar, $type);
                $time = $this->getArg('time', time());

                if ($filter = $this->getArg('q')) {
                    $feed->addFilter('search', $filter);
                }

                if ($catid = $this->getArg('catid')) {
                    $feed->addFilter('category', $catid);
                }

                if ($event = $feed->getItem($this->getArg('id'), $time)) {
                    $eventArray = $this->apiArrayFromEvent($event);
                    $this->setResponse($eventArray);
                    $this->setResponseVersion(1);

                } else {
                    $error = new KurogoError(
                            self::ERROR_NO_SUCH_EVENT,
                            'Invalid Request',
                            "The event $eventID cannot be found");
                    $this->throwError($error);
                }

                break;

            case 'search':
                break;

            case 'year':
                $year     = $this->getArg('year', null);
                $type     = $this->getArg('type', 'static');
                $calendar = $this->getArg('calendar', $this->getDefaultFeed($type));
                $month    = $this->getArg('month', 1); // default to january

                if (!$year) {
                    $year = date('m') < $month ? date('Y') - 1 : date('Y');
                }

                $start = new DateTime(sprintf("%d%02d01", $year, $month), $this->timezone);
                $end   = new DateTime(sprintf("%d%02d01", $year+1, $month), $this->timezone);

                $feed = $this->getFeed($calendar, $type);
                $feed->setStartDate($start);
                $feed->setEndDate($end);
                $feed->addFilter('year', $year);
                $iCalEvents = $feed->items();

                $count = 0;
                foreach ($iCalEvents as $iCalEvent) {
                    $events[] = $this->apiArrayFromEvent($iCalEvent);
                    $count++;
                }

                $response = array(
                    'total' => $count,
                    'returned' => $count,
                    'displayField' => 'title',
                    'results' => $events,
                    );

                $this->setResponse($response);
                $this->setResponseVersion(1);


                break;

            case 'resources':
                $resources = array();
                $resourceFeeds = $this->getFeeds('resource');
                if ($resourceFeeds) {
                    foreach ($resourceFeeds as $calendar=>$resource) {
                        $feed = $this->getFeed($calendar, 'resource');
                        $availability = 'Available';
                        if ($event = $feed->getNextEvent()) {
                            $now = time();
                            if ($event->overlaps(new TimeRange($now, $now))) {
                                $availability = 'In use';
                            } elseif ($event->overlaps(new TimeRange($now + 900, $now + 1800))) {
                                $availability = 'In use at ' . $this->timeText($event, true);
                            }
                        }

                        $resources[$calendar] = array(
                            'title'        => $resource['TITLE'],
                            'availability' => $availability,
                            'type'         => 'resource',
                            'calendar'     => $calendar,
                            );
                    }
                }

                $response = array(
                    'total' => count($resources),
                    'returned' => count($resources),
                    'displayField' => 'title',
                    'results' => $resources,
                    );

                $this->setResponse($response);
                $this->setResponseVersion(1);


                break;

            case 'user':
                break;

            case 'categories':
                //break;

            case 'category':
                //break;

            default:
                $this->invalidCommand();
                break;
        }
    }

}


