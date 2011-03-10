<?php
/**
  * @package Module
  * @subpackage Schedule
  */
/**
  */
includePackage('Authentication');
  
class SiteVideoWebModule extends WebModule {
  protected $id = 'video';
  protected $schedule = null;

  protected function initializeForPage() {
    $user = $this->getUser();
    $session = $user->getSessionData();
    
    $this->schedule = new Schedule();
    $facebook = new FacebookGroup($this->schedule->getFacebookGroupId(), $session['fb_access_token']);
    
    switch ($this->page) {
      case 'help':
        break;

      case 'index':
        $videos = $facebook->getGroupVideos();
        foreach ($videos as $i => $video) {
          $videos[$i]['url'] = $this->buildBreadcrumbURL('detail', array( 
            'id' => $video['id'],
          ));
        }

        $this->assign('user',      $user->getFullName());
        $this->assign('logoutURL', self::buildURLForModule('login', 'logout', array(
          'authority' => 'facebook'
        )));

        $this->assign('title',     $facebook->getGroupFullName());
        $this->assign('videos',    $videos);
        break;
              
      case 'detail':
        $this->assign('video', $facebook->getVideoPostDetails($this->getArg('id')));
        break;
    }
  }
}
