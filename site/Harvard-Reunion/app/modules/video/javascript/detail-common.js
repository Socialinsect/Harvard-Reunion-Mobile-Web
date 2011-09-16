/****************************************************************
 *
 *  Copyright 2011 The President and Fellows of Harvard College
 *  Copyright 2011 Modo Labs Inc.
 *
 *****************************************************************/

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

function setupVideoPlayer() {
  setTimeout(function () {
    var movie = document.getElementById('html5Movie');
    if (movie) {
      movie.width = (movie.offsetWidth < document.body.offsetWidth) ? 
        movie.offsetWidth : document.body.offsetWidth;
      movie.height = movie.offsetHeight;
      console.log(movie.width + ' x '+movie.height);
      VideoJS.setupAllWhenReady();
    }
  }, 500);
}
