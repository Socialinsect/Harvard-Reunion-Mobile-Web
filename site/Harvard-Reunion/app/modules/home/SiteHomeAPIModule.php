<?php

class SiteHomeAPIModule extends APIModule {
  protected $id = 'home';    

  public function initializeForCommand() {
    $user = $this->getUser('HarvardReunionUser');
    $this->schedule = new Schedule($user);
    
    $facebook = $this->schedule->getFacebookGroup();
    $twitter = $this->schedule->getTwitterFeed();

    switch ($this->command) {
      case 'recent':
        $response = array();
      
        // Only grab posts if logged in
        $posts = $facebook->getMyId() ? $facebook->getGroupStatusMessages() : array();
        $tweets = $twitter->getRecentTweets();
        
        $recent = false;
        $recentType = false;
        if (count($posts)) {
          $recent = reset($posts);
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