<?php

/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

require_once realpath(SITE_LIB_DIR.'/facebook-php-sdk/src/facebook.php');

class FacebookGroup {
  private $facebook = null;
  private $groupId = null;
  private $oldGroup = false;
  private $newStyle = true;
  private $loginFailedURL = '';

  private $myId = null;
  
  const FEED_LIFETIME = 60;
  const OBJECT_LIFETIME = 3600;
  const PLACE_LIFETIME = 14400;
  const NOCACHE_LIFETIME = 0;
  const ALL_FIELDS = null;
  
  private $queryConfig = array(
    'object' => array(         // default, no cache
      'cache'         => null,
      'cacheLifetime' => self::NOCACHE_LIFETIME,
      'suffix'        => '',
      'fields'        => self::ALL_FIELDS,
    ),
    'user' => array(
      'cache'         => null,
      'cacheLifetime' => self::OBJECT_LIFETIME,
      'suffix'        => '',
      'fields'        => self::ALL_FIELDS,
    ),
    'usergroups' => array(
      'cache'         => null,
      'cacheLifetime' => self::NOCACHE_LIFETIME,
      'suffix'        => '/groups',
    ),
    'group' => array(
      'cache'         => null,
      'cacheLifetime' => self::OBJECT_LIFETIME,
      'suffix'        => '',
      'fields'        => array('name'),
    ),
    'post' => array(
      'cache'         => null,
      'cacheLifetime' => self::OBJECT_LIFETIME,
      'suffix'        => '',
      'fields'        => self::ALL_FIELDS, // doesn't work with 'picture' field
    ),
    'photo' => array(
      'cache'         => null,
      'cacheLifetime' => self::OBJECT_LIFETIME,
      'suffix'        => '',
      'fields'        => null,
    ),
    'feed' => array(
      'cache'         => null,
      'cacheLifetime' => self::FEED_LIFETIME,
      'suffix'        => '/feed',
    ),
    'feedOrder' => array(
      'cache'         => null,
      'cacheLifetime' => self::FEED_LIFETIME,
      'suffix'        => '',
    ),
    'photos' => array(
      'cache'         => null,
      'cacheLifetime' => self::FEED_LIFETIME,
      'suffix'        => '',
      'perUser'       => true,
    ),
    'comments' => array(
      'cache'         => null,
      'cacheLifetime' => self::FEED_LIFETIME,
      'suffix'        => '/comments',
    ),
    'likes' => array(
      'cache'         => null,
      'cacheLifetime' => self::FEED_LIFETIME,
      'suffix'        => '/likes',
    ),
    'checkins' => array(
      'cache'         => null,
      'cacheLifetime' => self::FEED_LIFETIME,
      'suffix'        => '/checkins',
    ),
    'place' => array(
      'cache'         => null,
      'cacheLifetime' => self::PLACE_LIFETIME,
      'suffix'        => '',
      'fields'        => self::ALL_FIELDS,
    ),
    'searchPlaces' => array(
      'cache'         => null,
      'cacheLifetime' => self::PLACE_LIFETIME,
      'suffix'        => '/search',
    ),
    'youtube' => array(
      'cache'         => null,
      'cacheLifetime' => self::OBJECT_LIFETIME,
      'suffix'        => '',
      'fields'        => self::ALL_FIELDS,
    ),
    'vimeo' => array(
      'cache'         => null,
      'cacheLifetime' => self::OBJECT_LIFETIME,
      'suffix'        => '',
      'fields'        => self::ALL_FIELDS,
    ),
  );
  const AUTHOR_URL    = 'http://m.facebook.com/profile.php?id=';
  const OLD_DESKTOP_GROUP_URL = 'http://www.facebook.com/group.php?gid=';
  const NEW_DESKTOP_GROUP_URL = 'http://www.facebook.com/home.php?sk=group_';
  const OLD_MOBILE_GROUP_URL = 'http://m.facebook.com/group.php?gid=';
  const NEW_MOBILE_GROUP_URL = 'http://m.facebook.com/home.php?sk=group_';
  
  public static function appLogout() {
    $facebook = new ReunionFacebook(array(
      'appId'  => Kurogo::getSiteVar('FACEBOOK_APP_ID'),
      'secret' => Kurogo::getSiteVar('FACEBOOK_APP_SECRET'),
      'cookie' => true,
    ));
    
    $facebook->expireSession();
  }
  
