<?php
/**
  * @package Module
  * @subpackage Home
  */

class SiteHomeWebModule extends HomeWebModule {
  private $schedule = null;

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
          $this->assign('groupURL', $facebook->getGroupURL());
          $this->assign('groupName', $facebook->getGroupFullName());
          
        } else {
          $this->addInternalJavascript('/common/javascript/lib/utils.js');
          $this->addOnLoad('autoupdateContent("autoupdateContainer", "'.URL_PREFIX.$this->id.'/facebookContent");');
        
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
          $this->redirectTo('facebook', array(), true);
          
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

      case 'fqLogin':
        $foursquare = $this->schedule->getFoursquareFeed();
        //error_log(print_r($this->args, true));
        $url  = $this->getArg('url', FULL_URL_PREFIX.'home/');
        $code = $this->getArg('code', false);
        
        if ($code) {
          $foursquare->authorize($url, $code);
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
