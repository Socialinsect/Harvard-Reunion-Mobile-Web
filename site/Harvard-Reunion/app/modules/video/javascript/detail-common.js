var videoFrameOriginalRatio = null;

function resizeVideoFrame() {
  var videoFrame = document.getElementById('videoFrame');
  
  if (videoFrame) {
    if (videoFrameOriginalRatio === null) {
      if (videoFrame.width && videoFrame.height) {
        videoFrameOriginalRatio = videoFrame.height/videoFrame.width;
      } else {
        videoFrameOriginalRatio = videoFrame.offsetHeight/videoFrame.offsetWidth;
      }
    }
    
    var newWidth = document.body.offsetWidth;
    var newHeight = Math.round(newWidth*videoFrameOriginalRatio);
    
    videoFrame.width = newWidth;
    videoFrame.height = newHeight;

    // Run a second time in case the scroll bar disappeared when we resized
    var newWidth = document.body.offsetWidth;
    var newHeight = Math.round(newWidth*videoFrameOriginalRatio);
    
    videoFrame.width = newWidth;
    videoFrame.height = newHeight;
  }
}
