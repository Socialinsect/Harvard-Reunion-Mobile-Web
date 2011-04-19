<?php

class Foursquare {
  const COOKIE_NAME = 'fqReunionSession';
  private $clientId = '';
  private $clientSecret = '';
  
  const SHORT_LIFETIME = 20;
  const LONG_LIFETIME = 20;
  const NOCACHE_LIFETIME = 0;
  
  private $queryConfig = array(
    'users' => array(
      'cache'         => null,
      'cacheLifetime' => self::LONG_LIFETIME,
      'path'          => 'users/',
      'includeParams' => false,
    ),
    'venueSearch' => array(
      'cache'         => null,
      'cacheLifetime' => self::LONG_LIFETIME,
      'path'          => 'venues/search',
      'includeParams' => true,
    ),
    'venues' => array(
      'cache'         => null,
      'cacheLifetime' => self::SHORT_LIFETIME,
      'path'          => 'venues/',
      'includeParams' => false,
    ),
    'hereNow' => array(
      'cache'         => null,
      'cacheLifetime' => self::SHORT_LIFETIME,
      'path'          => 'venues/',
      'suffix'        => '/herenow',
      'includeParams' => false,
    ),
    'checkins' => array(
      'cache'         => null,
      'cacheLifetime' => self::NOCACHE_LIFETIME,
      'path'          => 'users/',
      'suffix'        => '/checkins',
      'includeParams' => false,
    ),
    'addCheckin' => array(
      'cache'         => null,
      'cacheLifetime' => self::NOCACHE_LIFETIME,
      'path'          => 'checkins/add',
      'includeParams' => true,
    ),
  );
  
  private $session = null;

  function __construct() {
    $urlPrefixes = Kurogo::getSiteVar('FOURSQUARE_FULL_URL_PREFIXES');
    
    $foundApp = false;
    foreach ($urlPrefixes as $i => $urlPrefix) {
      if (FULL_URL_PREFIX == $urlPrefix) {
        $appIds     = Kurogo::getSiteVar('FOURSQUARE_APP_IDS');
        $appSecrets = Kurogo::getSiteVar('FOURSQUARE_APP_SECRETS');
        
        $foundApp = true;
        $this->clientId = $appIds[$i];
        $this->clientSecret = $appSecrets[$i];
        
        break;
      }
    }
    
    if (!$foundApp) {
      error_log('WARNING: no foursquare app id for url '.FULL_URL_PREFIX);
    }
  }
  
  public function getUserFullName() {
    $fullName = null;
    
    $results = $this->api('users', $this->getUserId());
    //error_log(print_r($results, true));
    
    if (isset($results['response'], $results['response']['user'])) {
      $parts = array();
      
      if (isset($results['response']['user']['firstName'])) {
        $parts[] = $results['response']['user']['firstName'];
      }
      if (isset($results['response']['user']['lastName'])) {
        $parts[] = $results['response']['user']['lastName'];
      }
      if ($parts) {
        $fullName = implode(' ', $parts);
      }
    }
    
    return $fullName;
  }
  
  public function getUserId() {
    $session = $this->getSession();
    if (!isset($session['uid']) || $session['uid'] == 'self') {
      $results = $this->api('users', 'self');
      if (isset($results['response'], 
                $results['response']['user'], 
                $results['response']['user']['id'])) {
        $session['uid'] = $results['response']['user']['id'];
        $this->setSession($session);
      }
    }
    
    return isset($session['uid']) ? $session['uid'] : null;
  }
  
