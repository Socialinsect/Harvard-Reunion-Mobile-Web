function autoupdateContent() {
  // cache updates approximately every 20 seconds
  setInterval(updateContent, 20000);
}

function updateContent() {
  var httpRequest = new XMLHttpRequest();
  httpRequest.onreadystatechange = function() {
    if (httpRequest.readyState == 4) {
      document.getElementById('autoupdateContainer').innerHTML = httpRequest.responseText;
    }
  };
  httpRequest.open("GET", CONTENT_AJAX_URL, true);
  httpRequest.send(null);
}

function validateTextInputForm(textId, message) {
    if(document.getElementById(textId).value=="") {
        alert(message);
        return false;
    }
    return true;
}
