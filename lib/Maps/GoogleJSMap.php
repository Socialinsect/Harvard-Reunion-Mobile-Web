<?php

class GoogleJSMap extends JavascriptMapImageController {

// http://code.google.com/apis/maps/documentation/javascript/overlays.html

    private $locatesUser = false;

    protected $canAddAnnotations = true;
    protected $canAddPaths = true;
    protected $canAddPolygons = true;
    protected $canAddLayers = true;

    protected $markers = array();
    protected $paths = array();
    protected $polygons = array();

    public function setLocatesUser($locatesUser) {
        $this->locatesUser = ($locatesUser == true);
    }

    ////////////// overlays ///////////////

    public function addAnnotation($latitude, $longitude, $style=null, $title=null)
    {
        $marker = array(
            'lat' => $latitude,
            'lon' => $longitude,
            );

        if ($title) {
            $marker['title'] = $title;
        }

        $this->markers[] = $marker;
    }

    public function addPath($points, $style=null)
    {
        $path = array('coordinates' => $points);
        
        $pathStyle = array();
        if (($color = $style->getStyleForTypeAndParam(MapStyle::LINE, MapStyle::COLOR)) !== null) {
            $pathStyle['strokeColor'] = '"#'.substr($color, 0, 6).'"';
            if (strlen($color) == 8) {
                $alphaHex = substr($color, 6);
                $alpha = hexdec($alphaHex) / 256;
                $pathStyle['strokeOpacity'] = round($alpha, 2);
            }
        }
        if (($weight = $style->getStyleForTypeAndParam(MapStyle::LINE, MapStyle::WEIGHT)) !== null) {
            $pathStyle['strokeWeight'] = $weight;
        }

        $path['style'] = $pathStyle;
        
        $this->paths[] = $path;
    }
    
    public function addPolygon($rings, $style=null)
    {
    	$polygon = array('rings' => $rings);

        $pathStyle = array();
        if (($color = $style->getStyleForTypeAndParam(MapStyle::POLYGON, MapStyle::COLOR)) !== null) {
            $pathStyle['strokeColor'] = '"#'.substr($color, 0, 6).'"';
            if (strlen($color) == 8) {
                $alphaHex = substr($color, 6);
                $alpha = hexdec($alphaHex) / 256;
                $pathStyle['strokeOpacity'] = round($alpha, 2);
            }
        }
        if (($color = $style->getStyleForTypeAndParam(MapStyle::POLYGON, MapStyle::FILLCOLOR)) !== null) {
            $pathStyle['fillColor'] = '"#'.substr($color, 0, 6).'"';
            if (strlen($color) == 8) {
                $alphaHex = substr($color, 6);
                $alpha = hexdec($alphaHex) / 256;
                $pathStyle['fillOpacity'] = round($alpha, 2);
            }
        }
        if (($weight = $style->getStyleForTypeAndParam(MapStyle::POLYGON, MapStyle::WEIGHT)) !== null) {
            $pathStyle['strokeWeight'] = $weight;
        }
        $polygon['style'] = $pathStyle;
        
    	$this->polygons[] = $polygon;
    }

    private static function coordsToGoogleArray($coords) {
        $gCoords = array();
        foreach ($coords as $coord) {
            $gCoords[] .= 'new google.maps.LatLng('.$coord[0].','.$coord[1].')';
        }
        return implode(',', $gCoords);
    }
    
    private function getPolygonJS() {
        $js = "var polypaths;\nvar polygon;";

        foreach ($this->polygons as $polygon) {
            $polyStrings = array();
            foreach ($polygon['rings'] as $ring) {
                $polyString[] = '['.self::coordsToGoogleArray($ring).']';
            }
            $multiPathString = implode(',', $polyString);

            $properties = array('paths: polypaths');
            foreach ($polygon['style'] as $attrib => $value) {
                $properties[] = "$attrib: $value";
            }
            $propString = implode(',', $properties);

            $js .= <<<JS

polypaths = [{$multiPathString}];
polygon = new google.maps.Polygon({{$propString}});
polygon.setMap(map);

JS;
        }

        return $js;
    }

    private function getPathJS() {
        $js = "var coordinates;\nvar path;";
        foreach ($this->paths as $path) {
            $coordString = self::coordsToGoogleArray($path['coordinates']);

            $properties = array('path: coordinates');
            foreach ($path['style'] as $attrib => $value) {
                $properties[] = "$attrib: $value";
            }
            $propString = implode(',', $properties);

            $js .= <<<JS

coordinates = [{$coordString}];
path = new google.maps.Polyline({{$propString}});
path.setMap(map);

JS;

        }
        return $js;
    }

    ////////////// output ///////////////

    // url of script to include in <script src="...
    public function getIncludeScript() {
        return 'http://maps.google.com/maps/api/js?sensor='
             . ($this->locatesUser ? 'true' : 'false');
    }

    public function getHeaderScript() {
        $imageWidth = $this->imageWidth;
    	if (strpos($imageWidth, '%') === FALSE) {
    	    $imageWidth = $imageWidth.'px';
    	}
        $imageHeight = $this->imageHeight;
    	if (strpos($imageHeight, '%') === FALSE) {
    	    // setting height as % won't actually work, but...
    	    $imageHeight = $imageWidth.'px';
    	}

        $script = <<<JS

var map;

function loadMap() {
    var mapImage = document.getElementById("{$this->mapElement}");
    mapImage.style.display = "inline-block";
    mapImage.style.width = "{$imageWidth}";
    mapImage.style.height = "{$imageHeight}";


    var latlng = new google.maps.LatLng({$this->center['lat']}, {$this->center['lon']});
    var options = {
        zoom: {$this->zoomLevel},
        center: latlng,
        mapTypeId: google.maps.MapTypeId.ROADMAP
    };

    map = new google.maps.Map(mapImage, options);
}

JS;

        return $script;
    }

    public function getFooterScript() {

        $script = <<<JS

hideMapTabChildren();
loadMap();

JS;

		if ($this->polygons) {
            $script .= $this->getPolygonJS();
		}

        if ($this->paths) {
            $script .= $this->getPathJS();
        }

        foreach ($this->markers as $index => $marker) {
            $title = 'marker';
            if (isset($marker['title'])) {
                $title = $marker['title'];
            }

            $script .= <<<JS

var marker{$index} = new google.maps.Marker({
    position: new google.maps.LatLng({$marker['lat']},{$marker['lon']}),
    map: map,
    title: "{$title}"
});

JS;
        }

        return $script;
    }

}