  public function findVenues($venueTitle, $coords) {
    $args = array(
      'll'    => implode(',', $coords),
      'llAcc' => '1000',
    );
    if ($venueTitle) {
      $args['query'] = $venueTitle;
    }
    
    $results = $this->api('venueSearch', '', 'GET', $args);
    //error_log(print_r($results, true));
    
    $venues = array();
    if (isset($results['response'], $results['response']['groups'])) {
      foreach ($results['response']['groups'] as $group) {
        if ($group['type'] == 'places') {
          foreach ($group['items'] as $item) {
            $venues[] = array(
              'id'       => $item['id'],
              'title'    => $item['name'],
              'coords'   => array($item['location']['lat'], $item['location']['lng']),
            );
          }
          break;
        }
      }
    }
    
    return $venues;
  }
  
  public function getVenueCheckinState($venueId) {
    $venueCheckinState = array(
      'checkedin'  => false,
      'checkins'   => array(),
      'otherCount' => 0,
      'friendCount' => 0,
    );

    $results = $this->api('venues', $venueId);
    //error_log(print_r($results, true));
    if (isset($results['response'],  
              $results['response']['venue'], 
              $results['response']['venue']['hereNow'], 
              $results['response']['venue']['hereNow']['groups'])) {
      
      $myId = $this->getUserId();
      
      foreach ($results['response']['venue']['hereNow']['groups'] as $group) {
        foreach ($group['items'] as $item) {
          if ($item['type'] != 'checkin' || !isset($item['user'])) { continue; }
          
          if ($item['user']['id'] == $myId) {
            $venueCheckinState['checkedin'] = true;
          } else {
            $venueCheckinState['otherCount']++;
          }
          
          $isFriend = $item['user']['id'] != $myId && $group['type'] == 'friends';
          if ($isFriend) {
            $venueCheckinState['friendCount']++;
          }
          
          $parts = array();
          if (isset($item['user']['firstName'])) {
            $parts[] = $item['user']['firstName'];
          }
          if (isset($item['user']['lastName'])) {
            $parts[] = $item['user']['lastName'];
          }
          
          $checkin = array(
            'id'     => $item['user']['id'],
            'friend' => $isFriend,
            'name'   => implode(' ', $parts),
            'when'   => array(
              'time'       => $item['createdAt'],
              'delta'      => FacebookGroup::relativeTime(intval($item['createdAt'])),
              'shortDelta' => FacebookGroup::relativeTime(intval($item['createdAt']), true),
            ),
          );
          
          if (isset($item['shout'])) {
            $checkin['message'] = $item['shout'];
          }
          
          if (isset($item['user']['photo'])) {
            $checkin['photo'] = $item['user']['photo'];
          }
          
          $venueCheckinState['checkins'][] = $checkin;
        }
      }
    }
    
    return $venueCheckinState;
  }
    
  public function addCheckin($venueId, $message, $coords=null) {
    $realVenueId = $venueId;
  
    $results = $this->api('venues', $venueId);
    //error_log(print_r($results, true));
    if (isset($results['response'], $results['response']['venue'])) {
      $realVenueId = $results['response']['venue']['id'];
      
      if (!$coords && isset($results['venue']['location']['lat'], $results['venue']['location']['lng'])) {
        // Is a place page
        $coords = array($results['venue']['location']['lat'], $results['venue']['location']['lng']);
        error_log('Using venue coords '.implode(',', $coords));
      }
    }
    
    if ($coords) {
      $results = $this->api('addCheckin', '', 'POST', array(
        'venueId'   => $realVenueId,
        'shout'     => $message, 
        'll'        => implode(',', array_values($coords)),
        'broadcast' => 'public',
      ));
      
      // invalidate caches
      $this->invalidateCache('venues', $realVenueId);
      $this->invalidateCache('hereNow', $realVenueId);
      $this->invalidateCache('checkins', $this->getUserId());
    }
  }

  public function getSession() {
    if (!$this->session) {
      if (isset($_COOKIE[self::COOKIE_NAME]) && $_COOKIE[self::COOKIE_NAME]) {
        $value = $_COOKIE[self::COOKIE_NAME];
        $session = array();
        parse_str(trim(get_magic_quotes_gpc() ? stripslashes($value) : $value, '"'), $session);
        //error_log('Loading session '.print_r($session, true));
        if (isset($session['access_token'])) {
          $this->session = $session;
        }
      }
    }
    
    return $this->session;
  }
  
