<?php

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

      case 'recent':
        $response = array();
        
        // Only grab posts if logged in
        $posts = !$facebook->needsLogin() ? $facebook->getGroupStatusMessages() : array();
        $tweets = $twitter->getRecentTweets();
        
        $recent = false;
        $recentType = false;
        if (count($posts) && $user->getShowHomeFacebookPosts()) {
          $recent = reset($posts);
          $recentType = 'facebook';
        }
        if (count($tweets) && $user->getShowHomeTwitterStream()) {
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
        
      case 'facebook':
        $this->setResponse($facebook->getGroupStatusMessages());
        $this->setResponseVersion(1);
        break;

      case 'comments':
        $postId = $this->getArg('id');
        
        $this->setResponse($facebook->getComments($postId));
        $this->setResponseVersion(1);
        break;
        
      case 'twitter':
        $this->setResponse($twitter->getRecentTweets());
        $this->setResponseVersion(1);
        break;
        
      default:
        $this->invalidCommand();
        break;
    }
  }
}