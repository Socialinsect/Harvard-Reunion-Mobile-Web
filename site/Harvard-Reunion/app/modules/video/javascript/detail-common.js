var videoFrameOriginalRatio = null;

function resizeVideoFrame() {
  var videoFrame   = document.getElementById('videoFrame');
  var videoWrapper = document.getElementById('videoWrapper');
  
  if (videoFrame && videoWrapper) {
    if (videoFrameOriginalRatio === null) {
      videoFrameOriginalRatio = videoFrame.offsetHeight/videoFrame.offsetWidth;
    }
    
    var newWidth = videoWrapper.offsetWidth;
    var newHeight = Math.round(newWidth*videoFrameOriginalRatio);
    
    videoFrame.width = newWidth;
    videoFrame.height = newHeight;

    // Run a second time in case the scroll bar disappeared when we resized
    var newWidth = videoWrapper.offsetWidth;
    var newHeight = Math.round(newWidth*videoFrameOriginalRatio);
    
    videoFrame.width = newWidth;
    videoFrame.height = newHeight;
  }
}

function validateTextInputForm(textId, message) {
    if(document.getElementById(textId).value=="") {
        alert(message);
        return false;
    }
    return true;
}
