<?php

/****************************************************************
 *
 *  Copyright 2010-2012 The President and Fellows of Harvard College
 *  Copyright 2010-2012 Modo Labs Inc.
 *
 *****************************************************************/

class HarvardMapDataController extends ArcGISDataController
{
    protected $returnsGeometry = false;
    protected $isSearchLayer = false;

    // set the cache lifetime to a month since we are
    // pulling in multiple feeds for searches
    protected $cacheLifetime = 2592000;

    protected function init($args)
    {
        parent::init($args);
        //if (isset($args['DYNAMIC_ZOOM_LEVEL']))
        //    $this->dynamicZoomLevel = $args['DYNAMIC_ZOOM_LEVEL'];
        
        //if (isset($args['ARCGIS_LAYER_ID']))
        //    $this->parser->setDefaultLayer($args['ARCGIS_LAYER_ID']);

        if (isset($args['RETURNS_GEOMETRY']))
            $this->returnsGeometry = $args['RETURNS_GEOMETRY'];
    }

    protected function getProjectedFeature(Placemark $placemark)
    {
        if (!$this->returnsGeometry || $placemark->getGeometry() == null) {
            $featureInfo = $this->queryFeatureServer($placemark);
            $placemark->setGeometryType($featureInfo['geometryType']);
            $placemark->readGeometry($featureInfo['geometry']);
        }

        $placemark = parent::getProjectedFeature($placemark);

        if ($placemark->getField('PhotoURL') === null) {
            if (!isset($featureInfo))
                $featureInfo = $this->queryFeatureServer($placemark);

            $photoURL = self::getPhotoFromFeatureInfo($featureInfo);
            if ($photoURL) {
                $placemark->setField('PhotoURL', $photoURL);
            }
        }

        return $placemark;
    }
    
    private static function getSupplementaryFeatureData($bldgId, $searchField, $queryBase, $layerId=0) {
        $directory = Kurogo::getSiteVar('ARCGIS_FEATURE_CACHE') .'/'. crc32($queryBase);
        $featureCache = new DiskCache($directory, 86400*7, true);
        if (!$featureCache->isFresh($bldgId)) {
            $query = http_build_query(array(
                'searchText'     => $bldgId,
                'searchFields'   => $searchField,
                'contains'       => 'false',
                'sr'             => '',
                'layers'         => $layerId,
                'returnGeometry' => 'true',
                'f'              => 'json',
                ), null, '&');
            $json = file_get_contents($queryBase . '/find?' . $query);
            $jsonObj = json_decode($json, true);
        
            if (isset($jsonObj['results']) && count($jsonObj['results'])) {
                $result = $jsonObj['results'][0];
                $featureCache->write($result, $bldgId);
            } else {
                error_log("could not find building $bldgId", 0);
            }
        }
        $result = $featureCache->read($bldgId);
        $photoURL = self::getPhotoFromFeatureInfo($result);
        if ($photoURL) {
            $result['attributes']['PhotoURL'] = $photoURL;
        }
        return $result;
    }

    private static function getPhotoFromFeatureInfo($featureInfo) {
        $result = null;
        $photoFields = array('PHOTO_FILE', 'Photo', 'Photo File');
        foreach ($photoFields as $field) {
            if (isset($featureInfo['attributes'][$field]) && 
                    preg_match('/\.(png|png8|jpg|jpeg|gif)$/', $featureInfo['attributes'][$field])) {
                $result = Kurogo::getSiteVar('MAP_PHOTO_SERVER').rawurlencode($featureInfo['attributes'][$field]);
                break;
            }
        }
        return $result;
    }
    
    public function getFeatureByField($searchField, $value) {
        if (!$this->returnsGeometry) {
            $queryBase = Kurogo::getSiteVar('ARCGIS_FEATURE_SERVER');
        } else {
            $queryBase = $this->baseURL;
        }
        $this->initializeParser();
        $this->initializeLayers();
        $featureInfo = self::getSupplementaryFeatureData($value, $searchField, $queryBase);
        $feature = $this->parser->featureFromJSON($featureInfo);
        return $feature;
    }

    private function queryFeatureServer($feature) {
        if (!$this->returnsGeometry) {
            $queryBase = Kurogo::getSiteVar('ARCGIS_FEATURE_SERVER');
        } else {
            $queryBase = $this->baseURL;
        }

        $searchFieldCandidates = array(
            'Building Number', 'Building Name', 'Building', 'Building_HU.BL_ID');
        foreach ($searchFieldCandidates as $field) {
            $searchField = $field;
            $bldgId = $feature->getField($field);
            if ($bldgId) {
                break;
            }
        }
        return self::getSupplementaryFeatureData($bldgId, $searchField, $queryBase, $this->parser->getSelectedLayerId());
    }
    
    public function getBldgDataByNumber($bldgId) {
        $this->selectPlacemark($bldgId);
        $feature = $this->getSelectedPlacemark();
        if ($feature) {
            if ($feature->getField('PhotoURL') === null) {
                //$featureInfo = $controller->queryFeatureServer($feature);
                $featureInfo = $this->queryFeatureServer($feature);
                $photoURL = self::getPhotoFromFeatureInfo($featureInfo);
                if ($photoURL) {
                    $feature->setField('PhotoURL', $photoURL);
                }
            }
        }
        return $feature;
    }
    
    public function setIsSearchLayer($isSearchLayer) {
        $this->isSearchLayer = $isSearchlayer;
    }

}
