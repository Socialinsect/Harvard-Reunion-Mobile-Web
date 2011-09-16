<?php

/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

class SiteMapAPIModule extends MapAPIModule
{
    private $outputProjector;
    private $inputProjector;

    private $fieldConfig;
    private $photoServer;

    protected function arrayFromMapFeature(MapFeature $feature) {
        // needed for search, places, and detail APIs.
        // for now the iphone will only handle lat/lon
        if (!$this->outputProjector || !$this->inputProjector) {
            $dataProjection = $this->dataController->getProjection();
            if ($dataProjection != GEOGRAPHIC_PROJECTION) {
                $this->outputProjector = new MapProjector();
                $this->outputProjector->setSrcProj($dataProjection);
    
                $this->inputProjector = new MapProjector();
                $this->inputProjector->setDstProj($dataProjection);
            }
        }
        
        $category = $feature->getCategory();
        if (!is_array($category)) {
            $category = explode(MAP_CATEGORY_DELIMITER, $category);
        }

        $info = $feature->getDescription();

        if (!isset($this->fieldConfig)) {
            $this->fieldConfig = $this->getAPIConfig('detail');
        }

        if (!isset($this->photoServer)) {
            $this->photoServer = $this->getOptionalModuleVar('MAP_PHOTO_SERVER');
        }

        $suppress = $this->fieldConfig->getVar('suppress', 'details');
        $photofields = $this->fieldConfig->getVar('photofields', 'details');

        $filteredInfo = array();
        $photoURL = null;
        if (is_array($info)) {
            foreach ($info as $infoDict) {
                if (in_array($infoDict['label'], $photofields)) {
                    $photoFile = $infoDict['title'];
                    if ($photoFile && $photoFile != 'Null') {
                        $photoFile = str_replace(' ', '%20', $photoFile);
                        $photoURL = $this->photoServer.$photoFile;
                    }
                    
                } else if (!in_array($infoDict['label'], $suppress)) {
                    $filteredInfo[] = $infoDict;
                }
            }
        }
        $result = array(
            'title' => $feature->getTitle(),
            'subtitle' => $feature->getSubtitle(),
            'id' => $feature->getIndex(),
            'category' => $category,
            'description' => $filteredInfo,
            );

        if ($photoURL) {
            $result['photo'] = $photoURL;
        }

        $geometry = $feature->getGeometry();
        if ($geometry) {
            if (isset($this->outputProjector)) {
                $geometry = $this->outputProjector->projectGeometry($geometry);
            }

            $center = $geometry->getCenterCoordinate();
            if ($geometry instanceof MapPolygon) {
                $serializedGeometry = $geometry->getRings();
                $result['geometryType'] = 'polygon';
            } elseif ($geometry instanceof MapPolyline) {
                $serializedGeometry = $geometry->getPoints();
                $result['geometryType'] = 'polyline';
            } elseif ($geometry) {
                $serializedGeometry = $geometry->getCenterCoordinate();
                $result['geometryType'] = 'point';
            }
            $result['geometry'] = $serializedGeometry;
            $result['lat'] = $center['lat'];
            $result['lon'] = $center['lon'];
        }

        return $result;
    }
    
    protected function arrayFromEvent($schedule, $event) {
      $result = array();
      
      $info = $schedule->getEventInfo($event);
      if ($info['location'] && !self::argVal($info['location'], 'multiple', false) &&
          (isset($info['location']['latlon']) || isset($info['location']['building']))) {
        
          // get map feature associated with event location
          $feature = null;
          if (isset($info['location']['building'])) {
              $feature = $this->dataController->getFeature(
                  $info['location']['building'], $this->getCategoriesAsArray());
              $result = $this->arrayFromMapFeature($feature);
              
          } else if (isset($info['location']['latlon'])) {
              $result = array(
                  'title' => '',
                  'subtitle' => '',
                  'id' => '',
                  'category' => array(),
                  'description' => array(),
                  'geometryType' => 'point',
                  'geometry' => array(
                      'lat' => $info['location']['latlon'][0],
                      'lon' => $info['location']['latlon'][1],
                  ),
                  'lat' => $info['location']['latlon'][0],
                  'lon' => $info['location']['latlon'][1],
              );
          }

          // overwrite fields with event details
          $result['id']       = $info['id'];
          $result['start']    = $event->get_start();
          $result['title']    = $info['title'];
          $result['category'] = array('event');
  
          $result['subtitle'] = self::argVal($info['location'], 'title', '');
          if (strtoupper($result['subtitle']) == 'TBA' && isset($info['location']['address'])) {
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
                  $result['subtitle'] = implode(', ', $parts);
              }
          }
      }
    
