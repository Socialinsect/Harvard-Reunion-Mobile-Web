<?php
/**
  * @package Module
  * @subpackage Home
  */

/**
  * @package Module
  * @subpackage Home
  */
class SiteMapWebModule extends MapWebModule {
  protected function staticMapImageDimensions() {
    list($imageWidth, $imageHeight) = parent::staticMapImageDimensions();
    
    switch ($this->pagetype) {
      case 'compliant':
        if ($this->platform == 'bbplus') {
          $imageHeight = 100;
        } else {
          $imageHeight = 180;
        }
        break;
    }
    return array($imageWidth, $imageHeight);
  }
  
  protected function dynamicMapImageDimensions() {
    list($imageWidth, $imageHeight) = parent::dynamicMapImageDimensions();
    
    switch ($this->pagetype) {
      case 'compliant':
        if ($this->platform == 'bbplus') {
          $imageHeight = 100;
        } else {
          $imageHeight = 180;
        }
        break;
    }
    return array($imageWidth, $imageHeight);
  }


  protected function initializeForPage() {
    $buildingId = $this->getArg('building', false);
    if ($this->page == 'detail' && $buildingId) {
      // Figure out the full Harvard campus category
      if (!$this->feeds) {
        $this->feeds = $this->loadFeedData();
      }
      foreach ($this->feeds as $id => $feed) {
        if ($feed['TITLE'] == 'Search Results') {
          $this->args['category'] = $id;
          $this->args['featureindex'] = $buildingId;
          break;
        }
      }
    }
    
    parent::initializeForPage();
    
    if ($this->page == 'detail' && !isset($this->args['featureindex'])) {
      $this->assign('canBookmark', false);
    }
  }
}
