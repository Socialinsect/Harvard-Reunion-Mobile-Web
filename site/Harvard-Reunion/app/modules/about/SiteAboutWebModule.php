<?php
/**
  * @package Module
  * @subpackage About
  */

class SiteAboutWebModule extends AboutWebModule {
  protected function getInfoForUser($user) {
    $feeds = $this->loadFeedData();

    $feed = array();
    if (isset($feeds['default'])) {
      $feed = $feeds['default'];
    }

    $index = $user->getGraduationClass();
    if (isset($feeds[$index])) {
      $feed = array_merge($feed, $feeds[$index]);
      
    } else {
      $index .= ($user->getCollegeIndex() == 0 ? 'h' : 'r');
      if (isset($feeds[$index])) {
        $feed = array_merge($feed, $feeds[$index]);
      }
    }

    $info = array();
    if ($feed) {
      $info = array(
        'about'   => self::argVal($feed, 'ABOUT_TEXT', array()),
        'links'   => array(),
      );
      
      $linkArrays = array(
        'label'    => self::argVal($feed, 'LINKS_LABELS',    array()),
        'title'    => self::argVal($feed, 'LINKS_TITLES',    array()),
        'subtitle' => self::argVal($feed, 'LINKS_SUBTITLES', array()),
        'url'      => self::argVal($feed, 'LINKS_URLS',      array()),
        'class'    => self::argVal($feed, 'LINKS_CLASSES',   array()),
      );
      
      foreach ($linkArrays['title'] as $index => $title) {
        $link = array();
        foreach (array_keys($linkArrays) as $key) {
          $value = self::argVal($linkArrays[$key], $index, '');
          if ($value) {
            if (isset($feed[$value])) {
              $link[$key] = $feed[$value];
            } else {
              $link[$key] = $value;
            }
          }
        }
        
        if ($link) {
          $info['links'][] = $link;
        }
      }
    }
    
    return $info;
  }

  protected function initializeForPage() {
    $user = $this->getUser('HarvardReunionUser');
    
    switch ($this->page) {
      case 'index':
        $this->assign('info', $this->getInfoForUser($user));
        break;
    }
  }
}
