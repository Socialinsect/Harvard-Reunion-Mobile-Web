<?php

class InvalidPassKeyException extends Exception {}
class InvalidDeviceTokenException extends Exception {}

/* Representation of a unique device that is, was, or will be
 * registered with our push server.
 *
 * This implementation assumes there is one app ID per platform.
 * For APNs, device tokens are associated with app IDs and we
 * just assume any device token that comes our way is for our
 * configured app ID.  If we need to support multiple App IDs in
 * we will need to redo some of this.
 */
abstract class PushClientDevice {

  protected $platform;

  // table fields
  protected $deviceId;
  protected $passKey;
  protected $deviceToken;
  protected $lastUpdate; // unixtime
  protected $validAfter; // unixtime after which we may send messages
  protected $isActive = false;

  protected $unreadNotifications;

  const SOURCE_NEW = 10; // just generated new pass key
  const SOURCE_HTTP = 20; // client device request; requires checking pass key
  const SOURCE_DAEMON = 30; // no verification needed

  abstract protected function verifyDeviceTokenFormat($deviceToken);

  public static function deviceForToken($deviceToken, $source, $platform, $passKey=null) {
    // TODO: find a way to populate tables beforehand so we don't
    // need this in every constructor/factory method
    PushDB::createDatabaseTables($platform);

    $sql = 'SELECT * FROM '.PushDB::deviceTableForPlatform($platform)
          .' WHERE device_token=?';
    $params = array($deviceToken);
    $result = PushDB::connection()->query($sql, $params);
    $row = $result->fetch(PDO::FETCH_ASSOC);
    if ($row) {
      return self::factory($row['device_id'], $source, $platform, $passKey);
    }
    return null;
  }

  public static function factory($deviceId, $source, $platform, $passKey=null) {
    switch ($platform) {
      case 'ios':
      default:
        $className = 'ApplePushClientDevice';
        break;
    }

    return new $className($deviceId, $source, $passKey);
  }

  public static function generateNewDevice($platform) {
    // TODO: find a way to populate tables beforehand so we don't
    // need this in every constructor/factory method
    PushDB::createDatabaseTables($platform);
    
    $passKey = rand(0, 9999999999);
    $sql = 'INSERT INTO '.PushDB::deviceTableForPlatform($platform) 
          .' ( pass_key, active ) '
          .' VALUES ( ?, 1 )';
    $params = array($passKey);
    PushDB::connection()->query($sql, $params);

    $deviceId = PushDB::connection()->lastInsertId();
    return self::factory($deviceId, self::SOURCE_NEW, $platform, $passKey);
  }

  // returned to client the first time they
  // register with the server
  public function getDevicePassKey() {
    return array('device_id' => $this->deviceId, 'pass_key' => $this->passKey);
  }

  protected function __construct($deviceId, $source, $passKey=null) {
    // TODO: find a way to populate tables beforehand so we don't
    // need this in every constructor/factory method
    PushDB::createDatabaseTables($this->platform);
  
    $this->deviceId = $deviceId;
    switch ($source) {
      case self::SOURCE_NEW:
        $this->passKey = $passKey;
        break;
        
      case self::SOURCE_HTTP:
        $sql = 'SELECT device_token, last_update, valid_after '
              .'  FROM '.$this->deviceTable() 
              .' WHERE device_id=? AND pass_key=? AND active=1';
        $params = array($this->deviceId, $passKey);
        $result = PushDB::connection()->query($sql, $params);
        $row = $result->fetch(PDO::FETCH_ASSOC);
        if ($row) {
          $this->isActive = true;
          $this->passKey = $passKey;
          $this->deviceToken = $row['device_token'];
          $this->lastUpdate = $row['last_update'];
          $this->validAfter = $row['valid_after'];

        } else {
          throw new InvalidPassKeyException("invalid pass key");
        }

        break;
        
      case self::SOURCE_DAEMON:
      default:
        $sql = 'SELECT pass_key, device_token, last_update, valid_after '
              .'  FROM '.$this->deviceTable() 
              .' WHERE device_id=? AND active=1';
        $params = array($this->deviceId);

        $result = PushDB::connection()->query($sql, $params);
        $row = $result->fetch(PDO::FETCH_ASSOC);
        if ($row) {
          $this->isActive = true;
          $this->passKey = $row['pass_key'];
          $this->deviceToken = $row['device_token'];
          $this->lastUpdate = $row['last_update'];
          $this->validAfter = $row['valid_after'];
        }

        break;
    }
  }
  
