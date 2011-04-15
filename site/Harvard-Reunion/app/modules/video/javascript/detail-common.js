var videoFrameOriginalRatio = null;

function resizeVideoFrame() {
  var videoFrame   = document.getElementById('videoFrame');
  var videoWrapper = document.getElementById('videoWrapper');
  
  if (videoFrame && videoWrapper) {
    if (videoFrameOriginalRatio === null) {
      if (videoFrame.width && videoFrame.height) {
        videoFrameOriginalRatio = videoFrame.height/videoFrame.width;
      } else {
        videoFrameOriginalRatio = videoFrame.offsetHeight/videoFrame.offsetWidth;
      }
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
    
    alert(newWidth+' x '+newHeight);
  }
}
