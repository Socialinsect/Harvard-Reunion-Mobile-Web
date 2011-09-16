<?php

/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

require_once realpath(LIB_DIR.'/Maps/ArcGISParser.php');

class HarvardMapSearch extends MapSearch {

    private $searchFeedData;
    private $searchController;
    private $controllerLayerID;
    
    public function setFeedData($feeds) {
        foreach ($feeds as $id => $feed) {
            if ($feed['TITLE'] == 'Search Results') {
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

        $params = array(
            'str' => $query,
            'fmt' => 'json',
            );
    
        $url = Kurogo::getSiteVar('MAP_SEARCH_URL').'?'.http_build_query($params, null, '&');
        $rawContent = file_get_contents($url);
        $content = json_decode($rawContent, true);
    
        foreach ($content['items'] as $result) {
            if (strlen($result['bld_num']) && !in_array($result['bld_num'], $bldgIds))
                $bldgIds[] = $result['bld_num'];
        }

        if ($bldgIds) {
            foreach ($bldgIds as $bldgId) {
                $featureInfo = HarvardMapDataController::getBldgDataByNumber($bldgId);
                
                if ($featureInfo && $featureInfo['attributes']) {
                    // we've set up HarvardMapDataController to expect building ID's
                    // if the data source is the search layer, which isn't consistent
                    // with the other layers but...
                    $feature = new ArcGISFeature(
                        $featureInfo['attributes'],
                        $featureInfo['geometry'],
                        $bldgId,
                        $this->controllerLayerID);
                    $feature->setTitleField('Building Name');
                    // TODO find a better place to set this attribute
                    if (isset($featureInfo['geometryType'])) {
                        $feature->setGeometryType($featureInfo['geometryType']);
                    }
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
            $this->searchController->setDebugMode(Kurogo::getSiteVar('DATA_DEBUG'));
        }
    	return $this->searchController;
    }

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

}
