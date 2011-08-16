<?php

/****************************************************************
 *
 *  Copyright 2011 The President and Fellows of Harvard College
 *  Copyright 2011 Modo Labs Inc.
 *
 *****************************************************************/

class TwitterHashtag {
  const SEARCH_URL = 'http://search.twitter.com/search.json';
  protected $hashtag = '';
  protected $cache;
  protected $cacheLifetime = 60;
  
  function __construct($hashtag) {
    $this->hashtag = $hashtag;
  }
  
  public function getTweetURL() {
    return 'http://mobile.twitter.com/?'.http_build_query(array(
      'status' => $this->hashtag,
    ), null, '&');
  }
  
  public function getFeedURL() {
    return 'http://mobile.twitter.com/searches?'.http_build_query(array(
      'q' => $this->hashtag,
    ), null, '&');
  }
  
  public function getRecentTweets() {
    $cacheName = "search_{$this->hashtag}";
  
    if (!$this->cache) {
      $this->cache = new DiskCache(CACHE_DIR."/Twitter", $this->cacheLifetime, TRUE);
      $this->cache->setSuffix('.json');
      $this->cache->preserveFormat();
    }
    
    $content = '';
    if ($this->cache->isFresh($cacheName)) {
      $content = $this->cache->read($cacheName);
      
    } else {
      $maxTweets = FacebookGroup::getMaxPostCount();

      $url = self::SEARCH_URL.'?'.http_build_query(array(
        'q'           => $this->hashtag,
        'result_type' => 'recent',
        'rpp'         => $maxTweets,
      ), null, '&');
      error_log("Requesting {$url}");
      
      $content = @file_get_contents($url);
      $this->cache->write($content, $cacheName);
    }
      
    $json = json_decode($content, true);
    
    $tweets = array();
    if (is_array($json) && isset($json['results'])) {
      foreach ($json['results'] as $result) {
        $tweets[] = array(
          'id'      => number_format($result['id'], 0, '.', ''),
          'message' => $result['text'],
          'author'  => array(
            'name'  => $result['from_user'],
            'id'    => $result['from_user_id'],
            'photo' => $result['profile_image_url'],
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
}
