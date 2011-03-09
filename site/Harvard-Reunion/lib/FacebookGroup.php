<?php

class FacebookGroup {
  private $accessToken = null;
  private $groupId = null;
  private $CURL_OPTS = array(
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_USERAGENT      => 'facebook-php-2.0',
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 2,
  );
  const GRAPH_API_URL = "https://graph.facebook.com/";
  const LOGOUT_URL = "https://www.facebook.com/logout.php";
  const AUTHOR_URL = "http://m.facebook.com/profile.php?id=";

  function __construct($groupId, $accessToken) {
    $this->accessToken = $accessToken;
    $this->groupId = $groupId;
  }
  
  public function getUser() {
    $result = $this->graphQuery('me');
    if ($result && isset($result['name'])) {
      return $result['name'];
    }
    
    return null;
  }

  public function getGroupFullName() {
    $json = $this->graphQuery($this->groupId);
    if ($json) {
      return $json['name'];
    }
    return null;
  }
  
  public function getGroupPhotos() {
    $result = $this->getGroupPosts($this->groupId);

    $photos = array();
    foreach ($result['data'] as $i => $post) {
      if ($post['type'] == 'photo') {
        
        $photos[] = $this->formatPost($post);
      }
    }
    
    return $photos;
  }
  
  public function getGroupVideos() {
    $result = $this->getGroupPosts($this->groupId);

    $videos = array();
    foreach ($result['data'] as $i => $post) {
      if ($post['type'] == 'video') {
        
        $videos[] = $this->formatPost($post);
      }
    }
    
    return $videos;
  }
  
  public function getPhotoPostDetails($postId) {
    $post = $this->graphQuery($postId);
    
    $photoDetails = $this->formatPost($post);
    
    if (isset($post['object_id'])) {
      $photo = $this->graphQuery($post['object_id']);
      
      if (isset($photo['source'])) {
        $photoDetails['img'] = $photo['source'];
      }
    }
    
    $photoDetails['comments'] = $this->getPostComments($postId);
    
    return $photoDetails;
  }
  
  private function getPostComments($postId) {
    $result = $this->graphQuery($postId.'/comments');
    
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

  private function getGroupPosts() {
    $json = $this->graphQuery($this->groupId.'/feed');
    
    return $json;
  }
  
  private function formatPost($post) {
    $formatted = array(
      'id'    => $post['id'],
      'author'    => array(
        'name' => $post['from']['name'],
        'id'   => $post['from']['id'],
        'url'  => $this->authorURL($post['from']),
      ),
      'link'  => $post['link'],
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
    if (isset($post['actions'])) {
      $formatted['actions'] = $post['actions'];
    }
    
    return $formatted;
  }
  
  private function authorURL($from) {
    return self::AUTHOR_URL.$from['id'];
  }
  
  private function graphQuery($path, $params=array()) {
    $params['method'] = 'GET';
  
    $result = json_decode($this->query(self::GRAPH_API_URL.$path, $params), true);

    if (isset($result['error'])) {
      error_log("Got Facebook graph API error: ".print_r($result['error'], true));
      return false;
    }
    
    return $result;
  }

  private function query($url, $params=array(), $uploadingFile=false) {
    // add access token
    $params['access_token'] = $this->accessToken;

    // json_encode all params values that are not strings
    foreach ($params as $key => $value) {
      if (!is_string($value)) {
        $params[$key] = json_encode($value);
      }
    }

    $opts = $this->CURL_OPTS;
    if ($uploadingFile) {
      $opts[CURLOPT_POSTFIELDS] = $params;
    } else {
      $opts[CURLOPT_POSTFIELDS] = http_build_query($params, null, '&');
    }
    $opts[CURLOPT_URL] = $url;
    
    error_log("$url?".http_build_query($params, null, '&'));

    // disable the 'Expect: 100-continue' behaviour. This causes CURL to wait
    // for 2 seconds if the server does not support this header.
    $opts[CURLOPT_HTTPHEADER] = array('Expect:');

    $ch = curl_init();
    curl_setopt_array($ch, $opts);
    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result;
  }
  
  
  public function getLogoutUrl($redirectToURL) {
    if (strpos($redirectToURL, 'http') === FALSE) {
      $redirectToURL = FULL_URL_PREFIX . ltrim($redirectToURL, '/');
    }
    
    return self::LOGOUT_URL.'?'.http_build_query(array(
      'next'         => $redirectToURL,
      'access_token' => $this->accessToken,
    ));
  }

  public static function relativeTime($time=null, $limit=86400, $format='g:i A M jS') {
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
