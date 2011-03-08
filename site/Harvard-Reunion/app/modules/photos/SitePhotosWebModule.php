<?php
/**
  * @package Module
  * @subpackage Schedule
  */
/**
  */
includePackage('Authentication');
  
class SitePhotosWebModule extends WebModule {
  protected $id = 'photos';
  protected $schedule = null;

  protected function initialize() {
    $this->schedule = new Schedule();
  }

  protected function initializeForPage() {
    $session = $this->getUser()->getSessionData();
    
    $facebook = new FacebookGroup($this->schedule->getFacebookGroupId(), $session['fb_access_token']);
    
    switch ($this->page) {
      case 'help':
        break;

      case 'index':
        

        $this->assign('user',      $facebook->getFacebookUser());
        $this->assign('logoutURL', $facebook->getLogoutUrl("/{$this->id}/"));

        $this->assign('title',     $facebook->getFacebookGroupFullName());
        $this->assign('photos',    $facebook->getFacebookGroupPhotos());
        break;
              
      case 'detail':
        break;
    }
  }
}