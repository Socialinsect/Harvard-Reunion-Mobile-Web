<?php

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
    
        $url = $GLOBALS['siteConfig']->getVar('MAP_SEARCH_URL').'?'.http_build_query($params);
        $rawContent = file_get_contents($url);
        $content = json_decode($rawContent, true);
    
        foreach ($content['items'] as $result) {
            if (strlen($result['bld_num']) && !in_array($result['bld_num'], $bldgIds))
                $bldgIds[] = $result['bld_num'];
        }

        if ($bldgIds) {
            foreach ($bldgIds as $bldgId) {
                $featureInfo = HarvardMapDataController::getBldgDataByNumber($bldgId);
                $feature = new ArcGISFeature($featureInfo['attributes'], $featureInfo['geometry']);
                $feature->setTitleField('Building Name');
                $results[] = $feature;
            }
        }
        return $results;
    }
    
    public function getURLArgsForSearchResult($aResult) {
        $featureIndex = null;
        // sad list of possible fields i got from examining ArcGISLayer::parseData output.
        // don't know if there is a less manual way to get id field
        foreach (array('Building Number', 'Building_HU.BL_ID') as $idField) {
            if ($featureIndex = $aResult->getField($idField)) {
                break;
            }
        }
        return array(
            'featureindex' => $featureIndex,
            'category' => $this->controllerLayerID,
            );
    }
	
    public function getTitleForSearchResult($aResult) {
        // results in this class are ArcGISFeature objects
        // instead of dictionaries containing MapFeature objects
        return $aResult->getTitle();
    }
    
    public function getSubtitleForSearchResult($aResult) {
        return $aResult->getSubtitle();
    }

    public function getLayerForSearchResult($featureID=null) {
        if ($this->searchController == null) {
            $this->searchController = MapDataController::factory(
                $this->searchFeedData['CONTROLLER_CLASS'],
                $this->searchFeedData);
            $this->searchController->setDebugMode($GLOBALS['siteConfig']->getVar('DATA_DEBUG'));
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

        $url = $GLOBALS['siteConfig']->getVar('MAP_SEARCH_URL').'?'.http_build_query($params);
        $rawContent = file_get_contents($url);
        $content = json_decode($rawContent, true);

        foreach ($content['items'] as $resultObj) {
            if (!in_array($resultObj['bld_num'], $bldgIds))
                $bldgIds[] = $resultObj['bld_num'];
        }

        if ($bldgIds) {
            foreach ($bldgIds as $bldgId) {
                $featureInfo = HarvardMapDataController::getBldgDataByNumber($bldgId);
                $feature = new ArcGISFeature($featureInfo['attributes'], $featureInfo['geometry']);
                $feature->setTitleField('Building Name');
                $results[] = $feature;
            }
        }

        return $results;
    }

}
