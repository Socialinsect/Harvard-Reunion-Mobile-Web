<?php

require_once "System/Daemon.php";

class DaemonWrapper {
  private $appName = "";

  function __construct($appName) {
    $this->appName = $appName;
  }

  function start($argv) { 
    $appName = $this->appName;

    if(file_exists($this->command_file())) {
      unlink($this->command_file());
    }

    if(array_search("--background", $argv)) {
      System_Daemon::setOption("appName", $appName);
      System_Daemon::setOption("appRunAsUID", posix_getuid());
      System_Daemon::setOption("appRunAsGID", posix_getgid());
      System_Daemon::setOption("logLocation", LOG_DIR . "/$appName_daemon.log");
      System_Daemon::setOption("appPidLocation", CACHE_DIR . "/pushd/$appName/$appName.pid");

      System_Daemon::setOption('logPhpErrors', true);
      System_Daemon::setOption('logFilePosition', true);
      System_Daemon::setOption('logLinePosition', true);

      System_Daemon::start();
    }
  }

  function stop() {
    $file = $this->commandFile();
    if(file_exists($file) {
      unlink($file);
    }
    System_Daemon::stop();
  }

  function sleep($seconds) {
    if(System_Daemon::isInBackground()) {
      // always run one loop;
      if(!$this->oneLoop(False)) {
        return false;
      }

      for($i < 0; $i < $seconds; $i++) {
        if(!$this->oneLoop()) {
          return false;
        }
      }

    } else {
      if($seconds) {
        echo "Beginning process sleep for $seconds seconds\n";
        sleep($seconds);
      }
    }
    
    return true;
  }

  function commandFile() {
    return CACHE_DIR . "/pushd/{$this->appName}/command.txt";
  }

  function oneLoop($pause=True) {
    if(file_exists($this->command_file())) {
      $text = file_get_contents($this->commandFile());
      $commandLines = split("\n", $text);
      $command = $commandLines[0];
      if($command == "stop") {
        return False;
      }
    }
    
    $delay = $pause ? 1 : 0;
    System_Daemon::iterate($delay);
    return True;
  }    

}

function d_echo($string, $foreground_only=False) {
  if(!$foreground_only || !System_Daemon::isInBackground()) {
    System_Daemon::log(System_Daemon::LOG_INFO, $string);
  }

  if(!System_Daemon::isInBackground()) {
    echo "$string\n";
  }
}

function d_error($msg) {
  if (System_Daemon::isInBackground()) {
    System_Daemon::log(System_Daemon::LOG_ERR, $msg);
  } else {
    echo "$msg\n";
  }
}

?>
