// Initalize the ellipsis event handlers
function initHome() {
    var homeEllipsizer = new ellipsizer();
    
    // cap at 100 divs to avoid overloading phone
    for (var i = 0; i < 100; i++) {
        var elem = document.getElementById('ellipsis_'+i);
        if (!elem) { break; }
        homeEllipsizer.addElement(elem);
    }
    
    setInterval(updateRecentMessage, 5000);
}

function updateRecentMessage() {
  var httpRequest = new XMLHttpRequest();
  httpRequest.onreadystatechange = function() {
    if (httpRequest.readyState == 4) {
      var json = JSON.parse(httpRequest.responseText);
      
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
  return (confirm("Are you sure you want to sign out? Events you've bookmarked in this website may be forgotten.")) 
}

function initMessageList() {
  // twitter cache updates approximately every 20 seconds
  setInterval(updateMessageList, 20000);
}

function updateMessageList() {
  var httpRequest = new XMLHttpRequest();
  httpRequest.onreadystatechange = function() {
    if (httpRequest.readyState == 4) {
      var json = JSON.parse(httpRequest.responseText);
      
      if (json.response && json.response.length) {
        var listContainer = document.getElementById('listContainer');
        var listHeader = document.getElementById('listHeader');
        var listFooter = document.getElementById('listFooter');
        
        var newInnerHTML = '';
        
        if (listHeader) {
          while (listHeader.nodeName != 'LI' && listHeader.nodeName != 'BODY') {
            listHeader = listHeader.parentNode;
          }
          if (listHeader.nodeName == 'LI') {
            newInnerHTML += '<li>'+listHeader.innerHTML+'</li>';
          }
        }
        
        for (var i = 0; i < json.response.length; i++) {
          entry = json.response[i];
          var newItem = document.createElement('li');
          if (newItem) {
            newInnerHTML += '<li>&ldquo;'+(entry.message ? entry.message : '')
              +'&rdquo; <span class="smallprint"> - '+
              entry.author.name+', '+entry.when.delta+'</span></li>';
          }
        }
        if (listFooter) {
          while (listFooter.nodeName != 'LI' && listFooter.nodeName != 'BODY') {
            listFooter = listFooter.parentNode;
          }
          if (listFooter.nodeName == 'LI') {
            newInnerHTML += '<li>'+listFooter.innerHTML+'</li>';
          }
        }

        listContainer.innerHTML = newInnerHTML;
      }
    }
  };
  httpRequest.open("GET", MESSAGE_LIST_AJAX_URL, true);
  httpRequest.send(null);
}
