/****************************************************************
 *
 *  Copyright 2011 The President and Fellows of Harvard College
 *  Copyright 2011 Modo Labs Inc.
 *
 *****************************************************************/

function registeredEventAlert() {
  var bookmark = document.getElementById('bookmark');
  if (!hasClass(bookmark, 'on')) {
    alert('Bookmarking this event will only add it to your personal schedule.  You will still need to register for it to attend.');
  }
}
