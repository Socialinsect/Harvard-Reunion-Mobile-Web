<?php
/**
  * @package Module
  * @subpackage About
  */

class SiteSettingsWebModule extends WebModule {
  protected $id = 'settings';

  protected function initializeForPage() {
    $user = $this->getUser('HarvardReunionUser');
    $schedule = new Schedule($user);
    
    switch ($this->page) {
      case 'index':
        $facebook = $schedule->getFacebookFeed();

        $authority = $user->getAuthenticationAuthorityIndex();
        $logoutArgs = array(
          'authority' => $authority,
          'hard'      => 1,
        );
        if ($authority == 'anonymous') {
          $logoutArgs['url'] = URL_PREFIX.'/login/?authority=harris';
        }
        $info['logoutURL'] = $this->buildURLForModule('login', 'logout', $logoutArgs);

        $info = array(
          'harris' => array(
            'authority' => $authority,
            'username'  => $user->getUserID(),
            'fullname'  => $user->getFullName(),
            'year'      => $user->getGraduationClass(),
            'shortYear' => substr($user->getGraduationClass(), 2),
            'number'    => $schedule->getReunionNumber(),
            'toggleURL' => $this->buildURLForModule('login', 'logout', $logoutArgs),
          ),
          'facebook' => array(
            'groupName' => $schedule->getFacebookGroupName(),
            'username'  => $facebook->needsLogin() ? null : $facebook->getUserFullName(),
            'fullname'  => $facebook->needsLogin() ? null : $facebook->getUserFullName(),
            'toggleURL' => $facebook->needsLogin() ? $facebook->getNeedsLoginURL() : $facebook->getLogoutURL(),
            'showHome'  => $user->getShowHomeFacebookPosts(),
          ),
          'foursquare' => array(
            'username'  => null,
            'fullname'  => null,
            'toggleURL' => 'https://foursquare.com/mobile/login?continue='.
              urlencode(FULL_URL_PREFIX.$this->buildBreadcrumbURL($this->page, $this->args, false)),
          ),
          'twitter' => array(
            'hashtag'   => $schedule->getTwitterHashTag(),
            'showHome'  => $user->getShowHomeTwitterStream(),
          ),
        );
        
        $this->addInlineJavascript('var CHANGE_SETTIINGS_URL = "'.
          FULL_URL_PREFIX.$this->id.'/change?"');
        
        //error_log(print_r($info, true));
        $this->assign('info', $info);
        break;
        
      case 'change':
        if (isset($this->args['showTwitter'])) {
          $user->setShowHomeTwitterStream($this->args['showTwitter']);
        }
        if (isset($this->args['showFacebook'])) {
          $user->setShowHomeFacebookPosts($this->args['showFacebook']);
        }
        
        $this->redirectTo('index');
        break;
    }
  }
}
