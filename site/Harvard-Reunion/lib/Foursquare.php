<?php

class Foursquare {
  const COOKIE_NAME = 'fqReunionSession';
  private $clientId = '';
  private $clientSecret = '';
  
  const USER_LIFETIME = 14400;
  const PLACE_LIFETIME = 14400;
  const NOCACHE_LIFETIME = 0;
  
  private $queryConfig = array(
    'users' => array(
      'cache'         => null,
      'cacheLifetime' => self::PLACE_LIFETIME,
      'path'          => 'users/',
      'includeParams' => false,
    ),
    'venueSearch' => array(
      'cache'         => null,
      'cacheLifetime' => self::PLACE_LIFETIME,
      'path'          => 'venues/search',
      'includeParams' => true,
    ),
    'venues' => array(
      'cache'         => null,
      'cacheLifetime' => self::PLACE_LIFETIME,
      'path'          => 'venues/',
      'includeParams' => false,
    ),
    'checkins' => array(
      'cache'         => null,
      'cacheLifetime' => self::NOCACHE_LIFETIME,
      'path'          => 'users/self/checkins',
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
      if ($urlPrefix == FULL_URL_PREFIX) {
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
    
    $results = $this->api('users', 'self');
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
  
  public function isCheckedIn($venueId, $afterTimestamp) {
    $isCheckedIn = false;
    $realVenueId = $venueId;
    
    $results = $this->api('venues', $venueId);
    //error_log(print_r($results, true));
    if (isset($results['response'], $results['response']['venue'])) {
      $realVenueId = $results['response']['venue']['id'];
    }
    
    $results = $this->api('checkins', '', array(
      'afterTimestamp' => $afterTimestamp,
    ));
    //error_log(print_r($results, true));
    if (isset($results['response'], $results['response']['checkins'], $results['response']['checkins']['items'])) {
      foreach ($results['response']['checkins']['items'] as $item) {
        if ($item['venue']['id'] == $realVenueId) {
          $isCheckedIn = true;
          break;
        }
      }
    }
    
    return $isCheckedIn;
  }
  
  public function addCheckin($venueId, $message, $coords) {
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
      'display'       => 'touch',
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
  
  public function getLogoutUrl() {
    return FULL_URL_PREFIX.'home/fqLogout?'.http_build_query(array(
      'url' => $this->getCurrentUrl(),
    ));
  }
  
  public function getLogoutRedirectURL($redirectURL) {
    return $redirectURL;
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
    
    $cacheName = implode('_', array($type, $id));
    if ($this->queryConfig[$type]['includeParams']) {
      $cacheName .= http_build_query($params, null, '&');
    }

    $url = 'https://api.foursquare.com/v2/'.$path;
    $params['oauth_token'] = $this->getAccessToken();
    
    $shouldCache = $cache && $method == 'GET';
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
