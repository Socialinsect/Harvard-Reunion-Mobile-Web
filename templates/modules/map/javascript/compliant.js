var loadedImages = {};
var centerZoomBased;
var staticMapOptions;

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
    
    var mapImage = document.getElementById("mapimage");

    var zoomIn = document.getElementById("zoomin");
    zoomIn.onclick = function() {
        if (centerZoomBased) {
            staticMapOptions['zoom'] = staticMapOptions['zoom'] + 1;
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
            staticMapOptions['zoom'] = staticMapOptions['zoom'] - 1;
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
    if (centerZoomBased) {
        var centerCoord = staticMapOptions['center']['lat'] + "," + staticMapOptions['center']['lon'];
        return baseURL + "&center=" + centerCoord + "&zoom=" +  staticMapOptions['zoom']
    } else { // bbox-based maps
        var bbox = staticMapOptions['bbox'];
        var bboxStr = bbox['xmin'] + "," + bbox['ymin'] + "," + bbox['xmax'] + "," + bbox['ymax'];
        return baseURL + "&bbox=" + bboxStr;
    }
}

function loadImage(imageURL,imageID) {
  if (!loadedImages[imageID]) {
    // Loads an image from the given URL into the image with the specified ID
    var img = document.getElementById(imageID);
    if(img) {
      if(imageURL != "") {
        img.src = imageURL;
      } else {
        img.src = "/common/images/blank.png";
      }
    }
    loadedImages[imageID] = true;
  }
}

/*
function rotateMap() {
// Load a rotated map image
	var objMap = document.getElementById("mapimage");
	var objContainer = document.getElementById("container");
	var objScrollers = document.getElementById("mapscrollers");
	if(objMap) {
		show("loadingimage");
		mapW = window.innerWidth;
		mapH = window.innerHeight;
		var bboxW = mapBoxE - mapBoxW;
		var bboxH = mapBoxN - mapBoxS;
		if (mapH / mapW != bboxH / bboxW) { // need taller image
			var newBBoxH = mapH * bboxW / mapW;
			mapBoxN = mapBoxN + (newBBoxH - bboxH) / 2;
			mapBoxS = mapBoxS - (newBBoxH - bboxH) / 2;
		}

		loadImage(getMapURL(mapBaseURL),'mapimage'); 
	}
	if(objContainer) {
		objContainer.style.width=mapW+"px";
		objContainer.style.height=mapH+"px";
		objMap.style.width=mapW+"px";
		objMap.style.height=mapH+"px";
	}
	if(objScrollers) {
		switch(window.orientation)
		{
			case 0:
			case 180:
				objScrollers.style.height=(mapH-42)+"px";
			break;
	
			case -90:
			case 90:
				objScrollers.style.height=mapH+"px";
			break;
	
		}
	}
}

function rotateMapAlternate() {
// Load a rotated map image - needs work to get innerWidth and innerHeight working correctly -- will be required once firmware 2.0 is released enabling full-screen chromeless browsing
	var objMap = document.getElementById("mapimage");
	if(objMap) {
		show("loadingimage");
		mapW = window.innerWidth;
		mapH = window.innerHeight;
		loadImage(getMapURL(mapBaseURL),'mapimage'); 
	}
}

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

