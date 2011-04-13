function initCheckins() {
    setInterval(updateCheckins, 20000);
}

function updateCheckins() {
  var httpRequest = new XMLHttpRequest();
  httpRequest.onreadystatechange = function() {
    if (httpRequest.readyState == 4 && httpRequest.responseText.length) {
      document.getElementById('checkins').innerHTML = httpRequest.responseText;
    }
  };
  httpRequest.open("GET", CHECKIN_CONTENT_URL, true);
  httpRequest.send(null);
}
