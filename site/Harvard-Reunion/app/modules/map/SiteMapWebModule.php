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
    $event = $this->getArg('event', false);
    if ($this->page == 'detail' && $event) {
      // muck with arguments to make the right thing happen
    }
    
    parent::initializeForPage();
    
    if ($this->page == 'detail' && $event) {
      // set template variables
    }
  }
}