  ////////// device (de)registration

  public function isActive() {
    return $this->isActive;
  }
  
  public function canSendMessages() {
    return $this->isActive() && time() > $this->validAfter;
  }
  
  public function getDeviceToken() {
    return $this->deviceToken;
  }

  // signal that messages should not be sent to the device until
  // $seconds seconds from now
  public function blockForSeconds($seconds) {
    $this->validAfter = time() + $seconds;
    $sql = 'UPDATE '.$this->deviceTable()
          .'   SET valid_after=?'
          .' WHERE device_id=?';
    $params = array($this->validAfter, $this->deviceId);
    
    $conn = PushDB::connection();
    $conn->query($sql, $params);
  }

  protected function deviceTable() {
    return PushDB::deviceTableForPlatform($this->platform);
  }

  public function registerDeviceToken($deviceToken) {
    // deactive any previous registrations of this device
    self::deactiveDeviceForToken($deviceToken, $this->platform);

    if (!$this->verifyDeviceTokenFormat($deviceToken)) {
      throw new Exception("device token:'{$deviceToken}' not correctly formatted");
    }

    $this->deviceToken = $deviceToken;
    $this->isActive = true;
    $this->lastUpdate = time();
    $this->validAfter = time();

    $sql = 'UPDATE '.$this->deviceTable() 
          .'   SET device_token=?, active=1, last_update=?, valid_after=?'
          .' WHERE device_id=?';
    $params = array($this->deviceToken, $this->lastUpdate, $this->validAfter, $this->deviceId);

    $conn = PushDB::connection();
    $conn->query($sql, $params);
  }
  
  public static function deactiveDeviceForToken($deviceToken, $platform, $revokeTime=null) {
    $sql = 'UPDATE '.PushDB::deviceTableForPlatform($platform)
          .'   SET active=0, last_update=? '
          .' WHERE device_token=?';
    $params = array(time(), $deviceToken);

    if ($revokeTime !== NULL) {
      $sql .= ' AND last_update < ?';
      $params[] = $revokeTime;
    }

    $conn = PushDB::connection();
    $conn->query($sql, $params);
  }

  ///////// device subscriptions

  // TODO give these functions return values
  // so callers know whether they succeeded

  public function addSubscription($tag, $params=array()) {
    $tableName = $tag.'_subscriptions';

    $params['device_id'] = $this->deviceId;
    $params['platform'] = $this->platform;

    $fields = implode(', ', array_keys($params));
    $placeholders = implode(', ', array_fill(0, count($fields), '?'));
    $values = array_values($params);

    $sql = "INSERT INTO $tableName ( $fields ) VALUES ( $placeholders )";
    $conn = PushDB::connection();
    $result = $conn->query($sql, $values);
  }

  public function removeSubscription($tag, $params=array()) {
    $tableName = $tag.'_subscriptions';

    $params['device_id'] = $this->deviceId;
    $params['platform'] = $this->platform;

    $whereClause = '';
    foreach (array_keys($params) as $param) {
      if (strlen($whereClause) > 0) {
        $whereClause .= ' AND ';
      }
      $whereClause .= "$param=?";
    }

    $values = array_values($params);

    $sql = "DELETE FROM $tableName WHERE $whereClause";
    $result = PushDB::conection()->query($sql, $values);
  }
  
  //////// device notification queue

