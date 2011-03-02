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
    
    if ($this->page == 'detail' && !$this->getArg('featureindex', false)) {
      $this->assign('canBookmark', false);
    }
  }
}
