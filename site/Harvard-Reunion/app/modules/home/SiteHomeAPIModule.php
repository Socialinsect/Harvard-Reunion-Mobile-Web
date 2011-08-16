<?php

/****************************************************************
 *
 *  Copyright 2011 The President and Fellows of Harvard College
 *  Copyright 2011 Modo Labs Inc.
 *
 *****************************************************************/

class SiteHomeAPIModule extends APIModule {
  protected $id = 'home';

  public function initializeForCommand() {
    $user = $this->getUser('HarvardReunionUser');
    $schedule = new Schedule($user);
    
    $facebook = $schedule->getFacebookFeed();
    $twitter = $schedule->getTwitterFeed();

    switch ($this->command) {
      case 'config':
        if (Schedule::userHasReunion($user)) {
          $this->setResponse($schedule->getReunionConfig());
          $this->setResponseVersion(1);
          
        } else {
          $error = new KurogoError(
            1,
            'Invalid Request',
            'User\'s graduation year does not have an upcoming reunion');
          $this->throwError($error);
        }
        break;
      
      case 'modules':
        $moduleNavConfig = ModuleConfigFile::factory('home', 'module');
        if (!$moduleNavConfig) {
          $error = new KurogoError(
            1,
            'Invalid Configuration',
            'Home module config missing');
          $this->throwError($error);
        }
        
        $moduleConfig = array();
        $moduleConfig['primary']   = array_keys($moduleNavConfig->getOptionalSection('primary_modules', array()));
        $moduleConfig['secondary'] = array_keys($moduleNavConfig->getOptionalSection('secondary_modules', array()));
        
        $this->setResponse($moduleConfig);
        $this->setResponseVersion(1);
        break;
      
      case 'recent':
        $response = array();
        
        // Only grab posts if logged in
        $posts = array();
        $tweets = array();
        if ($user->getShowHomeFacebookPosts() && !$facebook->needsLogin() && $facebook->isMemberOfGroup()) {
          $posts = $facebook->getGroupStatusMessages();
        }
        if ($user->getShowHomeTwitterStream()) {
          $tweets = $twitter->getRecentTweets();
        }
        
        $recent = false;
        $recentType = false;
        if (count($posts)) {
          $recent = reset($posts);
          $recent['message'] = htmlspecialchars($recent['message']); // match twitter which is already escaped
          $recent['author']['name'] = htmlspecialchars($recent['author']['name']);
          $recentType = 'facebook';
        }
        if (count($tweets)) {
          $tweet = reset($tweets);
          if (!$recent || 
              (intval($tweet['when']['time']) > intval($recent['when']['time']))) {
            $recent = $tweet;
            $recentType = 'twitter';
          }
        }
        if ($recent) {
          $response = array(
            'type'    => $recentType,
            'message' => $recent['message'],
            'author'  => $recent['author']['name'],
            'age'     => $recent['when']['shortDelta'],
          );
        }
        
        $this->setResponse($response);
        $this->setResponseVersion(1);
        break;
        
      default:
        $this->invalidCommand();
        break;
    }
  }
}