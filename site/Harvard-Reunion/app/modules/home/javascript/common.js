/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

// Initalize the ellipsis event handlers
function initHome() {
    var homeEllipsizer = new ellipsizer();
    
    // cap at 100 divs to avoid overloading phone
    for (var i = 0; i < 100; i++) {
        var elem = document.getElementById('ellipsis_'+i);
        if (!elem) { break; }
        homeEllipsizer.addElement(elem);
    }
    
    setInterval(updateRecentMessage, 20000);
}

function updateRecentMessage() {
  var httpRequest = new XMLHttpRequest();
  httpRequest.onreadystatechange = function() {
    if (httpRequest.readyState == 4) {
      var json = window.JSON ? 
        JSON.parse(httpRequest.responseText) : 
        (new Function("return "+httpRequest.responseText))();
      
      if (json.response && json.response.message) {
        document.getElementById('recentMessage').innerHTML = json.response.message;
        document.getElementById('recentAuthor' ).innerHTML = json.response.author;
        document.getElementById('recentAge'    ).innerHTML = json.response.age;
        
        document.getElementById('recentContainer').className = 'recent '+ json.response.type;
      }
    }
  };
  httpRequest.open("GET", RECENT_MESSAGE_AJAX_URL, true);
  httpRequest.send(null);
}

function confirmLogout() {
  return confirm("Are you sure you want to sign out?");
}
