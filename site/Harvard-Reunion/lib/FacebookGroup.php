<?php

require_once realpath(SITE_LIB_DIR.'/facebook-php-sdk/src/facebook.php');

class FacebookGroup {
  private $facebook = null;
  private $groupId = null;
  private $oldGroup = false;
  private $newStyle = true;
  private $loginFailedURL = '';

  private $myId = null;
  private $queryFields = array(
    'group' => array(
      'name',
    ),
    'video' => null,
    'photo' => array(
      'source',
      'height',
      'width',
    ),
    'post'  => null,
  );
  const AUTHOR_URL    = 'http://m.facebook.com/profile.php?id=';
  const OLD_GROUP_URL = 'http://m.facebook.com/group.php?gid=';
  const NEW_GROUP_URL = 'http://m.facebook.com/home.php?sk=group_';
    
  function __construct($groupId, $isOldGroup) {
    $this->facebook = new ReunionFacebook(array(
      'appId'  => $GLOBALS['siteConfig']->getVar('FACEBOOK_APP_ID'),
      'secret' => $GLOBALS['siteConfig']->getVar('FACEBOOK_APP_SECRET'),
      'cookie' => true,
    ));

    $this->groupId = $groupId;
  }
  
  public function needsLogin() {
    return !$this->facebook->getSession();
  }
  
  public function getNeedsLoginURL() {
    return $this->facebook->getNeedsLoginURL();
  }
  
  public function getSwitchUserURL() {
    return $this->facebook->getSwitchUserURL();
  }
  
  public function getMyId() {
    return $this->facebook->getUser();
  }
  
  public function isMemberOfGroup() {
    $results = $this->graphQuery($this->getMyId().'/groups');
    
    if (isset($results, $results['data'])) {
      foreach ($results['data'] as $result) {
        if (isset($result['id']) && $result['id'] == $this->groupId) {
          return true;
        }
      }
    }
    
    return false;
  }
  
  public function getUserFullName() {
    $results = $this->graphQuery($this->getMyId());
    if ($results) {
      return $results['name'];
    }
    return 'error';
    
  }
  
  public function getGroupFullName() {
    $results = $this->getGroupDetails();
    if ($results) {
      return $results['name'];
    }
    return null;
  }
  
  public function getGroupURL() {
    if ($this->oldGroup) {
      return self::OLD_GROUP_URL.$this->groupId;
    } else {
      return self::NEW_GROUP_URL.$this->groupId;
    }
  }
  
  public function getGroupStatusMessages() {
    $results = $this->getGroupPosts();
    //error_log(print_r($results, true));
    
    $statuses = array();
    if (isset($results['data'])) {
      foreach ($results['data'] as $i => $post) {
        if ($post['type'] == 'status') {
          $statuses[] = $this->formatPost($post);
        }
      }
    }
    
    return $statuses;
  }
  
  public function getGroupPhotos() {
    $results = $this->getGroupPosts();
    //error_log(print_r($results, true));

    $photos = array();
    if (isset($results['data'])) {
      foreach ($results['data'] as $i => $post) {
        if ($post['type'] == 'photo') {
          $photos[] = $this->formatPost($post);
        }
      }
    }
    
    return $photos;
  }
  
  public function getGroupVideos() {
    $results = $this->getGroupPosts();

    $videos = array();
    if (isset($results['data'])) {
      foreach ($results['data'] as $i => $post) {
        if ($post['type'] == 'video') {
          $videos[] = $this->formatPost($post);
        }
      }
    }
    
    return $videos;
  }
  
  public function getGroupPhotoOrder() {
    return $this->getGroupPostOrder('photo');
  }
  
  public function getGroupVideoOrder() {
    return $this->getGroupPostOrder('video');
  }
  
  public function getPhotoPost($postId) {
    $post = $this->getPostDetails($postId);
    //error_log(print_r($post, true));
    
    $photoDetails = $this->formatPost($post);
    
    if (isset($post['object_id'])) {
      $photo = $this->getPhotoDetails($post['object_id']);
      //error_log(print_r($photo, true));
      
      if (isset($photo['source'], $photo['height'], $photo['width'])) {
        $photoDetails['img']['src'] = $photo['source'];
        $photoDetails['img']['height'] = $photo['height'];
        $photoDetails['img']['width'] = $photo['width'];
      }
    }
    
    return $photoDetails;
  }
  
