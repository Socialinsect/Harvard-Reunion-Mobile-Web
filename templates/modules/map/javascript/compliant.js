var loadedImages = {};
var centerZoomBased;
var staticMapOptions;
var mapWidth;
var mapHeight;

function hideMapTabChildren() {
    var mapTab = document.getElementById("mapTab");
    for (var i = 0; i < mapTab.childNodes.length; i++) {
        var node = mapTab.childNodes[i];
        if (node.className == "image") {
            mapTab.removeChild(node);
            break;
        }
    }
    for (var i = 0; i < mapTab.childNodes.length; i++) {
        var node = mapTab.childNodes[i];
        if (node.className == "scrollers") {
            mapTab.removeChild(node);
            break;
        }
    }
}

function loadImage(imageURL,imageID) {
    if (!loadedImages[imageID]) {
        // Loads an image from the given URL into the image with the specified ID
        var img = document.getElementById(imageID);
        if (img) {
            if (imageURL != "") {
                img.src = imageURL;
            } else {
                img.src = "/common/images/blank.png";
            }
        }
        loadedImages[imageID] = true;
    }
}

function addStaticMapControls() {
    if (!staticMapOptions) {
        return;
    }

    centerZoomBased = ("center" in staticMapOptions);
    
    var initCenter;
    var initZoom;
    var initBBox;
    
    if (centerZoomBased) {
        initCenter = staticMapOptions['center'];
        initZoom = staticMapOptions['zoom'];
    } else {
        initBBox = staticMapOptions['bbox'];
    }
    
    var mapImage = document.getElementById("staticmapimage");

    var zoomIn = document.getElementById("zoomin");
    zoomIn.onclick = function() {
        if (centerZoomBased) {
            staticMapOptions['zoom'] = parseInt(staticMapOptions['zoom']) + 1;
        } else {
            var bbox = staticMapOptions['bbox'];
            var dLat = bbox['ymax'] - bbox['ymin'];
            var dLon = bbox['xmax'] - bbox['xmin']
            bbox['ymax'] = bbox['ymax'] - dLat / 4;
            bbox['xmax'] = bbox['xmax'] - dLon / 4;
            bbox['ymin'] = bbox['ymin'] + dLat / 4;
            bbox['xmin'] = bbox['xmin'] + dLon / 4;
            staticMapOptions['bbox'] = bbox;
        }
        mapImage.src = constructMapURL();
    }
    
    var zoomOut = document.getElementById("zoomout");
    zoomOut.onclick = function() {
        if (centerZoomBased) {
            staticMapOptions['zoom'] = parseInt(staticMapOptions['zoom']) - 1;
        } else {
            var bbox = staticMapOptions['bbox'];
            var dLat = bbox['ymax'] - bbox['ymin'];
            var dLon = bbox['xmax'] - bbox['xmin']
            bbox['ymax'] = bbox['ymax'] + dLat / 2;
            bbox['xmax'] = bbox['xmax'] + dLon / 2;
            bbox['ymin'] = bbox['ymin'] - dLat / 2;
            bbox['xmin'] = bbox['xmin'] - dLon / 2;
            staticMapOptions['bbox'] = bbox;
        }
        mapImage.src = constructMapURL();
    }
    
    var recenter = document.getElementById("recenter");
    recenter.onclick = function() {
        if (centerZoomBased) {
            staticMapOptions['center'] = initCenter;
            staticMapOptions['zoom'] = initZoom;
        } else {
            staticMapOptions['bbox'] = initBBox;
        }
        mapImage.src = constructMapURL();
    }
}

function constructMapURL() {
    var baseURL = staticMapOptions['baseURL'];
    var dimensionFunction = eval(staticMapOptions['dimensionFunction']);
    var dimensionString = dimensionFunction(mapWidth, mapHeight);
    
    if (centerZoomBased) {
        var centerCoord = staticMapOptions['center']['lat'] + "," + staticMapOptions['center']['lon'];
        return baseURL + "&center=" + centerCoord + "&zoom=" + staticMapOptions['zoom'] + dimensionString;
    } else { // bbox-based maps
        var bbox = staticMapOptions['bbox'];
        var bboxStr = bbox['xmin'] + "," + bbox['ymin'] + "," + bbox['xmax'] + "," + bbox['ymax'] + dimensionString;
        return baseURL + "&bbox=" + bboxStr;
    }
}

