<?php
/**
  * @package Module
  * @subpackage Schedule
  */
  
class SitePhotosWebModule extends WebModule {
  protected $id = 'photos';
  protected $schedule = null;

  protected function initialize() {
    $this->schedule = new Schedule();
  }

  protected function initializeForPage() {    
    switch ($this->page) {
      case 'help':
        break;

      case 'index':
        $this->schedule->getFacebookGroup();
        break;
              
      case 'detail':
        break;
    }
  }
}