  public function getVideoPost($postId) {
    $post = $this->getPostDetails($postId);
    //error_log(print_r($post, true));
    
    $videoDetails = $this->formatPost($post);
    
    if (isset($post['source'])) {
      $videoDetails['embedHTML'] = $this->getVideoEmbedHTML($post);
    }
    
    return $videoDetails;
  }
  
  public function addPost($message) {
    $results = $this->graphQuery($this->groupId.'/feed', 'POST', array('message' => $message));
  }
  
  public function addComment($objectId, $message) {
    $results = $this->graphQuery($objectId.'/comments', 'POST', array('message' => $message));
  }
  
  public function removeComment($commentId) {
    $results = $this->graphQuery($commentId, 'DELETE');
  }
  
  public function like($objectId) {
    $results = $this->graphQuery($objectId.'/likes', 'POST');
  }
  
  public function unlike($objectId) {
    $results = $this->graphQuery($objectId.'/likes', 'DELETE');
  }
  
  public function getComments($objectId) {
    $results = $this->graphQuery($objectId.'/comments', array('limit' => 500));
    
    $comments = array();
    if (isset($results['data'])) {
      foreach ($results['data'] as $comment) {
        $comments[] = array(
          'id'      => $comment['id'],
          'message' => $comment['message'],
          'author'  => array(
            'name' => $comment['from']['name'],
            'id'   => $comment['from']['id'],
            'url'  => $this->authorURL($comment['from']),
          ),
          'when'    => array(
            'time'       => strtotime($comment['created_time']),
            'delta'      => self::relativeTime($comment['created_time']),
            'shortDelta' => self::relativeTime($comment['created_time'], true),
          ),
        );
      }
    }
    
    return $comments;
  }
  
  public function getLikes($objectId) {
    $results = $this->graphQuery($objectId.'/likes');
        
    return isset($results['data']) ? $results['data'] : array();
  }

  private function getGroupDetails() {
    return $this->getObjectDetails('group', $this->groupId);
  }
  private function getPostDetails($objectId) {
    return $this->getObjectDetails('post', $objectId);
  }
  private function getPhotoDetails($objectId) {
    return $this->getObjectDetails('photo', $objectId);
  }
  private function getVideoDetails($objectId) {
    return $this->getObjectDetails('video', $objectId);
  }
  private function getObjectDetails($type, $objectId) {
    $args = array();
    if (isset($this->queryFields[$type])) {
      $args['fields'] = implode(',', $this->queryFields[$type]);
    }
  
    return $this->graphQuery($objectId, $args);
  }

  private function getGroupPosts() {
    return $this->graphQuery($this->groupId.'/feed', array('limit' => 1000));
  }
  
  private function getGroupPostOrder($type=null) {
    $results = $this->fqlQuery("SELECT post_id,actor_id,attachment FROM stream WHERE source_id={$this->groupId} LIMIT 1000");
    //error_log(print_r($results, true));
    
    $posts = array();
    foreach ($results as $result) {
      $post = array(
        'id'     => $result['post_id'],
        'type'   => 'status',
        'author' => array(
          'id' => $result['actor_id'],
        ),
      );
      if (isset($result['attachment'], $result['attachment']['media'])) {
        foreach ($result['attachment']['media'] as $media) {
          if (isset($media['type'])) {
            $post['type'] = $media['type'];
            break;
          }
        }
      }
      if (!$type || $type == $post['type']) {
        $posts[] = $post;
      }
    }

    return $posts;
  }

  private function formatPost($post) {
    $formatted = array();
    
    if (isset($post['id'])) {
      $formatted['id'] = $post['id'];
    }
    if (isset($post['from'])) {
      $formatted['author'] = array(
        'name' => $post['from']['name'],
        'id'   => $post['from']['id'],
        'url'  => $this->authorURL($post['from']),
      );
    }
    if (isset($post['created_time'])) {
      $formatted['when'] = array(
        'time'       => strtotime($post['created_time']),
        'delta'      => self::relativeTime($post['created_time']),
        'shortDelta' => self::relativeTime($post['created_time'], true),
      );
    }
    if (isset($post['message'])) {
      $formatted['message'] = $post['message'];
    }
    if (isset($post['picture'])) {
      $formatted['thumbnail'] = $post['picture'];
    }
    
    return $formatted ? $formatted : false;
  }
  
