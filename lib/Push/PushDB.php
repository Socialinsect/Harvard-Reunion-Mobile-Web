<?php

class PushDB
{
  public static $db = null;

  public static function connection()
  {
    if (!$connection = self::$db) {
      $dbFile = Kurogo::getSiteVar('PUSH_DB');
      try {
        self::$db = new db(array('DB_TYPE' => 'sqlite',
                                 'DB_FILE' => $dbFile));
        $connection = self::$db;
      } catch (Exception $e) {
        error_log('Could not open database file '.$dbFile.', '.$e->getMessage());
      
      }
    }
    return $connection;
  }

  public static function notificationTableForPlatform($platform) {
    switch ($platform) {
      case 'ios':
      default:
        return 'apns_notifications';
        break;
    }
  }

  public static function deviceTableForPlatform($platform) {
    switch ($platform) {
      case 'ios':
      default:
        return 'apns_devices';
        break;
    }
  }

  /* by default all notifications are enabled for any subscriptions.
   * this table provides a disabled flag that overrides subscriptions.
   * so if the user re-enables notifications, they will get back
   * their old subscriptions.
   */
  public static function settingsTableForPlatform($platform) {
    switch ($platform) {
      case 'ios':
      default:
        return 'apns_settings';
        break;
    }
  }

  public static function createDatabaseTables($platform) {
    $conn = self::connection();
    $tableName = self::deviceTableForPlatform($platform);
    $sql = "SELECT 1 FROM $tableName";
    if (!$result = $conn->query($sql, array(), db::IGNORE_ERRORS)) {
      $sql = "CREATE TABLE $tableName (
                device_id integer primary key autoincrement,
                device_token char(64),
                active int,
                pass_key int,
                last_update int,
                valid_after int)";
      $conn->query($sql);
    }

    $tableName = self::notificationTableForPlatform($platform);
    $sql = "SELECT 1 FROM $tableName";
    if (!$result = $conn->query($sql, array(), db::IGNORE_ERRORS)) {
      $sql = "CREATE TABLE $tableName (
                message_id integer primary key autoincrement,
                device_id int,
                tag char(64),
                created_unixtime int,
                sent_unixtime int,
                undeliverable_unixtime int,
                read_unixtime int,
                expire_time int,
                retries_remaining int,
                badge_value char(32),
                message text)";
      $conn->query($sql);
    }

    $tableName = self::settingsTableForPlatform($platform);
    $sql = "SELECT 1 FROM $tableName";
    if (!$result = $conn->query($sql, array(), db::IGNORE_ERRORS)) {
      $sql = "CREATE TABLE $tableName (
                device_id int,
                tag char(64),
                disabled int)";

      $conn->query($sql);
    }
  }

  public static function getAllDevices($platform) {
    $sql = 'SELECT device_id FROM '.self::deviceTableForPlatform($platform);
    $result = self::connection()->query($sql);
    return $result->fetchAll();
  }

  public static function getActiveDevices($platform) {
    $sql = 'SELECT device_id FROM '.self::deviceTableForPlatform($platform)
          .' WHERE active=1';
    $result = self::connection()->query($sql);
    return $result->fetchAll();
  }

  public static function getUnsentNotifications($platform) {
    $sql = 'SELECT message_id, device_id, tag, message, badge_value, expire_time '
          .'   FROM '.self::notificationTableForPlatform($platform)
          .' WHERE sent_unixtime IS NULL '
          .'    AND retries_remaining > 0'
          .'   AND expire_time > ?'
          .' ORDER BY device_id';
    $params = array(time());
    return self::connection()->query($sql, $params);
  }
}
