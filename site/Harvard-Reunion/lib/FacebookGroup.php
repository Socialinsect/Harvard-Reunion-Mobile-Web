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

  function __construct($groupId, $accessToken) {
    $this->accessToken = $accessToken;
    $this->groupId = $groupId;
  }
  
  public function getFacebookUser() {
    $result = $this->graphQuery('me');error_log(print_r($result, true));
    if ($result && isset($result['name'])) {
      return $result['name'];
    }
    
    return null;
  }

  public function getFacebookGroupFullName() {
    $json = $this->graphQuery($this->groupId);
    if ($json) {
      return $json['name'];
    }
    return null;
  }
  
  public function getFacebookGroupPhotos() {
    $result = $this->getFacebookGroupPosts($this->groupId);

    $photos = array();
    foreach ($result['data'] as $i => $post) {
      if ($post['type'] == 'photo') {
        error_log(print_r($post, true));
        $img = $this->graphQuery($post['object_id']);
        
        $photos[] = array(
          'title'     => $post['message'],
          'author'    => $post['from']['name'],
          'authorId'  => $post['from']['id'],
          'url'       => $post['link'],
          'thumbnail' => $post['picture'],
          'img'       => $img['source'],
          'time'      => $post['updated_time'],
          'actions'   => $post['actions'],
        );
      }
    }
    error_log(print_r($photos, true));
    
    return $photos;
  }

  private function getFacebookGroupPosts() {
    $json = $this->graphQuery($this->groupId.'/feed');
    
    return $json;
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
  error_log($redirectToURL);
    return self::LOGOUT_URL.'?'.http_build_query(array(
      'next'         => $redirectToURL,
      'access_token' => $this->accessToken,
    ));
  }

}
