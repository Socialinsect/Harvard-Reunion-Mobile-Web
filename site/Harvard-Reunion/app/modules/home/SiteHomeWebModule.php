<?php
/**
  * @package Module
  * @subpackage Home
  */

/**
  * @package Module
  * @subpackage Home
  */
class SiteHomeWebModule extends HomeWebModule {
  private $schedule = null;

  static public function loadReunionConfig() {
    $data = array();
    
    $feedConfigFile = realpath_exists(SITE_CONFIG_DIR.'/feeds/reunion.ini');
    if ($feedConfigFile) {
      $data = parse_ini_file($feedConfigFile, true);
    }
    
    return $data;
  }

  protected function initializeForPage() {
    $this->schedule = new Schedule();
    $user = $this->schedule->getAttendee();    

    $facebookUser = $this->getUser();
    $session = $facebookUser->getSessionData();
    if (isset($session['fb_access_token'])) {
      $facebook = new FacebookGroup($this->schedule->getFacebookGroupId(), $session['fb_access_token']);
    } else {
      $facebook = null;
    }
  
    switch ($this->page) {
      case 'index':
        // TODO: get from backend
        $userInfo = array(
          'fullname' => $user->getFullName(),
          'class' => $user->getGraduationClass(),
        );

        $scheduleInfo = array(
          'year'  => $this->schedule->getReunionNumber(),
          'dates' => $this->schedule->getDateDescription(),
          'title' => $this->schedule->getReunionTitle(),
        );
        
        $socialInfo = array(
          'facebook' => array(
            'name' => $this->schedule->getFacebookGroupName(),
            'url'  => 'http://m.facebook.com/home.php?sk=group_'.
                        $this->schedule->getFacebookGroupId(),
          ),
          'twitter' => array(
            'name' => $this->schedule->getTwitterHashTag(),
            'url'  => 'http://mobile.twitter.com/searches?q='.
                        urlencode($this->schedule->getTwitterHashTag()),
          ),
          'recent' => null,
        );
        
        $facebookPosts = $facebook ? $facebook->getGroupStatusMessages() : array();
        
        if (count($facebookPosts)) {
          $recentPost = reset($facebookPosts);
          
          
          $socialInfo['recent'] = array(
            'type'    => 'facebook',
            'message' => $recentPost['message'],
            'author'  => $recentPost['author']['name'],
            'age'     => $recentPost['when']['shortDelta'],
          );
        }
        
        $this->addInternalJavascript('/common/javascript/lib/ellipsizer.js');
        $this->addOnLoad('initHome();');
        
        $this->assign('userInfo',     $userInfo);
        $this->assign('scheduleInfo', $scheduleInfo);
        $this->assign('socialInfo',   $socialInfo);
        break;
        
     case 'search':
        break;
    }
    
    parent::initializeForPage();
  }
}