  function __construct($groupId, $isOldGroup) {
    $this->facebook = new ReunionFacebook(array(
      'appId'  => Kurogo::getSiteVar('FACEBOOK_APP_ID'),
      'secret' => Kurogo::getSiteVar('FACEBOOK_APP_SECRET'),
      'cookie' => true,
    ));

    $this->groupId  = $groupId;
    $this->oldGroup = $isOldGroup;
  }
  
  public function needsLogin() {
    return $this->facebook->getSession() == null;
  }
  
  public function getSwitchUserURL() {
    return $this->facebook->getSwitchUserURL();
  }
  
  public function getLoginURL() {
    return $this->facebook->getLoginURL();
  }
  
  public function authorize($redirectURL, $code) {
    return $this->facebook->authorize($redirectURL, $code);
  }
  
  public function getLogoutURL() {
    return $this->facebook->getLogoutUrl();
  }
  
  public function getLogoutRedirectURL($redirectURL) {
    return $this->facebook->getLogoutRedirectURL($redirectURL);
  }
  
  public function expireSession() {
    return $this->facebook->expireSession();
  }

  public function getMyId() {
    return $this->facebook->getUser();
  }
  
  public function isMemberOfGroup() {
    $results = $this->graphQuery('usergroups', $this->getMyId());
    
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
    $results = $this->graphQuery('user', $this->getMyId());
    if ($results) {
      return $results['name'];
    }
    return 'error';
    
  }
  
  public function getGroupFullName() {
    $results = $this->graphQuery('group', $this->groupId);
    if ($results) {
      return $results['name'];
    }
    return null;
  }
  
  public function getGroupURL($isJoinLink=false) {
    $groupURL = $this->oldGroup ? 
      self::OLD_MOBILE_GROUP_URL : self::NEW_MOBILE_GROUP_URL;
    
    if ($isJoinLink && $GLOBALS['deviceClassifier']->getPagetype() == 'compliant') {
      // Facebook's mobile web site is lame and doesn't have a join link
      // Direct phones who have a chance of displaying the real website to 
      // it instead
      $groupURL = $this->oldGroup ? 
        self::OLD_DESKTOP_GROUP_URL : self::NEW_DESKTOP_GROUP_URL;
    }
    
    return $groupURL.$this->groupId;
  }
  
  //
  // Feed
  // 
  
  public function addPost($message) {
    $results = $this->graphQuery('feed', $this->groupId, 'POST', array('message' => $message));
  }
  
  private function getGroupPosts() {
    return $this->graphQuery('feed', $this->groupId, array('limit' => 1000));
  }

  
  public function getGroupStatusMessages() {
    $results = $this->getGroupPosts();
    //error_log(print_r($results, true));
    
    $maxPosts = self::getMaxPostCount();
    $count = 0;

    $statuses = array();
    if (isset($results['data'])) {
      foreach ($results['data'] as $i => $post) {
        if ($post['type'] == 'status') {
          $statuses[] = $this->formatPost($post);
          if (++$count >= $maxPosts) { break; }
        }
      }
    }
    
    return $statuses;
  }
  
  private static function positionSort($a, $b) {
    return intval($a['position']) - intval($b['position']);
  }
 
