<?php

/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

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
        $foursquare = $schedule->getFoursquareFeed();

        $authority = $user->getAuthenticationAuthorityIndex();
        $logoutArgs = array(
          'authority' => $authority,
          'hard'      => 1,
        );
        if ($authority == 'anonymous') {
          $logoutArgs['url'] = URL_PREFIX.ltrim('/login/?authority=harris', '/');
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
            'toggleURL' => $facebook->needsLogin() ? $facebook->getLoginURL() : $facebook->getLogoutURL(),
            'showHome'  => $user->getShowHomeFacebookPosts(),
          ),
          'foursquare' => array(
            'username'  => $foursquare->needsLogin() ? null : $foursquare->getUserFullName(),
            'fullname'  => $foursquare->needsLogin() ? null : $foursquare->getUserFullName(),
            'toggleURL' => $foursquare->needsLogin() ? 
              $foursquare->getLoginURL(true) : $this->buildBreadcrumbURL('foursquare', array()),
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
        $user->setShowHomeTwitterStream($this->getArg('showTwitter', false));
        $user->setShowHomeFacebookPosts($this->getArg('showFacebook', false));
        
        $this->redirectTo('index');
        break;
        
      case 'foursquare':
        $foursquare = $schedule->getFoursquareFeed();
        
        if ($foursquare->needsLogin()) {
          $this->redirectTo('index');
        }      

        $this->assign('username',      $foursquare->getUserFullName());
        $this->assign('returnURL',     $this->buildURL('index'));
        $this->assign('foursquareURL', $foursquare->getManualLogoutURL());

        $foursquare->setSession(null);
        break;
    }
  }
}