  private function formatPhoto($photo) {
    $formatted = array();
    
    if (isset($photo['id'])) {
      $formatted['id'] = $photo['id'];
      $formatted['position'] = isset($photo['position']) ? $photo['position'] : PHP_INT_MAX;
    }
    if (isset($photo['from'])) {
      $formatted['author'] = array(
        'name' => $photo['from']['name'],
        'id'   => $photo['from']['id'],
        'url'  => $this->authorURL($photo['from']),
      );
    }
    if (isset($photo['created_time'])) {
      $formatted['when'] = array(
        'time'       => strtotime($photo['created_time']),
        'delta'      => self::relativeTime($photo['created_time']),
        'shortDelta' => self::relativeTime($photo['created_time'], true),
      );
    }
    if (isset($photo['name'])) {
      $formatted['message'] = $photo['name'];
    }
    if (isset($photo['picture'])) {
      $formatted['thumbnail'] = $photo['picture'];
    }
    
    return $formatted ? $formatted : false;
  }
  
  private static function commentSort($a, $b) {
    return intval($a['when']['time']) - intval($b['when']['time']);
  }
  
  private static function positionSort($a, $b) {
    return intval($a['position']) - intval($b['position']);
  }
  
  private function authorURL($from) {
    return self::AUTHOR_URL.$from['id'];
  }
  
  private function graphQuery($path, $method='GET', $params=array()) {
    try {
      if (is_array($method) && empty($params)) {
        $params = $method;
        $method = 'GET';
      }
      $params['cacheName'] = "graph_{$path}";
      
      $results = $this->facebook->api($path, $method, $params);
      
    } catch (FacebookApiException $e) {
      error_log("Got Facebook graph API error: ".print_r($e->getResult(), true));
      return array();
    }
    
    return $results;
  }
  
  private function fqlQuery($query) {
    try {
      $results = $this->facebook->fql($query);
      
    } catch (FacebookApiException $e) {
      error_log("Got Facebook FQL error: ".print_r($e->getResult(), true));
      return array();
    }
    
    return $results;
  }
  
  
  public static function relativeTime($time=null, $shortFormat=false, $limit=86400) {
    if (empty($time) || (!is_string($time) && !is_numeric($time))) {
      $time = time();
      
    } else if (is_string($time)) {
      $time = strtotime($time);
    }
    
    $now = time();
    $relative = '';
    
    $diff = $now - $time;
    
    if ($diff >= $limit) {
      $format = $shortFormat ? 'M j g:ia' : 'M j g:ia';
      $relative = date($format, $time);
      
    } else if ($diff < 0) {
      $relative = 'in the future';
      
    } else if ($diff == 0) {
      $relative = 'now';
      
    } else if ($diff < 60) {
      $relative = $shortFormat ? '< 1 min ago' : 'less than one minute ago';
      
    } else if (($minutes = ceil($diff / 60)) < 60) {
      $relative = $minutes.($shortFormat ? ' min' : ' minute').($minutes == 1 ? '' : 's').' ago';
      
    } else {
      $hours = ceil($diff / 3600);
      $relative = ($shortFormat ? ' ' : 'about ').$hours.' hour'.($hours == 1 ? '' : 's').' ago';
    }
    
    return $relative;
  }
  
  private function getVideoEmbedHTML($post) {
    $html = '';
    
    $source = $post['source'];
    $isObject = isset($post['object_id']) && $post['object_id'];
    
    if ($isObject) {
      $html = '<video controls><source src="'.$source.'" /></video>';
      
    } else if (preg_match(';^http://www.youtube.com/v/([^&]+).*$;', $source, $matches)) {
      $videoID = $matches[1];

      $html = '<iframe id="videoFrame" src="http://www.youtube.com/embed/'.$videoID.
        '" width="640" height="390" frameborder="0"></iframe>';
    
    } else if (preg_match(';clip_id=(.+);', $source, $matches)) {
      $videoID = $matches[1];
      $videoInfo = json_decode(file_get_contents(
        "http://vimeo.com/api/v2/video/{$videoID}.json"), true);
      
      if (isset($videoInfo, $videoInfo[0], $videoInfo[0]['width'], $videoInfo[0]['height'])) {
        $html = '<iframe id="videoFrame" src="http://player.vimeo.com/video/'.$videoID.
          '" width="'.$videoInfo[0]['width'].'" height="'.$videoInfo[0]['height'].
          '" frameborder="0"></iframe>';
      }
    }
    
    return $html;
  }
}