  private function getGroupPhotoObjects() {
    $results = $this->fqlQuery('photos', 
      "SELECT pid, aid, owner, object_id, src, link, caption FROM photo WHERE pid IN (SELECT pid FROM photo_tag WHERE subject = {$this->groupId} LIMIT 1000) LIMIT 1000");
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
  
  public function getGroupPhotos() {
    if ($this->oldGroup) {
      // In old groups, photos are only objects
      $photos = $this->getGroupPhotoObjects();
      
    } else {
      // in new groups, photos are in posts
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
  
  //
  // Posts
  // 
    
  public function getPhotoPost($postId) {
    if ($this->oldGroup) {
      // In old groups, photos are only objects
      $photo = $this->getPhotoDetails($postId);
      //error_log(print_r($photo, true));
      
      $photoDetails = $this->formatPhoto($photo);

      if (isset($photo['source'], $photo['height'], $photo['width'])) {
        $photoDetails['img']['src'] = $photo['source'];
        $photoDetails['img']['height'] = $photo['height'];
        $photoDetails['img']['width'] = $photo['width'];
      }
      
    } else {
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
    
  private function getPostDetails($objectId) {
    $postDetails = $this->graphQuery('post', $objectId);
    
    // Although there are comments and likes available here do not add them
    // The cache lifetimes on the posts themselves are much longer than 
    // the lifetime on the comment and like feeds.  We would suppress these
    // with the fields parameter but there is a bug with the 'pictures' field
    if (isset($postDetails['comments'])) {
      unset($postDetails['comments']);
    }
    if (isset($postDetails['likes'])) {
      unset($postDetails['likes']);
    }
  
    return $postDetails;
  }
  
  //
  // Comments
  //
  
  public function addComment($objectId, $message) {
    $results = $this->graphQuery('comments', $objectId, 'POST', array('message' => $message));
  }
  
  public function removeComment($commentId) {
    $results = $this->graphQuery('object', $commentId, 'DELETE');
  }
  
  public function getComments($objectId) {
    $results = $this->graphQuery('comments', $objectId);
    
    $maxComments = self::getMaxPostCount();
    $count = 0;
    
    $comments = array();
    if (isset($results['data'])) {
      foreach ($results['data'] as $comment) {
        $comments[] = $this->formatComment($comment);
        if (++$count >= $maxComments) { break; }
      }
    }
    
    return $comments;
  }
  
  //
  // Checkins
  //
  
  public function addCheckin($placeId, $message, $coords=null) {
    if (!$coords) {
      $results = $this->graphQuery('place', $placeId);
      if (isset($results['location'], $results['location']['latitude'])) {
        // Is a place page
        $coords = array($results['location']['latitude'], $results['location']['longitude']);
        error_log('Using place coords '.implode(',', $coords));
        
      } else if (isset($results['venue'], $results['venue']['latitude'])) {
        // Is an event
        $coords = array($results['venue']['latitude'], $results['venue']['longitude']);
        error_log('Using event venue coords '.implode(',', $coords));
      }
    }
    if ($coords) {
      $results = $this->graphQuery('checkins', $this->getMyId(), 'POST', array(
        'message'     => $message,
        'place'       => $placeId,
        'coordinates' => json_encode(array(
          'latitude'  => $coords[0],
          'longitude' => $coords[1],
        )),
      ));
    }
  }
  
  public function isCheckedIn($placeId, $since=null) {
    $results = $this->graphQuery('checkins', $this->getMyId());
    //error_log(print_r($results, true));
    
    if (isset($results['data'])) {
      if (!$since) { $since = time() - 60*60; }
      foreach ($results['data'] as $checkin) {
        $created = strtotime($checkin['created_time']);
      
        if ($created > $since && $checkin['place']['id'] == $placeId) {
          //error_log(print_r($checkin, true));
          return true;
        }
      }
    }
    
    return false;
  }
  
  //
  // Places
  //
  
  public function findPlaces($placeTitle, $coords) {
    $args = array(
      'type'     => 'place',
      'center'   => implode(',', $coords),
      'distance' => '1000',
    );
    if ($placeTitle) {
      $args['q'] = $placeTitle;
    }
    
    $results = $this->graphQuery('searchPlaces', '', 'GET', $args);
    //error_log(print_r($results, true));
    
    $places = array();
    if (isset($results['data'])) {
      foreach ($results['data'] as $data) {
        $places[] = array(
          'id'       => $data['id'],
          'title'    => $data['name'],
          'category' => $data['category'],
          'coords'   => array($data['location']['latitude'], $data['location']['longitude']),
        );
      }
    }
    
    return $places;
  }
  
  //
  // Likes
  //
  
  public function like($objectId) {
    $results = $this->graphQuery('likes', $objectId, 'POST');
  }
  
  public function unlike($objectId) {
    $results = $this->graphQuery('likes', $objectId, 'DELETE');
  }
  
  public function getLikes($objectId) {
    $results = $this->graphQuery('likes', $objectId);
        
    return isset($results['data']) ? $results['data'] : array();
  }
  
  
  //
  // Post Order
  //
  
  public function getGroupPhotoOrder() {
    if ($this->oldGroup) {
      // In old groups, photos are only objects
      return $this->getGroupPhotoObjects();
      
    } else {
      return $this->getGroupPostOrder('photo');
    }
  }
  
  public function getGroupVideoOrder() {
    return $this->getGroupPostOrder('video');
  }

  private function getGroupPostOrder($type=null) {
    $results = $this->fqlQuery(
      'feedOrder', 
      "SELECT post_id,actor_id,attachment FROM stream WHERE source_id={$this->groupId} LIMIT 1000", 
      $this->groupId);
    //error_log(print_r($results, true));
    
    $posts = array();
    foreach ($results as $result) {
      $post = array(
        'id'     => $result['post_id'],
        'type'   => 'status',
        'author' => array(
          'id'    => $result['actor_id'],
          'photo' => $this->photoURLForUserID($result['actor_id']),
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
  
  //
  // Photos
  //
  
  private function photoURLForUserID($id) {
    return "https://graph.facebook.com/{$id}/picture?type=square";
  }
  
  private function getPhotoDetails($objectId) {
    return $this->graphQuery('photo', $objectId);
  }

  //
  // Formatting
  //

  private function formatPost($post) {
    $formatted = array();
    
    if (isset($post['id'])) {
      $formatted['id'] = $post['id'];
    }
    if (isset($post['from'])) {
      $formatted['author'] = array(
        'name'  => $post['from']['name'],
        'id'    => $post['from']['id'],
        'photo' => $this->photoURLForUserID($post['from']['id']),
        'url'   => $this->authorURL($post['from']),
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
    if (!isset($post['message']) || 
        (isset($post['link']) && $post['message'] == $post['link'])) {
      if (isset($post['description'])) {
        $formatted['message'] = $post['description'];
      } else {
        unset($formatted['message']);
      }
    }
    
    if (isset($post['picture'])) {
      $formatted['thumbnail'] = $post['picture']; // only in group feed
    }
    
    return $formatted ? $formatted : false;
  }
  
  private function formatPhoto($photo) {
    $formatted = array(
      'type' => 'photo',
    );
    
    if (isset($photo['id'])) {
      $formatted['id'] = $photo['id'];
      $formatted['position'] = isset($photo['position']) ? $photo['position'] : PHP_INT_MAX;
    }
    if (isset($photo['from'])) {
      $formatted['author'] = array(
        'name'  => $photo['from']['name'],
        'id'    => $photo['from']['id'],
        'photo' => $this->photoURLForUserID($photo['from']['id']),
        'url'   => $this->authorURL($photo['from']),
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
  
  private function formatComment($comment) {
    return array(
      'id'      => $comment['id'],
      'message' => $comment['message'],
      'author'  => array(
        'name'  => $comment['from']['name'],
        'id'    => $comment['from']['id'],
        'photo' => $this->photoURLForUserID($comment['from']['id']),
        'url'   => $this->authorURL($comment['from']),
      ),
      'when'    => array(
        'time'       => strtotime($comment['created_time']),
        'delta'      => self::relativeTime($comment['created_time']),
        'shortDelta' => self::relativeTime($comment['created_time'], true),
      ),
    );
  }
  
  private function authorURL($from) {
    return self::AUTHOR_URL.$from['id'];
  }
  
  public static function getMaxPostCount() {
    $maxPosts = 100;
    
    switch ($GLOBALS['deviceClassifier']->getPagetype()) {
      case 'compliant':
        $maxPosts = 200;
        break;
        
      case 'touch':
        $maxPosts = 100;
        break;
        
      case 'basic':
        $maxPosts = 50;
        break;
    }
    
    return $maxPosts;
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
      $format = $shortFormat ? 'M j' : 'M j g:ia';
      $relative = date($format, $time);
      
    } else if ($diff < 0) {
      $relative = 'in the future';
      
    } else if ($diff == 0) {
      $relative = 'now';
      
    } else if ($diff < 60) {
      $relative = $shortFormat ? 'moments ago' : 'less than one minute ago';
      
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
    
    $platform = $GLOBALS['deviceClassifier']->getPlatform();
    $needsLink = $platform != 'iphone' && $platform != 'ipad' && $platform != 'android';

    if ($isObject) {
      if ($needsLink) {
        $html = $this->buildLink($source, $post['picture']);
      } else {
        $html = '<video src="'.$source.'" controls>Video format not supported by this device</video>';
      }
      
    } else if (preg_match(';^http://www.youtube.com/v/([^&?]+).*$;', $source, $matches)) {
      $videoID = $matches[1];

      if ($needsLink) {
        $videoInfo = $this->getYouTubeData($videoID);
        //error_log(print_r($videoInfo, true));

        $src = 'http://m.youtube.com/watch?v='.$videoID;
        $img = $post['picture'];
        if (isset($videoInfo['data'])) {
          if (isset($videoInfo['data']['thumbnail'])) {
            if (isset($videoInfo['data']['thumbnail']['hqDefault'])) {
              $img = $videoInfo['data']['thumbnail']['hqDefault'];
              
            } else if (isset($videoInfo['data']['thumbnail']['sqDefault'])) {
              $img = $videoInfo['data']['thumbnail']['sqDefault'];
            }
          }
        }
        
        $html = $this->buildLink($src, $img);
        
      } else {
        $html = '<iframe id="videoFrame" class="youtube-player" src="http://www.youtube.com/embed/'.$videoID.
          '" width="240" height="195" frameborder="0"></iframe>';
      }
    
    } else if (preg_match(';clip_id=([^&]+);', $source, $matches)) {
      $videoID = $matches[1];
      $videoInfo = $this->getVimeoData($videoID);
      //error_log(print_r($videoInfo, true));
      
      if (!$needsLink && isset($videoInfo, $videoInfo[0], $videoInfo[0]['width'], $videoInfo[0]['height'])) {
        $html = '<iframe id="videoFrame" src="http://player.vimeo.com/video/'.$videoID.
          '" width="'.$videoInfo[0]['width'].'" height="'.$videoInfo[0]['height'].
          '" frameborder="0"></iframe>';
          
      } else {
        $src = 'http://www.vimeo.com/m/#/'.$videoID;
        $img = $post['picture'];
        if (isset($videoInfo[0])) {
          if (isset($videoInfo[0]['mobile_url'])) {
            $src = $videoInfo[0]['mobile_url'];
          }
          if (isset($videoInfo[0]['thumbnail_large'])) {
            $img = $videoInfo[0]['thumbnail_large'];
          }
        }
        
        $html = $this->buildLink($src, $img);
      }
    }
    
    return $html;
  }
  
  private function buildLink($src, $img) {
    return '<a class="videoLink" href="'.$src.'"><div class="playButton"><div></div></div><img src="'.$img.'" alt="Video" /></a>';
  }
  
  private function getYouTubeData($id) {
    $cache = $this->getCacheForQuery('youtube');
    $cacheName = $id;
    
    if ($cache->isFresh($cacheName)) {
      $results = $cache->read($cacheName);
    } else {
      $url = 'http://gdata.youtube.com/feeds/mobile/videos/'.$id.'?'.http_build_query(array(
        'v'      => 2,
        'format' => 6, // RTSP streaming URL for mobile video playback
        'alt'    => 'jsonc',
      ));
      
      $results = json_decode(file_get_contents($url), true);
      if (isset($results['data'])) {
        $cache->write($results, $cacheName);
      }
    }
    
    return $results;
  }
  
  private function getVimeoData($id) {
    $cache = $this->getCacheForQuery('vimeo');
    $cacheName = $id;
    
    if ($cache->isFresh($cacheName)) {
      $results = $cache->read($cacheName);
    } else {
      $url = 'http://vimeo.com/api/v2/video/'.$id.'.json';
      
      $results = json_decode(file_get_contents($url), true);
      if (isset($results[0])) {
        $cache->write($results, $cacheName);
      }
    }
    
    return $results;
  }
   //
  // Query utility functions
  //

  private function getCacheForQuery($type) {
    if (!$this->queryConfig[$type]['cache'] && $this->queryConfig[$type]['cacheLifetime'] > 0) {
      $this->queryConfig[$type]['cache'] = new DiskCache(
        CACHE_DIR."/Facebook/$type", $this->queryConfig[$type]['cacheLifetime'], TRUE);
    }
    
    return $this->queryConfig[$type]['cache'];
  }
  
  private function getExceptionMessage($e) {
    $message = $e->getMessage();
    if (get_class($e) == 'FacebookApiException') {
      $message = print_r($e->getResult(), true);
    }      
    return $message;
  }
  
  private function applyQueryParameters($type, &$params) {
    if (isset($this->queryConfig[$type]['fields'])) {
      $params['fields'] = implode(',', $this->queryConfig[$type]['fields']);
    }
  }
  
  private function shouldCacheResultsForQuery($type, $results) {
    switch ($type) {
      case 'feed':
      case 'comments':
      case 'likes':
        return isset($results, $results['data']) && is_array($results['data']) && count($results['data']);
      
      case 'group':
      case 'user':
      case 'post':
      case 'photo':
      case 'video':
        return isset($results, $results['id']) && $results['id'];
        
      case 'feedOrder':
        return is_array($results) && count($results);
    }
    
    return isset($results) && $results;
  }

  //
  // Queries
  // 
  
  private function graphQuery($type, $id, $method='GET', $params=array()) {
    $results = array();
  
    if (is_array($method) && empty($params)) {
      $params = $method;
      $method = 'GET';
    }

    $cache = $this->getCacheForQuery($type);

    $path = $id.$this->queryConfig[$type]['suffix'];
    $cacheName = $id ? $id : http_build_query($params, null, '&');

    $shouldCache = $cache && $method == 'GET';
    $invalidateCache = $cache && $method != 'GET';
    
    if ($shouldCache && $cache->isFresh($cacheName)) {
      $results = $cache->read($cacheName);
    
    } else {
      try {
        $this->applyQueryParameters($type, $params);
        
        $results = $this->facebook->api($path, $method, $params);
        
        if ($shouldCache) {
          if ($this->shouldCacheResultsForQuery($type, $results)) {
            $cache->write($results, $cacheName);
            
          } else {
            error_log("Facebook Graph API request for $type '{$id}' returned empty data");
            $results = $cache->read($cacheName);
          }
          
        } else if ($invalidateCache) {
          $cacheFile = $cache->getFullPath($cacheName);
          if (file_exists($cacheFile)) {
            error_log("Removing invalidated cache file '$cacheFile'");
            @unlink($cacheFile);
          }
        }
        
      } catch (FacebookApiException $e) {
        error_log("Error while making Facebook Graph API request: ".$this->getExceptionMessage($e));
        $results = $shouldCache ? $cache->read($cacheName) : array();
      }
    }
    
    return $results ? $results : array();
  }
  
  private function fqlQuery($type, $query, $cacheSuffix='') {
    $results = array();
  
    $cache = $this->getCacheForQuery($type);
    $cacheName = ($cacheSuffix ? $cacheSuffix : md5($query));
    if (isset($this->queryConfig[$type], 
              $this->queryConfig[$type]['perUser']) && 
        $this->queryConfig[$type]['perUser']) {
      $cacheName .= '_'.$this->getMyId();
    }
    
    if ($cache->isFresh($cacheName)) {
      $results = $cache->read($cacheName);
    
    } else {
      try {
        $results = $this->facebook->fql($query);
        
        if ($this->shouldCacheResultsForQuery($type, $results)) {
          $cache->write($results, $cacheName);
        } else {
          error_log("Facebook FQL request for '$path' returned empty data");
        }
        
      } catch (Exception $e) {
        error_log("Error while making Facebook FQL request: ".$this->getExceptionMessage($e));
        
        $results = $cache->read($cacheName);
      }
    }
    
    return $results ? $results : array();
  }  
}

class ReunionFacebook extends Facebook {
  private $perms = array(
    'user_groups',
    'user_photos',
    'user_videos',
    'read_stream',
    'publish_stream',
  );
  protected $cache;
  protected $cacheLifetime = 60;
  
  public function __construct($config) {
    parent::__construct($config);

    self::$CURL_OPTS[CURLOPT_CONNECTTIMEOUT] = 20;
    //self::$CURL_OPTS[CURLOPT_SSL_VERIFYPEER] = false;
    //self::$CURL_OPTS[CURLOPT_SSL_VERIFYHOST] = 2;
    
    $hostnameParts = explode('.', $_SERVER['HTTP_HOST']);
    array_shift($hostnameParts);
    $domain = implode('.', $hostnameParts);
    $this->setBaseDomain($domain);
  }
  
  private function getDisplayType() {
    switch ($GLOBALS['deviceClassifier']->getPageType()) {
      case 'basic':
      case 'touch':
        return 'wap';
        
      case 'tablet':
        return 'page';
      
      default:
        return 'wap';//'touch';  // touch ui currently causing infinite redirects
    }
  }
  
  public function getUser() {
    $session = $this->getSession();
    
    if ($session && $session['uid'] == 'me') {
      try {
        $userInfo = $this->api('me', array(
          'fields' => 'id',
        ));
      } catch (Exception $e) {
        error_log("Failed to get user id");
        $userInfo = null;
      }
      
      if ($userInfo) {
        $session['uid'] = $userInfo['id'];
        
        unset($session['sig']);
        $session['sig'] = self::generateSignature(
          $session,
          $this->getApiSecret()
        );
        //error_log('Saving session with new uid '.print_r($session, true));
        $this->setSession($session);
      }
    }
    
    return $session ? $session['uid'] : null;
  }
  
  public function getSwitchUserURL($needsLoginURL='') {
    $loginURL = $this->getLoginUrl();
    
    return $this->getLogoutUrl(array(
      'next' => $loginURL,
    ));
  }
  
  // Use new oauth 2.0 dialog for mobile web
  public function getLoginUrl($params=array()) {
    $paramRemap = array(
      'next'      => 'redirect_uri',
      'req_perms' => 'scope',
    );
    foreach ($paramRemap as $old => $new) {
      if (isset($params[$old]) && !isset($params[$new])) {
        $params[$new] = $params[$old];
        unset($params[$old]);
      }
    }
    
    $redirectURL = $this->getCurrentUrl();
    if (isset($params['redirect_uri'])) {
      $redirectURL = $params['redirect_uri'];
      unset($params['redirect_uri']);
    }
    
    return 'https://www.facebook.com/dialog/oauth?'.http_build_query(
      array_merge(array(
        'display'      => $this->getDisplayType(),
        'client_id'    => $this->getAppId(),
        'redirect_uri' => $this->authorizeURL($redirectURL),
        'scope'        => implode(',', $this->perms),
      ), $params), null, '&');
  }
  
  public function authorize($redirectURL, $code) {
    if ($code) {
      // must use the same redirect url passed into the first oauth call
      $url = 'https://graph.facebook.com/oauth/access_token?'.http_build_query(
        array(
          'client_id'     => $this->getAppId(),
          'client_secret' => $this->getApiSecret(),
          'code'          => $code,
          'redirect_uri'  => $this->authorizeURL($redirectURL),
        ), null, '&');
      $results = @file_get_contents($url);
      
      $parts = array();
      parse_str($results, $parts);
      
      if (isset($parts['access_token'], $parts['expires'])) {
        // got an access token
        $session = array(
          'uid'          => 'me', // Lazy load... see getUser() override
          'access_token' => $parts['access_token'],
          'expires'      => time() + $parts['expires'],
        );
    
        // put a real sig, so that validateSignature works
        $session['sig'] = self::generateSignature(
          $session,
          $this->getApiSecret()
        );
        //error_log('Saving session '.print_r($session, true));
        $this->setSession($session);
      }
    } 
  }
  
  private function authorizeURL($redirectURL) {
    return FULL_URL_PREFIX.'home/fbLogin?'.http_build_query(array(
      'url' => $redirectURL
    ), null, '&');
  }

  // Do not set access_token or Facebook won't log you out
  public function getLogoutUrl($params=array()) {
    return FULL_URL_PREFIX.'home/fbLogout?'.http_build_query(
      array_merge(array(
        'next' => $this->getCurrentUrl(),
      ), $params), null, '&');
  }
  
  public function getLogoutRedirectURL($redirectURL) {
    return $this->getUrl(
      'www',
      'logout.php',
      array(
        // Do not include access token or api key
        // We want to log the user completely out
        'next' => $redirectURL,
      )
    );
  }
  
  public function expireSession() {
    $this->setSession(null);
  }
  
  public function fql($query, $format='json') {
    $result = json_decode($this->_oauthRequest(
      'https://api.facebook.com/method/fql.query', array(
        'query'  => $query,
        'format' => 'json',
      )
    ), true);

    // results are returned, errors are thrown
    if (is_array($result) && isset($result['error_code'])) {
      $e = new FacebookApiException($result);
      switch ($e->getType()) {
        // OAuth 2.0 Draft 00 style
        case 'OAuthException':
        // OAuth 2.0 Draft 10 style
        case 'invalid_token':
          $this->setSession(null);
      }
      throw $e;
    }

    return $result;
  }
  
  // Override to get a new session on demand
  protected function makeRequest($url, $params, $ch=null) {
    // Check if logged in:
    if (!$this->getSession()) {
      $loginURL = $this->getLoginURL();
      
      header("Location: $loginURL");
      exit();
    }
        
    error_log("Requesting {$url}?".http_build_query($params, null, '&'));
    return parent::makeRequest($url, $params, $ch);
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
