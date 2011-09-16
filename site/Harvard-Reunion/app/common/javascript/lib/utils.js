/****************************************************************
 *
 *  Copyright 2011 The President and Fellows of Harvard College
 *  Copyright 2011 Modo Labs Inc.
 *
 *****************************************************************/

function autoupdateContent($id, $url) {
  // cache updates approximately every 20 seconds
  var updateContent = function () {
    var httpRequest = new XMLHttpRequest();
    httpRequest.onreadystatechange = function() {
      if (httpRequest.readyState == 4 && typeof httpRequest.responseText == 'string') {
        document.getElementById($id).innerHTML = httpRequest.responseText;
      }
    };
    httpRequest.open("GET", $url, true);
    httpRequest.send(null);
  }

  setInterval(updateContent, 60000);
}


function validateTextInputForm(textId, message) {
    if(document.getElementById(textId).value=="") {
        alert(message);
        return false;
    }
    return true;
}
