<?php

class FacebookGroup {
  private $accessToken = null;
  private $groupId = null;
  private $myId = null;
  private $CURL_OPTS = array(
    CURLOPT_CONNECTTIMEOUT => 20,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 90,
    CURLOPT_USERAGENT      => 'facebook-php-2.0',
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 2,
  );
  private $apiToURL = array(
    'graph' => 'https://graph.facebook.com/',
    'fql'   => 'https://api.facebook.com/method/fql.query',
  );
  private $queryFields = array(
    'video' => null,
    'photo' => null,
    'post'  => null,
  );
  const AUTHOR_URL = "http://m.facebook.com/profile.php?id=";
  
  protected $cache;
  protected $cacheLifetime = 30;
  
  function __construct($groupId, $accessToken) {
    $this->accessToken = $accessToken;
    $this->groupId = $groupId;
  }
  
  public function getMyId() {
    if (!$this->myId) {
      $json = $this->graphQuery('me');
      if ($json) {
        $this->myId = $json['id'];
      }
    }
    
    return $this->myId;
  }
  
  public function getGroupFullName() {
    $json = $this->graphQuery($this->groupId, array('cache' => true));
    if ($json) {
      return $json['name'];
    }
    return null;
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
    $result = $this->graphQuery($objectId.'/comments', array(), array(), array('message' => $message));
  }
  
  public function removeComment($commentId) {
    $result = $this->graphQuery($commentId, array('method' => 'DELETE'));
  }
  
  public function like($objectId) {
    $result = $this->graphQuery($objectId.'/likes', array('method' => 'POST'));
  }
  
  public function unlike($objectId) {
    $result = $this->graphQuery($objectId.'/likes', array('method' => 'DELETE'));
  }
  
  public function getComments($objectId) {
    $result = $this->graphQuery($objectId.'/comments', array(), array('limit' => 500));
    
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
    $result = $this->graphQuery($objectId.'/likes', array());
        
    return isset($result['data']) ? $result['data'] : array();
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
  
    return $this->graphQuery($objectId, array('cache' => true), $args);
  }

  private function getGroupPosts() {
    return $this->graphQuery($this->groupId.'/feed', array('cache' => true), array('limit' => 1000));
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
  
  private function graphQuery($path, $options=array(), $getParams=array(), $postParams=array()) {
    $results = json_decode($this->query('graph', $path, $options, $getParams, $postParams), true);

    if (isset($results['error'])) {
      error_log("Got Facebook graph API error: ".print_r($results['error'], true));
      return array();
    }
    
    return $results;
  }
  
  private function fqlQuery($query, $options=array()) {
    $getParams = array(
      'query'  => $query,
      'format' => 'json',
    );
    
    $results = json_decode($this->query('fql', '', $options, $getParams), true);

    if (isset($results['error_code'])) {
      error_log("Got Facebook FQL error: {$results['error_msg']} ({$results['error_code']})");
      return array();
    }
    
    return $results;
  }

  private function query($type, $path, $options=array(), $getParams=array(), $postParams=array()) {
    if (!isset($this->apiToURL[$type])) { return null; }
    
    // Options:
    $method = isset($options['method']) ? $options['method'] : 'GET';
    $cacheResult = isset($options['cache']) && $options['cache'];
    if (count($postParams)) { 
      $method = 'POST';
    }
    if ($method != 'GET') {
      $cacheResult = false;  // only cache GET requests
    }
    
    // json_encode all params values that are not strings
    foreach ($getParams as $key => $value) {
      if (!is_string($value)) {
        $getParams[$key] = json_encode($value);
      }
    }    
    foreach ($postParams as $key => $value) {
      if (!is_string($value)) {
        $postParams[$key] = json_encode($value);
      }
    }    
    
    $cacheParams = http_build_query($getParams, null, '&');
    $cacheName = "{$type}_$path".($cacheParams ? '?' : '').$cacheParams;
    
    if ($cacheResult && !$this->cache) {
      $this->cache = new DiskCache(CACHE_DIR."/Facebook", $this->cacheLifetime, TRUE);
      $this->cache->setSuffix('.json');
      $this->cache->preserveFormat();
    }
    
    if ($cacheResult && $this->cache->isFresh($cacheName)) {
      $result = $this->cache->read($cacheName);
      
    } else {
      // add access token to params
      $getParams['access_token'] = $this->accessToken;
      
      $getParamString = http_build_query($getParams, null, '&');
      $url = $this->apiToURL[$type].$path.($getParamString ? '?' : '').$getParamString;

      error_log("$method request for $url");
      
      $opts = $this->CURL_OPTS;
      $opts[CURLOPT_CUSTOMREQUEST] = $method;
      if (count($postParams)) {
        error_log("... with post parameters ".json_encode($postParams));
        $opts[CURLOPT_POSTFIELDS] = $postParams;
      }
      $opts[CURLOPT_URL] = $url;
      $opts[CURLOPT_HTTPHEADER] = array('Expect:'); // disable 'Expect: 100-continue' behavior
      
      $ch = curl_init();
      curl_setopt_array($ch, $opts);
      $result = curl_exec($ch);
      curl_close($ch);
      
      if ($cacheResult) {
        if ($result !== false && $result !== 'false') {
          $this->cache->write($result, $cacheName);
          
        } else if ($result === false) {
          error_log("Request failed, reading expired cache");
          $result = $this->cache->read($cacheName);
        }
      }
    }
    
    return $result;
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