  public function setSession($session) {
    if ($session) {
      $expires = time() + 60*60*24*30; // 30 days
      if (isset($session['expires'])) {
        $expires = time() + $session['expires'];
      }
      $value = '"'.http_build_query($session, null, '&').'"';
      setcookie(self::COOKIE_NAME, $value, $expires, COOKIE_PATH);
      
    } else if (isset($_COOKIE[self::COOKIE_NAME])) {
      $expires = time() - 3600;
      setcookie(self::COOKIE_NAME, '', $expires, COOKIE_PATH);
    }
  }
  
  protected function getAccessToken() {
    $session = $this->getSession();
    if ($session) {
      return $session['access_token'];
    }
    return null;
  }
  
  private function getDisplayType() {
    switch ($GLOBALS['deviceClassifier']->getPageType()) {
      case 'basic':
      case 'touch':
        return 'wap';
        
      default:
        if ($GLOBALS['deviceClassifier']->getPlatform() == 'bbplus') {
          return 'wap';
        }
        return 'touch';
    }
  }

  public function needsLogin() {
    return $this->getSession() == null;
  }
  
  protected function authorizeURL($redirectURL) {
    return FULL_URL_PREFIX.'home/fqLogin?'.http_build_query(array(
      'url' => $redirectURL,
    ));
  }
  
  public function getLoginURL($forceDialog=false) {
    // Currently this provides an annoying user experience.  It should
    // get fixed.  See the following thread for more info:
    // http://groups.google.com/group/foursquare-api/browse_thread/thread/3385c4c58fe640e/ed214b861f034299
    $page = $forceDialog ? 'authorize' : 'authenticate';
    
    return "https://foursquare.com/oauth2/$page?".http_build_query(array(
      'client_id'     => $this->clientId,
      'response_type' => 'code',
      'display'       => $this->getDisplayType(),
      'redirect_uri'  => $this->authorizeURL($this->getCurrentUrl()),
    ));
  }
  
  public function authorize($redirectURL, $code) {
    if ($code) {
      // must use the same redirect url passed into the first oauth call
      $url = 'https://foursquare.com/oauth2/access_token';
      $params = array(
        'client_id'     => $this->clientId,
        'client_secret' => $this->clientSecret,
        'grant_type'    => 'authorization_code',
        'code'          => $code,
        'redirect_uri'  => $this->authorizeURL($redirectURL),
      );

      $results = $this->query($url, 'GET', $params);
      //error_log("Request for access token returned '$results'");
      
      $results = json_decode($results, true);
      if (isset($results['access_token'])) {
        // got an access token
        //error_log('Saving session '.print_r($results, true));
        $this->setSession($results);
      }
    } 
  }
  
  public function getLogoutUrl($redirectTo='') {
    return FULL_URL_PREFIX.'home/fqLogout?'.http_build_query(array(
      'url' => $redirectTo ? $redirectTo : $this->getCurrentUrl(),
    ));
  }
  
  public function getLogoutRedirectURL($redirectURL) {
    return $redirectURL;
  }
  
  public function getUserURL() {
    switch($GLOBALS['deviceClassifier']->getPageType()) {
      case 'basic':
      case 'touch':
        $siteType = 'mobile';
        
      default:
        if ($GLOBALS['deviceClassifier']->getPlatform() == 'bbplus') {
          return 'mobile';
        }
        $siteType = 'touch';
    }
  
    return 'https://foursquare.com/'.$siteType.'/user/'.$this->getUserId();
  }
    
  protected function getCurrentUrl() {
    return 'http'.(IS_SECURE ? 's' : '').'://'.
      $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
  }
  
