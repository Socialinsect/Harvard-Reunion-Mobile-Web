<?php

/****************************************************************
 *
 *  Copyright 2010-2012 The President and Fellows of Harvard College
 *  Copyright 2010-2012 Modo Labs Inc.
 *
 *****************************************************************/

require_once realpath(LIB_DIR.'/Maps/ArcGISParser.php');

class HarvardMapSearch extends MapSearch {

    private $searchFeedData;
    private $searchController;
    private $controllerLayerID;
    
    public function setFeedData($feeds) {
        foreach ($feeds as $id => $feed) {
            if ($feed['SEARCHABLE']) {
                $this->searchFeedData = $feed;
                $this->controllerLayerID = $id;
                break;
            }
        }
        parent::setFeedData($feeds);
    }
    
    public function searchByProximity($center, $tolerance=1000, $maxItems=0) {
        $searchController = $this->getLayerForSearchResult();
        $searchController->addFilter('outFields', 'Building_HU.BL_ID'); // see comment in getURLArgs...
        return $searchController->searchByProximity($center, $tolerance, $maxItems);
    }

    public function searchCampusMap($query) {
        $results = array();
        $bldgIds = array();
        $minusBldgIds = array(); // keep track of dupes
        $searchController = $this->getLayerForSearchResult();

        $params = array(
            'str' => $query,
            'fmt' => 'json',
            );
    
        $url = Kurogo::getSiteVar('MAP_SEARCH_URL').'?'.http_build_query($params, null, '&');
        $rawContent = file_get_contents($url);
        $content = json_decode($rawContent, true);

        foreach ($content['items'] as $result) {
            if (trim($result['bld_num'])) {
                if (!in_array($result['bld_num'], $bldgIds))
                    $bldgIds[] = $result['bld_num'];
            } else {
                // search result doesn't have a building number
                $searchText = strtoupper(trim($result['match_string']));
                $features = $searchController->search($searchText);
                foreach ($features as $feature) {
                    $feature->addCategoryId($searchController->getCategoryId());
                    $minusBldgIds[] = $feature->getId();
                    $results[] = $feature;
                }
            }
        }

        if ($bldgIds) {
            foreach ($bldgIds as $bldgId) {
                if (in_array($bldgId, $minusBldgIds)) {
                    continue;
                }
                $feature = $searchController->getBldgDataByNumber($bldgId);
                if ($feature) {
                    $feature->addCategoryId($searchController->getCategoryId());
                    $results[] = $feature;
                }
            }
        }
        
        return $results;
    }

    public function getLayerForSearchResult($featureID=null) {
        if ($this->searchController == null) {
            $this->searchController = MapDataController::factory(
                $this->searchFeedData['CONTROLLER_CLASS'],
                $this->searchFeedData);
            //$this->searchController->setCategory(array($this->controllerLayerID));
            $this->searchController->setDebugMode(Kurogo::getSiteVar('DATA_DEBUG'));
        }
    	return $this->searchController;
    }

    /*
    // search for courses
    public function searchCampusMapForCourseLoc($query) {

        $results = array();
        $bldgIds = array();

        $params = array(
            'str' => $query,
            'loc' => 'course',
            );

        $url = Kurogo::getSiteVar('MAP_SEARCH_URL').'?'.http_build_query($params, null, '&');
        $rawContent = file_get_contents($url);
        $content = json_decode($rawContent, true);

        foreach ($content['items'] as $resultObj) {
            if (!in_array($resultObj['bld_num'], $bldgIds))
                $bldgIds[] = $resultObj['bld_num'];
        }

        if ($bldgIds) {
            foreach ($bldgIds as $bldgId) {
                $featureInfo = HarvardMapDataController::getBldgDataByNumber($bldgId);
                $feature = new ArcGISFeature(
                    $featureInfo['attributes'],
                    $featureInfo['geometry'],
                    $bldgId,
                    $this->controllerLayerID);
                $feature->setTitleField('Building Name');
                $results[] = $feature;
            }
        }

        return $results;
    }
    */
}
