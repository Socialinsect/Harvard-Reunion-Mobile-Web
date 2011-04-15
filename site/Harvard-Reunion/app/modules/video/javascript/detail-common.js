var videoFrameOriginalRatio = null;

function resizeVideoFrame() {
  var videoFrame   = document.getElementById('videoFrame');
  var videoWrapper = document.getElementById('videoWrapper');
  
  if (videoFrame && videoWrapper) {
    if (videoFrameOriginalRatio === null) {
      videoFrameOriginalRatio = videoFrame.height/videoFrame.width;
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
