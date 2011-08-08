<?php

/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

/**
  * @package Module
  * @subpackage Home
  */

class SiteHomeWebModule extends HomeWebModule {
  private $schedule = null;
  const RETURN_URL_COOKIE_NAME = 'fqReunionLoginReturnURL';

  protected function initializeForPage() {
    $user = $this->getUser('HarvardReunionUser');
    $this->schedule = new Schedule($user);
    
    $facebook = $this->schedule->getFacebookFeed();
    $twitter = $this->schedule->getTwitterFeed();

    switch ($this->page) {
      case 'index':
        $userInfo = array(
          'authority'=> $user->getAuthenticationAuthorityIndex(),
          'fullname' => $user->getFullName(),
          'class'    => $user->getGraduationClass(),
        );

        $scheduleInfo = array(
          'year'  => $this->schedule->getReunionNumber(),
          'dates' => $this->schedule->getDateDescription(),
          'title' => $this->schedule->getReunionTitle(),
        );
        
        $socialInfo = array(
          'facebook' => array(
            'name' => $this->schedule->getFacebookGroupName(),
            'url' => $this->buildURL('facebook', array()),
          ),
          'twitter' => array(
            'name' => $this->schedule->getTwitterHashTag(),
            'url' => $this->buildURL('twitter', array()),
          ),
          'recent' => null,
        );
        
        // Only grab posts if logged in
        $posts = array();
        $tweets = array();
        if ($user->getShowHomeFacebookPosts() && !$facebook->needsLogin() && $facebook->isMemberOfGroup()) {
          $posts = $facebook->getGroupStatusMessages();
        }
        if ($user->getShowHomeTwitterStream()) {
          $tweets = $twitter->getRecentTweets();
        }
        
        $recent = false;
        $recentType = false;
        if (count($posts)) {
          $recent = reset($posts);
          $recentType = 'facebook';
          $recent['message'] = htmlspecialchars($recent['message']); // match twitter which is already escaped
          $recent['author']['name'] = htmlspecialchars($recent['author']['name']);
        }
        if (count($tweets)) {
          $tweet = reset($tweets);
          if (!$recent || 
              (intval($tweet['when']['time']) > intval($recent['when']['time']))) {
            $recent = $tweet;
            $recentType = 'twitter';
          }
        }
        if ($recent) {
          $socialInfo['recent'] = array(
            'type'    => $recentType,
            'message' => $recent['message'],
            'author'  => $recent['author']['name'],
            'age'     => $recent['when']['shortDelta'],
          );
        }
        
        $this->addInternalJavascript('/common/javascript/lib/ellipsizer.js');
        $this->addOnLoad('initHome();');
        
        $this->addInlineJavascript(
          'var RECENT_MESSAGE_AJAX_URL = "'.URL_BASE.API_URL_PREFIX.'/'.$this->id.'/recent"');
        
        $logoutArgs = array(
          'authority' => $userInfo['authority'],
          'hard'      => 1,
        );
        if ($userInfo['authority'] == 'anonymous') {
          $logoutArgs['url'] = URL_PREFIX.'login/?authority=harris';
        }
        $logoutURL = $this->buildURLForModule('login', 'logout', $logoutArgs);
        
        $this->assign('logoutURL',    $logoutURL);
        $this->assign('userInfo',     $userInfo);
        $this->assign('scheduleInfo', $scheduleInfo);
        $this->assign('socialInfo',   $socialInfo);
        break;
        
      case 'search':
        break;
        
      case 'facebook':
        if ($facebook->needsLogin()) {
          $this->assign('needsLogin', true);
          $this->assign('service', array(
            'type'  => 'facebook',
            'name'  => 'Facebook',
            'url'   => $facebook->getLoginURL(),
            'items' => 'posts',
          ));
          
        } else if (!$facebook->isMemberOfGroup()) {
          $this->assign('needsJoinGroup', true);
          $this->assign('groupURL', $facebook->getGroupURL(true));
          $this->assign('groupName', $facebook->getGroupFullName());
          $this->assign('itemType', 'posts');
          
        } else {
          $this->addInternalJavascript('/common/javascript/lib/utils.js');
          $this->addOnLoad('autoupdateContent("autoupdateContainer", "'.URL_PREFIX.$this->id.'/facebookContent");');
          
          if ($this->getArg('posted', false)) {
            $this->addOnLoad('_gaq.push('.json_encode(array(
              '_trackEvent', GA_EVENT_CATEGORY, 'Facebook Post', $facebook->getGroupFullName(),
            )).');');
          }
          
          $this->assign('user',          $facebook->getUserFullName());
          $this->assign('groupName',     $facebook->getGroupFullName());
          $this->assign('switchUserURL', $facebook->getSwitchUserURL());
          $this->assign('posts',         $facebook->getGroupStatusMessages());
          $this->assign('groupURL',      $facebook->getGroupURL());
        }
        break;
      
      case 'facebookContent':
        if (!$facebook->needsLogin() && $facebook->isMemberOfGroup()) {
          $this->assign('posts', $facebook->getGroupStatusMessages());
        }
        break;
      
      case 'twitter':
        $this->addInternalJavascript('/common/javascript/lib/utils.js');
        $this->addOnLoad('autoupdateContent("autoupdateContainer", "'.URL_PREFIX.$this->id.'/twitterContent");');
        
        $this->assign('hashtag',    $this->schedule->getTwitterHashTag());
        $this->assign('tweetURL',   $twitter->getTweetURL());
        $this->assign('twitterURL', $twitter->getFeedURL());
        $this->assign('posts',      $twitter->getRecentTweets());
        break;
       
      case 'twitterContent':
        $this->assign('posts', $twitter->getRecentTweets());
        break;
     
      case 'commentsContent':
        $this->assign('post', array('comments' => $facebook->getComments($this->getArg('id'))));
        break;
     
      case 'add':
        $message = $this->getArg('message');
        $type = $this->getArg('type');
        
        if ($type == 'facebook') {
          if ($message) {
            $facebook->addPost($message);
          }
          $this->redirectTo('facebook', array('posted' => '1'), true);
          
        } else if ($type == 'twitter') {
          $this->redirectTo('twitter', array(), true);
          
        } else {
          $this->redirectTo('index');
        }
        break;

      case 'fbLogin':
        $url  = $this->getArg('url', FULL_URL_PREFIX.'home/');
        $code = $this->getArg('code', false);
        
        if ($code) {
          $facebook->authorize($url, $code);
        }
        
        error_log("fbLogin: Redirecting to $url");
        header("Location: $url");
        exit();
        
      case 'fbLogout':
        $url = $this->getArg('next', FULL_URL_PREFIX.'home/');
        
        $facebook->expireSession();
        $redirect = $facebook->getLogoutRedirectURL($url);
        error_log("fbLogout: Redirecting to $redirect");
        header("Location: $redirect");
        exit();

      case 'fqLoginStart':
        $foursquare = $this->schedule->getFoursquareFeed();

        // foursquare redirect urls get url encoded on every hop
        // this interacts poorly with our breadcrumb urls and causes them
        // to become so large they break low end devices
        // To avoid this we stash the url in a cookie and redirect back to 
        // fqLogin which then reads the cookie and redirects back 
        $returnURL = $this->getArg('returnURL', FULL_URL_PREFIX.'home/');
        setcookie(self::RETURN_URL_COOKIE_NAME, $returnURL, 0, COOKIE_PATH);
        
        // This will return from foursquare to fqLogin below
        $url = $foursquare->getOAuthURL($this->getArg('forceDialog', false));
      
        error_log("fqLoginStart: Redirecting to $url");
        header("Location: $url");
        exit();
        

      case 'fqLogin':
        $url = $this->argVal($_COOKIE, self::RETURN_URL_COOKIE_NAME, FULL_URL_PREFIX.'home/');
        if (isset($_COOKIE[self::RETURN_URL_COOKIE_NAME])) {
          // blow away cookie
          setcookie(self::RETURN_URL_COOKIE_NAME, '', time() - 3600, COOKIE_PATH);
        }

        $foursquare = $this->schedule->getFoursquareFeed();
        
        $code = $this->getArg('code', false);
        if ($code) {
          $foursquare->authorize($code);
        }
        
        error_log("fqLogin: Redirecting to $url");
        header("Location: $url");
        exit();
        
      case 'fqLogout':
        // This currently doesn't work because foursquare doesn't have a 
        // static logout url (all logout urls need a hex key)
        // Leaving this here for when they add it.
        $foursquare = $this->schedule->getFoursquareFeed();
      
        $url = $this->getArg('url', FULL_URL_PREFIX.'home/');
        
        $foursquare->setSession(null);
        $redirect = $foursquare->getLogoutRedirectURL($url);
        error_log("fqLogout: Redirecting to $redirect");
        header("Location: $redirect");
        exit();

    }
    
    parent::initializeForPage();
    
    if ($this->page == 'index' && 
        ($this->pagetype == 'basic' || $this->pagetype == 'touch')) {
      // Suppress video module
      $modules = $this->getTemplateVars('modules');
      unset($modules['video']);
      $this->assign('modules', $modules);
    }
  }
}
