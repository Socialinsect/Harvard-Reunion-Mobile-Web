<?php

class FacebookGroup {
  private $accessToken = null;
  private $groupId = null;
  private $CURL_OPTS = array(
    CURLOPT_CONNECTTIMEOUT => 20,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 90,
    CURLOPT_USERAGENT      => 'facebook-php-2.0',
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 2,
  );
  private $apiToURL = array(
    'graph' => "https://graph.facebook.com/",
  );
  private $queryFields = array(
    'video' => null,
    'photo' => array(
      'source',
      'height',
      'width',
      'images',
    ),
    'post'  => array(
      'id',
      'from',
      'message',
      'source',
      'type',
      'object_id',
      'updated_time',
      'comments',
    ),
  );
  const AUTHOR_URL = "http://m.facebook.com/profile.php?id=";
  
  protected $cache;
  protected $cacheLifetime = 60;
  
  function __construct($groupId, $accessToken) {
    $this->accessToken = $accessToken;
    $this->groupId = $groupId;
  }
  
  public function getGroupFullName() {
    $json = $this->graphQuery($this->groupId);
    if ($json) {
      return $json['name'];
    }
    return null;
  }
  
  public function getGroupPhotos() {
    $result = $this->getGroupPosts();
    //error_log(print_r($result, true));
    
    $photos = array();
    if (isset($result['data'])) {
      foreach ($result['data'] as $i => $post) {
        if ($post['type'] == 'photo') {
          
          $photos[] = $this->formatPost($post);
        }
      }
    }
    
    return $photos;
  }
  
  public function getGroupVideos() {
    $result = $this->getGroupPosts();

    $videos = array();
    foreach ($result['data'] as $i => $post) {
      if ($post['type'] == 'video') {
        $videos[] = $this->formatPost($post);
      }
    }
    
    return $videos;
  }
  
  public function getPhotoPostDetails($postId) {
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
  
  public function getVideoPostDetails($postId) {
    $post = $this->getPostDetails($postId);
    //error_log(print_r($post, true));
    
    $videoDetails = $this->formatPost($post);
    
    if (isset($post['source'])) {
      $videoDetails['embed'] = $post['source'];
    }

    if (isset($post['object_id'])) {
      $video = $this->getVideoDetails($post['object_id']);
      error_log(print_r($video, true));
    }
    
    $videoDetails['comments'] = $this->getPostComments($postId);
    
    return $videoDetails;
  }  
  
  private function getPostComments($postId) {
    $result = $this->graphQuery($postId.'/comments', array('limit' => 100));
    
    $comments = array();
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
          'time'  => $comment['created_time'],
          'delta' => self::relativeTime($comment['created_time']),
        ),
      );
    }
    
    return $comments;
  }

  private function getPostDetails($id) {
    return $this->getObjectDetails('post', $id);
  }
  private function getPhotoDetails($id) {
    return $this->getObjectDetails('photo', $id);
  }
  private function getVideoDetails($id) {
    return $this->getObjectDetails('video', $id);
  }
  private function getObjectDetails($type, $id) {
    $args = array();
    if (isset($this->queryFields[$type])) {
      $args['fields'] = implode(',', $this->queryFields[$type]);
    }
  
    return $this->graphQuery($id, $args);
  }

  private function getGroupPosts($fields=array()) {
    return $this->graphQuery($this->groupId.'/feed', array('limit' => 500));
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
        'time'  => $post['updated_time'],
        'delta' => self::relativeTime($post['updated_time']),
      ),
    );
    if (isset($post['message'])) {
      $formatted['message'] = $post['message'];
    }
    if (isset($post['picture'])) {
      $formatted['thumbnail'] = $post['picture'];
    }
    
    $comments = array();
    if (isset($post['comments'], $post['comments']['data'])) {
      foreach ($post['comments']['data'] as $comment) {
        $comments[] = array(
          'id'      => $comment['id'],
          'message' => $comment['message'],
          'author'  => array(
            'name' => $comment['from']['name'],
            'id'   => $comment['from']['id'],
            'url'  => $this->authorURL($comment['from']),
          ),
          'when'    => array(
            'time'  => $comment['created_time'],
            'delta' => self::relativeTime($comment['created_time']),
          ),
        );
      }
    }
    $formatted['comments'] = $comments;
    
    return $formatted;
  }
  
  private function authorURL($from) {
    return self::AUTHOR_URL.$from['id'];
  }
  
  private function graphQuery($path, $params=array()) {
    $result = json_decode($this->query('graph', $path, $params), true);

    if (isset($result['error'])) {
      error_log("Got Facebook graph API error: ".print_r($result['error'], true));
      return false;
    }
    
    return $result;
  }

  private function query($type, $path, $getParams=array(), $postParams=array()) {
    if (!isset($this->apiToURL[$type])) { return null; }
    
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
    
    if (!$this->cache) {
      $this->cache = new DiskCache(CACHE_DIR."/Facebook", $this->cacheLifetime, TRUE);
      $this->cache->setSuffix('.json');
      $this->cache->preserveFormat();
    }
    
    if (!count($postParams) && $this->cache->isFresh($cacheName)) {
      $result = $this->cache->read($cacheName);
      
    } else {
      // add access token to params
      $getParams['access_token'] = $this->accessToken;
      
      $getParamString = http_build_query($getParams, null, '&');
      $url = $this->apiToURL[$type].$path.($getParamString ? '?' : '').$getParamString;

      error_log("Requesting $url");
      
      $opts = $this->CURL_OPTS;
      if ($postParams) {
        error_log("With post parameters ".print_r($postParams, true));
        $opts[CURLOPT_POST] = 1;
        $opts[CURLOPT_POSTFIELDS] = $postParams;
      }
      $opts[CURLOPT_URL] = $url;
      $opts[CURLOPT_HTTPHEADER] = array('Expect:'); // disable 'Expect: 100-continue' behavior
      
      $ch = curl_init();
      curl_setopt_array($ch, $opts);
      $result = curl_exec($ch);
      curl_close($ch);
      
      if ($result !== false && $result !== 'false') {
        $this->cache->write($result, $cacheName);
        
      } else if (!count($postParams) && $result === false) {
        error_log("Request failed, reading expired cache");
        $result = $this->cache->read($cacheName);
      }
    }
    
    return $result;
  }

  public static function relativeTime($time=null, $limit=86400, $format='M j g:ia') {
    if (empty($time) || (!is_string($time) && !is_numeric($time))) {
      $time = time();
      
    } else if (is_string($time)) {
      $time = strtotime($time);
    }
    
    $now = time();
    $relative = '';
    
    $diff = $now - $time;
    
    if ($diff >= $limit) {
      $relative = date($format, $time);
      
    } else if ($diff < 0) {
      $relative = 'in the future';
      
    } else if ($diff == 0) {
      $relative = 'now';
      
    } else if ($diff < 60) {
      $relative = 'less than one minute ago';
      
    } else if (($minutes = ceil($diff / 60)) < 60) {
      $relative = $minutes.' minute'.($minutes == 1 ? '' : 's').' ago';
      
    } else {
      $hours = ceil($diff / 3600);
      $relative = 'about '.$hours.' hour'.($hours == 1 ? '' : 's').' ago';
    }
    
    return $relative;
  }
}
