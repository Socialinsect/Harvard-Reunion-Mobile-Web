<?php
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
        $posts = $facebook->getMyId() ? $facebook->getGroupStatusMessages() : array();
        $tweets = $twitter->getRecentTweets();
        
        $recent = false;
        $recentType = false;
        if (count($posts) && $user->getShowHomeFacebookPosts()) {
          $recent = reset($posts);
          $recentType = 'facebook';
        }
        if (count($tweets) && $user->getShowHomeTwitterStream()) {
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
          'var RECENT_MESSAGE_AJAX_URL = "'.URL_BASE.API_URL_PREFIX.'/{$this->id}/recent"');
        
        $logoutArgs = array(
          'authority' => $userInfo['authority'],
          'hard'      => 1,
        );
        if ($userInfo['authority'] == 'anonymous') {
          $logoutArgs['url'] = URL_PREFIX.'/login/?authority=harris';
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
            'url'   => $facebook->getNeedsLoginURL(),
            'items' => 'posts',
          ));
          
        } else if (!$facebook->isMemberOfGroup()) {
          $this->assign('needsJoinGroup', true);
          $this->assign('groupURL', $facebook->getGroupURL());
          $this->assign('groupName', $facebook->getGroupFullName());
          
        } else {
          $this->addInlineJavascript(
            'var MESSAGE_LIST_AJAX_URL = "'.URL_BASE.API_URL_PREFIX.'/'.$this->id.'/facebook"');
          $this->addOnLoad('initMessageList();');
        
          $this->assign('user',          $facebook->getUserFullName());
          $this->assign('groupName',     $facebook->getGroupFullName());
          $this->assign('switchUserURL', $facebook->getSwitchUserURL());
          $this->assign('posts',         $facebook->getGroupStatusMessages());
          $this->assign('groupURL',      $facebook->getGroupURL());
        }
        break;
      
      case 'twitter':
        $this->addInlineJavascript(
          'var MESSAGE_LIST_AJAX_URL = "'.URL_BASE.API_URL_PREFIX.'/'.$this->id.'/twitter"');
        $this->addOnLoad('initMessageList();');

        $this->assign('hashtag',    $this->schedule->getTwitterHashTag());
        $this->assign('tweetURL',   $twitter->getTweetURL());
        $this->assign('twitterURL', $twitter->getFeedURL());
        $this->assign('posts',      $twitter->getRecentTweets());
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
        
      case 'fbLogout':
        $next = $this->getArg('next', FULL_URL_PREFIX.'home/');
        $facebook->expireSession('null');
        $redirect = $facebook->getLogoutRedirect($next);
        
        header("Location: $redirect");
        exit();
    }
    
    parent::initializeForPage();
  }
}
