var videoFrameHeight = null;
var videoFrameWidth = null;

function resizeVideoFrame() {
  var videoFrame = document.getElementById('videoFrame');
  
  if (videoFrame) {
    if (videoFrameHeight === null) {
      videoFrameWidth  = videoFrame.offsetWidth;
      videoFrameHeight = videoFrame.offsetHeight;
    }
    
    videoFrame.width = '100%';

    var newWidth = videoFrame.offsetWidth;
    var newHeight = Math.round(newWidth/videoFrameWidth * videoFrameHeight);
    
    videoFrame.width = newWidth;
    videoFrame.height = newHeight;
  }
}