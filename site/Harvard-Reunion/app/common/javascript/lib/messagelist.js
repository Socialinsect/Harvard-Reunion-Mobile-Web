function initMessageList() {
  // cache updates approximately every 20 seconds
  setInterval(updateMessageList, 20000);
}

function updateMessageList() {
  var httpRequest = new XMLHttpRequest();
  httpRequest.onreadystatechange = function() {
    if (httpRequest.readyState == 4) {
      var json = JSON.parse(httpRequest.responseText);
      
      if (json.response && json.response.length) {
        var listContainer = document.getElementById('listContainer');
        
        var newInnerHTML = '';
        
        for (var i = 0; i < json.response.length; i++) {
          entry = json.response[i];
          var newItem = document.createElement('li');
          if (newItem) {
            newInnerHTML += '<li>&ldquo;'+(entry.message ? entry.message : '')
              +'&rdquo; <span class="smallprint"> - '+
              entry.author.name+', '+entry.when.delta+'</span></li>';
          }
        }

        listContainer.innerHTML = newInnerHTML;
      }
    }
  };
  httpRequest.open("GET", MESSAGE_LIST_AJAX_URL, true);
  httpRequest.send(null);
}

function validateTextInputForm(textId, message) {
    if(document.getElementById(textId).value=="") {
        alert(message);
        return false;
    }
    return true;
}