class ReunionFacebook extends Facebook {
  private $perms = array(
    'user_about_me',
    'user_groups',
    'user_photos',
    'user_videos',
    'user_checkins',
    'publish_checkins',
    'read_stream',
    'publish_stream',
    //'offline_access',
  );
  protected $cache;
  protected $cacheLifetime = 60;
  
  
  public function __construct($config) {
    parent::__construct($config);

    self::$CURL_OPTS[CURLOPT_CONNECTTIMEOUT] = 20;
    //self::$CURL_OPTS[CURLOPT_SSL_VERIFYPEER] = false;
    //self::$CURL_OPTS[CURLOPT_SSL_VERIFYHOST] = 2;
  }
  
  // Override to always use touch display
  public function getLoginUrl($params=array()) {
    $params['display'] = 'touch';
    return parent::getLoginUrl($params);
    $currentUrl = $this->getCurrentUrl();
  }

  public function getNeedsLoginURL($needsLoginURL='') {
    return $this->getLoginURL(array(
      'next'       => $this->getCurrentUrl(),
      'cancel_url' => $this->getCurrentUrl(),
      'req_perms'  => implode(',', $this->perms),
    ));
  }
   
  public function getSwitchUserURL($needsLoginURL='') {
    $loginURL = $this->getLoginURL(array(
      'next'       => $this->getCurrentUrl(),
      'cancel_url' => $this->getCurrentUrl(),
      'req_perms'  => implode(',', $this->perms),
    ));
    
    return $this->getLogoutURL(array(
      'next' => $loginURL,
    ));
  }

  public function fql($query) {
    $results = json_decode($this->_oauthRequest($this->getUrl('api', 'method/fql.query'), array(
      'query'     => $query,
      'format'    => 'json',
      'cacheName' => 'fql_'.md5($query),
    )), true);
    
    if (is_array($results) && isset($results['error_code'])) {
      $e = new FacebookApiException($results);
      switch ($e->getType()) {
        // OAuth 2.0 Draft 00 style
        case 'OAuthException':
        // OAuth 2.0 Draft 10 style
        case 'invalid_token':
          $this->setSession(null);
      }
      throw $e;
      
    } else if (!is_array($results)) {
      $results = array();
    }
    
    return $results;
  }
  
  // Override to cache responses from the server
  protected function makeRequest($url, $params, $ch=null) {
    // Check if logged in:
    if (!$this->getSession()) {
      $loginURL = $this->getNeedsLoginURL();
      
      header("Location: $loginURL");
    }
        
    if (!$this->cache) {
      $this->cache = new DiskCache(CACHE_DIR."/Facebook", $this->cacheLifetime, TRUE);
      $this->cache->setSuffix('.json');
      $this->cache->preserveFormat();
    }
    
    $shouldCache = false;
    $cacheName = isset($params['cacheName']) ? $params['cacheName'] : '';
    if ($cacheName) {
      $method = isset($params['method']) ? $params['method'] : 'GET';
      if ($method == 'GET') {
        $shouldCache = true;  
      } else {
        // destroy cache
        $cacheFile = $this->cache->getFullPath($cacheName);
        if (file_exists($cacheFile)) {
          error_log("Removing stale cache file '$cacheFile'");
          @unlink($cacheFile);
        }
      }
    }
    unset($params['cacheName']); // Don't send this to facebook
    
    if ($shouldCache && $this->cache->isFresh($cacheName)) {
      $results = $this->cache->read($cacheName);
      
    } else {
      error_log("Requesting {$url}?".http_build_query($params));
      $results = parent::makeRequest($url, $params, $ch);
    
      if ($shouldCache) {
        $this->cache->write($results, $cacheName);
      }
    }
    
    return $results;
  }

  // Override to fix bug when logging in as a different user
  // https://github.com/facebook/php-sdk/issues#issue/263
  public function getSession() {
    if (!$this->sessionLoaded) {
      $signedRequest = $this->getSignedRequest();
      if ($signedRequest && !isset($signedRequest['user_id'])) {
        return null;
      }
    }
    return parent::getSession();
  }
}