function updateMapDimensions() {
    
    if (!centerZoomBased) {
        // if width and height proprotions changed, we need to update the bbox
        var oldHeight = mapHeight;
        var oldWidth = mapWidth;
    }
    
    if (window.innerHeight !== undefined) {
        mapHeight = window.innerHeight;
    } else {
        mapHeight = document.documentElement.clientHeight; // ie7
    }

    if (window.innerWidth !== undefined) {
        mapWidth = window.innerWidth;
    } else {
        mapWidth = document.documentElement.clientWidth; // ie7
    }

    if (!centerZoomBased) {
        // if width and height changed, we need to update the bbox
        if (oldWidth != mapWidth || oldHeight != mapHeight) {
            var bbox = staticMapOptions['bbox'];
            var bboxWidth = bbox['xmax'] - bbox['xmin'];
            var bboxHeight = bbox['ymax'] - bbox['ymin'];

            var newBBoxWidth = bboxWidth * mapWidth / oldWidth;
            var newBBoxHeight = bboxHeight * mapHeight / oldHeight;
            
            var dWidth = (newBBoxWidth - bboxWidth) / 2;
            var dHeight = (newBBoxHeight - bboxHeight) / 2;
            
            bbox['xmax'] += dWidth;
            bbox['xmin'] -= dWidth;
            bbox['ymax'] += dHeight;
            bbox['ymin'] -= dHeight;
            
            staticMapOptions['bbox'] = bbox;
        }
    }
    
    document.getElementById("staticmapimage").src = constructMapURL();
}

// north and east are sign arguments, e.g.:
// northeast is (1, 1)
// northwest is (1, -1)
// south is (-1, 0)
function scroll(north, east) {

    if (centerZoomBased) {
        var zoom = staticMapOptions['zoom'];
        var lat = staticMapOptions['center']['lat'];
        var lon = staticMapOptions['center']['lon'];
        var degreesCovered = 360 / Math.pow(2, parseInt(zoom) + 1);

        lat = parseFloat(lat) + degreesCovered * north;
        lon = parseFloat(lon) + degreesCovered * east;

        // round to 4 decimal places (roughly 10 meters)
        staticMapOptions['center']['lat'] = Math.round(lat * 10000) / 10000;
        staticMapOptions['center']['lon'] = Math.round(lon * 10000) / 10000;

    } else {
        var bbox = staticMapOptions['bbox'];
        var dLat = (bbox['ymax'] - bbox['ymin']) / 2;
        var dLon = (bbox['xmax'] - bbox['xmin']) / 2;
        bbox['ymax'] = bbox['ymax'] + dLat * north;
        bbox['ymin'] = bbox['ymin'] + dLat * north;
        bbox['xmax'] = bbox['xmax'] + dLon * east;
        bbox['xmin'] = bbox['xmin'] + dLon * east;
        staticMapOptions['bbox'] = bbox;
    }
    document.getElementById("staticmapimage").src = constructMapURL();
}

/*
function disable(strID) {
// Visually dims and disables the anchor whose id is strID
	var objA = document.getElementById(strID);
	if(objA) {
		if(objA.className.indexOf("disabled") == -1) { // only disable if it's not already disabled!
			objA.className = objA.className + " disabled";
		}
	}
}

function enable(strID) {
// Visually undims and re-enables the anchor whose id is strID
	var objA = document.getElementById(strID);
	if(objA) {
		objA.className = objA.className.replace("disabled","");
	}
}

function cancelOptions(strFormID) {
// Should cancel map-option changes and hide the form; this is just a stub for future real function
	var objForm = document.getElementById(strFormID);
	if(objForm) { objForm.reset() }
	hide("options"); 
}
*/

