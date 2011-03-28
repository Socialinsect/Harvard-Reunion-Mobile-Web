function facebookCheckin() {
  if (typeof navigator.geolocation != 'undefined') {
    navigator.geolocation.getCurrentPosition(function (location) {
      var latitude  = location.coords.latitude;
      var longitude = location.coords.longitude;
      
      if (latitude && longitude) {
        window.location = FACEBOOK_CHECKIN_URL+'&latitude='+latitude+'&longitude='+longitude;
      }
    });
    return false;
  }
  return true;
}