  private function getCacheForQuery($type) {
    if (!$this->queryConfig[$type]['cache'] && $this->queryConfig[$type]['cacheLifetime'] > 0) {
      $this->queryConfig[$type]['cache'] = new DiskCache(
        CACHE_DIR."/foursquare", $this->queryConfig[$type]['cacheLifetime'], TRUE);
    }
    
    return $this->queryConfig[$type]['cache'];
  }

  private function shouldCacheResultsForQuery($type, $results) {
    switch ($type) {
      case 'venues':
        return isset($results, $results['response'], $results['response']['venue']);
      
      case 'checkins':
        return isset($results, $results['response'], $results['response']['checkins']) && 
          count($results['response']['checkins']);
    }
    
    return isset($results, $results['response']) && $results['response'];
  }

  protected function getCacheName($type, $id, $params) {
    $cacheName = implode('_', array($type, $id));
    if ($this->queryConfig[$type]['includeParams']) {
      $cacheName .= http_build_query($params, null, '&');
    }
    return $cacheName;
  }

  protected function invalidateCache($type, $id, $params=array()) {
    $cache = $this->getCacheForQuery($type);
    $cacheName = $this->getCacheName($type, $id, $params);
    
    if ($cache && $cacheName) {
      $cacheFile = $cache->getFullPath($cacheName);
      if (file_exists($cacheFile)) {
        error_log("Removing invalidated cache file '$cacheFile'");
        @unlink($cacheFile);
      }
    }
  }

  protected function api($type, $id, $method='GET', $params=array()) {
    // Check if logged in:
    if (!$this->getSession()) {
      $loginURL = $this->getLoginURL();
      error_log($loginURL);
      header("Location: $loginURL");
      exit();
    }

    if (is_array($method) && empty($params)) {
      $params = $method;
      $method = 'GET';
    }
    
    $cache = $this->getCacheForQuery($type);
    
    $path = $this->queryConfig[$type]['path'].$id;
    if (isset($this->queryConfig[$type]['suffix'])) {
      $path .= $this->queryConfig[$type]['suffix'];
    }
    
    $cacheName = $this->getCacheName($type, $id, $params);

    $url = 'https://api.foursquare.com/v2/'.$path;
    $params['oauth_token'] = $this->getAccessToken();
    
    $shouldCache = $cache && $method == 'GET' && $id != 'self';
    $invalidateCache = $cache && $method != 'GET';
        
    if ($shouldCache && $cache->isFresh($cacheName)) {
      $results = $cache->read($cacheName);
      
    } else {
      $results = json_decode($this->query($url, $method, $params), true);
      
      if ($shouldCache) {
        if ($this->shouldCacheResultsForQuery($type, $results)) {
          $cache->write($results, $cacheName);
        } else {
          error_log("Error while making foursquare API request: ".
            (isset($results['meta'], $results['meta']['errorDetail']) ? 
              $results['meta']['errorDetail'] : $results));
          $results = $cache->read($cacheName);
        }
        
      } else if ($invalidateCache) {
        $cacheFile = $cache->getFullPath($cacheName);
        if (file_exists($cacheFile)) {
          error_log("Removing invalidated cache file '$cacheFile'");
          @unlink($cacheFile);
        }
      }
    }
    
    return $results ? $results : array();
  }
  
  protected function query($url, $method, $params=array()) {
    $ch = curl_init();

    $paramString = http_build_query($params, null, '&');
    
    if ($method == 'GET') {
      $url = $url.($paramString ? "?$paramString" : '');
    }

    $opts = array(
      CURLOPT_CONNECTTIMEOUT => 20,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT        => 60,
      CURLOPT_URL            => $url,
    );
    
    error_log("Requesting {$url}");

    if ($method != 'GET') {
      error_log("\twith post params $paramString");
      $opts[CURLOPT_POSTFIELDS] = $paramString;
    }
    
    curl_setopt_array($ch, $opts);
    $results = curl_exec($ch);
    curl_close($ch);
    
    return $results;
  }
}
