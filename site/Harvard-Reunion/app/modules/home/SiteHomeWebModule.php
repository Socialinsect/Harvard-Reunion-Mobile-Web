<?php
/**
  * @package Module
  * @subpackage Home
  */

class SiteHomeWebModule extends HomeWebModule {
  private $schedule = null;
  const SEARCH_URL = 'http://search.twitter.com/search.json';
  protected $tweetCache;
  protected $tweetCacheLifetime = 30;

  static public function loadReunionConfig() {
    $data = array();
    
    $feedConfigFile = realpath_exists(SITE_CONFIG_DIR.'/feeds/reunion.ini');
    if ($feedConfigFile) {
      $data = parse_ini_file($feedConfigFile, true);
    }
    
    return $data;
  }

  function getRecentTweets($hashtag, $limit=1) {
    $cacheName = "search_{$hashtag}";
  
    if (!$this->tweetCache) {
      $this->tweetCache = new DiskCache(CACHE_DIR."/Twitter", $this->tweetCacheLifetime, TRUE);
      $this->tweetCache->setSuffix('.json');
      $this->tweetCache->preserveFormat();
    }
    
    $content = '';
    if ($this->tweetCache->isFresh($cacheName)) {
      $content = $this->tweetCache->read($cacheName);
      
    } else {
      $url = self::SEARCH_URL.'?'.http_build_query(array(
        'q'           => $hashtag,
        'result_type' => 'recent',
        'rpp'         => $limit,
      ));
      $content = @file_get_contents($url);
      $this->tweetCache->write($content, $cacheName);
    }
      
    $json = json_decode($content, true);
    
    $tweets = array();
    if (is_array($json) && isset($json['results'])) {
      foreach ($json['results'] as $result) {
        $tweets[] = array(
          'message' => $result['text'],
          'author'  => array(
            'name' => $result['from_user'],
            'id'   => $result['from_user_id'],
          ),
          'when' => array(
            'time'       => strtotime($result['created_at']),
            'delta'      => FacebookGroup::relativeTime($result['created_at']),
            'shortDelta' => FacebookGroup::relativeTime($result['created_at'], true),
          ),
        );
      }
    }
    
    return $tweets;
  }


  protected function initializeForPage() {
    $user = $this->getUser('HarvardReunionUser');error_log(print_r($user, true));
    $this->schedule = new Schedule($user);
    
    $facebook = $this->schedule->getFacebookGroup();

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
        $tweets = $this->getRecentTweets($this->schedule->getTwitterHashTag());
        
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
        
        $this->assign('userInfo',     $userInfo);
        $this->assign('scheduleInfo', $scheduleInfo);
        $this->assign('socialInfo',   $socialInfo);
        break;
        
      case 'search':
        break;
        
      case 'facebook':
        $this->setPageTitle('Facebook Group');
        
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
          
        } else {
          $this->assign('user',          $facebook->getUserFullName());
          $this->assign('groupName',     $facebook->getGroupFullName());
          $this->assign('switchUserURL', $facebook->getSwitchUserURL());
          $this->assign('posts',         $facebook->getGroupStatusMessages());
        }
        
        $this->assign('groupName', $facebook->getGroupFullName());
        break;
      
      case 'twitter':
        $this->assign('hashtag', $this->schedule->getTwitterHashTag());
        $this->assign('tweetURL', $this->schedule->getTweetURL());
        $this->assign('posts',   $this->getRecentTweets($this->schedule->getTwitterHashTag(), 1000));
        break;
      
      case 'add':
        $message = $this->getArg('message');
        $type = $this->getArg('type');
        
        if ($message && $type == 'facebook') {
          $facebook->addPost($message);
          $this->redirectTo('facebook', array(), true);
          
        } else if ($message && $type == 'twitter') {
          $this->redirectTo('twitter', array(), true);
          
        } else {
          $this->redirectTo('index');
        }
        break;
    }
    
    parent::initializeForPage();
  }
}
