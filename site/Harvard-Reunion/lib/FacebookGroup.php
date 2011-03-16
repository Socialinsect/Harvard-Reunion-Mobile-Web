<?php

require_once realpath(SITE_LIB_DIR.'/facebook-php-sdk/src/facebook.php');

class FacebookGroup {
  private $facebook = null;
  private $groupId = null;
  private $newStyle = true;
  private $needsLoginURL = '';
  private $loginFailedURL = '';

  private $myId = null;
  private $queryFields = array(
    'group' => array(
      'name',
      'version',
    ),
    'video' => null,
    'photo' => array(
      'id',
      'from',
      'created_time',
      'name',
      'picture',
      'position',
    ),
    'post'  => array(
      'id',
      'from',
      'created_time',
      'message',
      'picture',
    ),
  );
  const AUTHOR_URL    = 'http://m.facebook.com/profile.php?id=';
  const OLD_GROUP_URL = 'http://m.facebook.com/group.php?gid=';
  const NEW_GROUP_URL = 'http://m.facebook.com/home.php?sk=group_';
  
  private $APP_ID = "193872970635695";
  private $APP_SECRET = "05a64a59e4ee8db3acae85673fb91795";
  
  function __construct($groupId, $needsLoginURL, $forceLogin) {
    $this->facebook = new ReunionFacebook(array(
      'appId'         => $this->APP_ID,
      'secret'        => $this->APP_SECRET,
      'cookie'        => true,
      'needsLoginURL' => $needsLoginURL,
    ));

    $this->groupId = $groupId;
    
    if ($forceLogin && !$this->facebook->getSession()) {
      $url = $this->facebook->getLoginStatusUrl(array(
        'no_user' => $needsLoginURL,
      ));
      
      header("Location: $url");
    }
  }
  
  public function getSwitchUserURL() {
    return $this->facebook->getSwitchUserURL();
  }
  
  public function getNeedsLoginURL() {
    return $this->facebook->getNeedsLoginURL();
  }
  
  public function getMyId() {
    return $this->facebook->getUser();
  }
  
  public function getUserFullName() {
    $results = $this->graphQuery('me');
    if ($results) {
      return $results['name'];
    }
    return 'error';
    
  }
  
  public function getGroupFullName() {
    $results = $this-getGroupDetails();
    if ($results) {
      return $results['name'];
    }
    return null;
  }
  