      return $result;
    }

    protected static function placeTitleSort($a, $b) {
        return strcasecmp(self::argVal($a, 'title', ''), self::argVal($b, 'title', ''));
    }

    public function initializeForCommand() {

        $this->feedGroups = $this->getFeedGroups();
        $this->numGroups = count($this->feedGroups);

        if ($this->command == 'search') {

            // Figure out the full Harvard campus category
            if (!$this->feeds) {
                $this->feeds = $this->loadFeedData();
            }
            foreach ($this->feeds as $id => $feed) {
                if ($feed['TITLE'] == 'Search Results') {
                    $this->args['category'] = $id;
                    break;
                }
            }
            $categoryPath = $this->getCategoriesAsArray();
            $this->dataController = $this->getDataController($categoryPath, $listItemPath);

            $mapSearchClass = $this->getOptionalModuleVar('MAP_SEARCH_CLASS', 'MapSearch');
            if (!$this->feeds)
                $this->feeds = $this->loadFeedData();
            $mapSearch = new $mapSearchClass($this->feeds);

            $searchType = $this->getArg('type', '');
            switch ($searchType) {
                case 'building':
                    $identifier = $this->getArg('identifier');
                    if ($identifier) {
                        $feature = $this->dataController->getFeature($identifier, $categoryPath);

                        $response = array(
                            'total' => 1,
                            'returned' => 1,
                            'displayField' => 'title',
                            'results' => array($this->arrayFromMapFeature($feature)),
                            );

                        $this->setResponse($response);
                        $this->setResponseVersion(1);

                    } else {
                        // TODO return a more informative error
                        $this->invalidCommand();
                    }

                    break;
                
                case 'nearby':
                    $lat = $this->getArg('lat', 0);
                    $lon = $this->getArg('lon', 0);

                    $center = array('lat' => $lat, 'lon' => $lon);
                    if ($this->inputProjector) {
                        $center = $this->inputProjector->projectPoint($center);
                    }

                    $searchResults = $mapSearch->searchByProximity($center, 1000, 10);
                    $places = array();
                    $places = array();
                    foreach ($searchResults as $result) {
                        $places[] = $this->arrayFromMapFeature($result);
                    }

                    $response = array(
                        'total' => count($places),
                        'returned' => count($places),
                        'displayField' => 'title',
                        'results' => $places,
                        );

                    $this->setResponse($response);
                    $this->setResponseVersion(1);

                    break;
                
                default:
                    $searchTerms = $this->getArg('q');
                    if ($searchTerms) {
                        $searchResults = $mapSearch->searchCampusMap($searchTerms);

                        $places = array();
                        foreach ($searchResults as $result) {
                            $places[] = $this->arrayFromMapFeature($result);
                        }

                        $user = $this->getUser('HarvardReunionUser');
                        $schedule = new Schedule($user);

                        $events = $schedule->searchEvents($searchTerms);
                        foreach ($events as $event) {
                          $eventPlace = $this->arrayFromEvent($schedule, $event);
                          if ($eventPlace) {
                            $places[] = $eventPlace;
                          }
                        }
                        
                        usort($places, array(get_class($this), 'placeTitleSort'));

                        $response = array(
                            'total' => count($places),
                            'returned' => count($places),
                            'displayField' => 'title',
                            'results' => $places,
                            );

                        $this->setResponse($response);
                        $this->setResponseVersion(1);

                    } else {
                        // TODO return a more informative error
                        $this->invalidCommand();
                    }
                    break;
            }

        } else if ($this->command == 'places') {
            $categoryPath = $this->getCategoriesAsArray();
            if ($categoryPath) {
                $this->dataController = $this->getDataController($categoryPath, $listItemPath);


                $listItems = $this->dataController->getListItems($listItemPath);
                $places = array();
                foreach ($listItems as $listItem) {
                    if ($listItem instanceof MapFeature) {
                        $aPlace = $this->arrayFromMapFeature($listItem);
                        $aPlace['category'] = $categoryPath;
                        $places[] = $aPlace;
                    }
                }

                $response = array(
                    'total' => count($places),
                    'returned' => count($places),
                    'displayField' => 'title',
                    'results' => $places,
                    );

                $this->setResponse($response);
                $this->setResponseVersion(1);
            }

            parent::initializeForCommand();

        } else {

            parent::initializeForCommand();
        }
    }
}