  protected function createNotification($tag, $data, $badgeValue) {
    $conn = PushDB::connection();
    $json = $conn->real_escape_string(json_encode($data));
    $values = array($this->deviceId, $json, $tag, time(), $badgeValue);
    $placeholders = implode(', ', array_fill(0, count($values), '?'));

    $sql = 'INSERT INTO '.$this->notificationTable()
          .'device_id, message, tag, created_unixtime, badge_value '
          ."VALUES ( $placeholders )";
    $result = $conn->query($sql, $values);
  }

  protected function notificationTable() {
    return PushDB::notificationTableForPlatform($this->platform);
  }

  // TODO figure out if there are cache issues when
  // called by the daemon
  public function getUnreadNotifications() {
    if (!$this->unreadNotifications) {
      $sql = 'SELECT message_id, tag, message, badge_value '
            .'  FROM '.$this->notificationTable()
            .' WHERE device_id=? '
            .'   AND sent_unixtime IS NOT NULL '
            .'   AND read_unixtime IS NULL';
      $params = array($this->deviceId);
      $result = PushDB::connection()->query($sql, $params);
      $this->unreadNotifications = $result->fetchAll();
    }
    return $this->unreadNotifications;
  }
  
  public function markNotificationsReadForTags($tags) {
    $placeholders = array_fill(0, count($tags), 'tag LIKE ?');
    $sql = 'UPDATE '.$this->notificationTable()
          .'   SET read_unixtime=?'
          .' WHERE device_id=? AND ( '.implode($placeholders).' )';
    $params = array(time(), $this->deviceId);
    $params = array_merge($params, $tags);
    PushDB::connection()->query($sql, $params);
  }

  public function markNotificationRead($messageId) {
    $sql = 'UPDATE '.$this->notificationTable()
          .'   SET read_unixtime=?'
          .' WHERE message_id=?';
    $params = array(time(), $messageId);
    PushDB::connection()->query($sql, $params);
  }

  public function markNotificationSent($messageId) {
    $sql = 'UPDATE '.$this->notificationTable()
          .'   SET sent_unixtime=?'
          .' WHERE message_id=?';
    $params = array(time(), $messageId);
    PushDB::connection()->query($sql, $params);
  }

  public function markNotificationUndeliverable($messageId) {
    $sql = 'UPDATE '.$this->notificationTable()
          .'   SET undeliverable_unixtime=?,'
          .'       retries_remaining = retries_remaining - 1 '
          .' WHERE message_id=?';
    $params = array(time(), $messageId);
    PushDB::connection()->query($sql, $params);
  }

  /////////// device settings for notification types

  protected function settingsTable() {
    return PushDB::settingsTableForPlatform($this->platform);
  }

  public function disableNotificationsForTag($tag) {
    $sql = 'UPDATE '.$this->settingsTable()
          .'   SET disabled=1'
          .' WHERE device_id=? AND tag=?';
    $params = array($this->deviceId, $tag);

    $result = PushDB::connection()->query($sql, $params);

    if (!$result->rowCount()) {
      $sql = 'INSERT INTO '.$this->settingsTable().' ( device_id, tag ) '
            .' VALUES ( ?, ? )';
      $result = PushDB::connection()->query($sql, $params);
    }
  }

  // push notifications are enabled by default so if no rows
  // are found we consider it enabled
  public function enableNotificationsForTag($tag) {
    $sql = 'UPDATE '.$this->settingsTable()
          .'   SET disabled=0'
          .' WHERE device_id=? AND tag=?';
    $params = array($this->deviceId, $tag);

    $result = PushDB::connection()->query($sql, $params);
  }

  public function notificationsEnabledForTag($tag) {
    $sql = 'SELECT disabled FROM '.$this->settingsTable()
          .' WHERE device_id=? AND tag=?';
    $params = array($this->deviceId, $tag);
    $results = PushDB::connection()->query($sql, $params);

    foreach ($results->fetchAll() as $row) {
      if ($row['disabled'] == 1) {
        return false;
      }
    }

    return true;
  }

}