  public function getGroupURL() {
    $results = $this->getGroupDetails();

    if (isset($results, $results['version']) && $results['version'] == 0) {
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
    //$results = $this->getGroupPosts();
    //error_log(print_r($results, true));
     
    $results = $this->fqlQuery("SELECT pid, aid, owner, object_id, src, link, caption FROM photo WHERE pid IN (SELECT pid FROM photo_tag WHERE subject = {$this->groupId} LIMIT 1000) LIMIT 1000");
    //$result3 = $this->fqlQuery("SELECT post_id,viewer_id,message,attachment FROM stream WHERE source_id={$this->groupId}  LIMIT 1000");
    //error_log(print_r($results, true));
    
    $photos = array();
    foreach ($results as $result) {
      if (isset($result['object_id'])) {
        $photo = $this->getPhotoDetails(number_format($result['object_id'], 0, '.', ''));
        
        $photos[] = $this->formatPhoto($photo);
      }
    }
    usort($photos, array(get_class($this), 'positionSort'));
    
    return $photos;
  }
  
  public function getGroupVideos() {
    $result = $this->getGroupPosts();

    $videos = array();
    if (isset($result['data'])) {
      foreach ($result['data'] as $i => $post) {
        if ($post['type'] == 'video') {
          $videos[] = $this->formatPost($post);
        }
      }
    }
    
    return $videos;
  }
  
  public function getPhoto($photoId) {
    $photo = $this->getPhotoDetails($photoId);
    //error_log(print_r($photo, true));    

    $photoDetails =  $this->formatPhoto($photo);
    
    if (isset($photo['source'], $photo['height'], $photo['width'])) {
      $photoDetails['img']['src'] = $photo['source'];
      $photoDetails['img']['height'] = $photo['height'];
      $photoDetails['img']['width'] = $photo['width'];
    }
    
    return $photoDetails;
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

    if (isset($post['object_id'])) {
      //$video = $this->getVideoDetails($post['object_id']);
      //error_log(print_r($video, true));
    }
    
    return $videoDetails;
  }  
  
  public function addComment($objectId, $message) {
    $result = $this->graphQuery($objectId.'/comments', array('message' => $message));
  }
  
  public function removeComment($commentId) {
    $result = $this->graphQuery($commentId, 'DELETE');
  }
  
  public function like($objectId) {
    $result = $this->graphQuery($objectId.'/likes', 'POST');
  }
  
  public function unlike($objectId) {
    $result = $this->graphQuery($objectId.'/likes', 'DELETE');
  }
  
  public function getComments($objectId) {
    $result = $this->graphQuery($objectId.'/comments', array('limit' => 500));
    
    $comments = array();
    if (isset($result['data'])) {
      foreach ($result['data'] as $comment) {
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
    $result = $this->graphQuery($objectId.'/likes');
        
    return isset($result['data']) ? $result['data'] : array();
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

  private function formatPost($post) {
    $formatted = array(
      'id'    => $post['id'],
      'author'    => array(
        'name' => $post['from']['name'],
        'id'   => $post['from']['id'],
        'url'  => $this->authorURL($post['from']),
      ),
      'when'  => array(
        'time'       => strtotime($post['created_time']),
        'delta'      => self::relativeTime($post['created_time']),
        'shortDelta' => self::relativeTime($post['created_time'], true),
      ),
    );
    if (isset($post['message'])) {
      $formatted['message'] = $post['message'];
    }
    if (isset($post['picture'])) {
      $formatted['thumbnail'] = $post['picture'];
    }
    
    return $formatted;
  }
  
  private function formatPhoto($photo) {
    $formatted = array(
      'id'    => $photo['id'],
      'author'    => array(
        'name' => $photo['from']['name'],
        'id'   => $photo['from']['id'],
        'url'  => $this->authorURL($photo['from']),
      ),
      'when'  => array(
        'time'       => strtotime($photo['created_time']),
        'delta'      => self::relativeTime($photo['created_time']),
        'shortDelta' => self::relativeTime($photo['created_time'], true),
      ),
    );
    if (isset($photo['name'])) {
      $formatted['message'] = $photo['name'];
    }
    if (isset($photo['picture'])) {
      $formatted['thumbnail'] = $photo['picture'];
    }
    
    $formatted['position'] = isset($photo['position']) ? $photo['position'] : PHP_INT_MAX;
    
    return $formatted;
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
    'user_photos',
    'user_videos',
    'user_checkins',
    'publish_checkins',
    'read_stream',
    'publish_stream',
    //'offline_access',
  );
  private $needsLoginURL = '';  
  protected $cache;
  protected $cacheLifetime = 30;

  
  public function __construct($config) {
    parent::__construct($config);

    if (isset($config['needsLoginURL'])) {
      $this->needsLoginURL = $config['needsLoginURL'];
    }
    
    self::$CURL_OPTS[CURLOPT_SSL_VERIFYPEER] = false;
    self::$CURL_OPTS[CURLOPT_SSL_VERIFYHOST] = 2;
  }
  
  public function getNeedsLoginURL() {
    return $this->getLoginURL(array(
      'next'       => $this->getCurrentUrl(),
      'cancel_url' => $this->needsLoginURL ? $this->needsLoginURL : $this->getCurrentUrl(),
      'req_perms'  => implode(',', $this->perms),
    ));
  }
   
  public function getSwitchUserURL() {
    $loginURL = $this->getLoginURL(array(
      'next'       => $this->getCurrentUrl(),
      'cancel_url' => $this->needsLoginURL ? $this->needsLoginURL : $this->getCurrentUrl(),
      'req_perms'  => implode(',', $this->perms),
    ));
    
    return $this->getLogoutURL(array(
      'next' => $loginURL,
    ));
  }

  public function fql($query) {
    $results = json_decode($this->_oauthRequest($this->getUrl('api', 'method/fql.query'), array(
      'query'  => $query,
      'format' => 'json',
    )), true);
    
    if (is_array($results) && isset($results['error_code'])) {
      $e = new FacebookApiException($result);
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
  
  protected function makeRequest($url, $params, $ch=null) {
    // Check if logged in:
    if (!$this->getSession()) {
      $loginURL = $this->getNeedsLoginURL();
      
      header("Location: $loginURL");
    }
  
    $shouldCache = isset($params['method']) && $params['method'] == 'GET';
  
    $cacheParams = http_build_query($params);
    $cacheName = "$url".($cacheParams ? '?' : '').$cacheParams;
    
    if ($shouldCache && !$this->cache) {
      $this->cache = new DiskCache(CACHE_DIR."/Facebook", $this->cacheLifetime, TRUE);
      $this->cache->setSuffix('.json');
      $this->cache->preserveFormat();
    }
    
    if ($shouldCache && $this->cache->isFresh($cacheName)) {
      $result = $this->cache->read($cacheName);
      
    } else {
      error_log("Requesting $cacheName");
      $result = parent::makeRequest($url, $params, $ch);
    
      if ($shouldCache) {
        $this->cache->write($result, $cacheName);
      }
    }
    
    return $result;
  }
}
