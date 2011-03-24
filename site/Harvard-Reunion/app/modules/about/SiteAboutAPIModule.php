<?php
/**
  * @package Module
  * @subpackage About
  */

class SiteAboutAPIModule extends APIModule {
  protected $id = 'about';    

  public static function getInfoForUser($feeds, $year, $collegeIndex=0) {
    $feed = array();
    if (isset($feeds['default'])) {
      $feed = $feeds['default'];
    }

    $index = $year;
    if (isset($feeds[$index])) {
      $feed = array_merge($feed, $feeds[$index]);
      
    } else {
      $index .= ($collegeIndex == 0 ? 'h' : 'r');
      if (isset($feeds[$index])) {
        $feed = array_merge($feed, $feeds[$index]);
      }
    }

    $info = array();
    if ($feed) {
      $info = array(
        'paragraphs' => self::argVal($feed, 'ABOUT_TEXT', array()),
        'sections'   => array(),
      );
      
      $sectionKeys   = self::argVal($feed, 'SECTION_KEYS',   array());
      $sectionTitles = self::argVal($feed, 'SECTION_TITLES', array());
      foreach ($sectionKeys as $i => $key) {
        $title =  self::argVal($sectionTitles, $i, '');
        if ($key && $title) {
          $info['sections'][$key] = array(
            'title' => $title,
            'links' => array(),
          );
        }
      }
      
      $linkArrays = array(
        'title'    => self::argVal($feed, 'LINKS_TITLES',    array()),
        'subtitle' => self::argVal($feed, 'LINKS_SUBTITLES', array()),
        'url'      => self::argVal($feed, 'LINKS_URLS',      array()),
        'class'    => self::argVal($feed, 'LINKS_CLASSES',   array()),
      );
      $sections = self::argVal($feed, 'LINKS_SECTION', array());
      
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
        
        if ($link && isset($sections[$index], $info['sections'][$sections[$index]])) {
          $info['sections'][$sections[$index]]['links'][] = $link;
        }
      }
      $info['sections'] = array_values($info['sections']);
    }
    
    return $info;
  }

  public function initializeForCommand() {
    $user = $this->getUser('HarvardReunionUser');

    switch ($this->command) {
      case 'info':
        $year = $this->getArg('year');
        $collegeIndex = $this->getArg('collegeIndex', 0);
        
        $feeds = $this->loadFeedData();
        
        $this->setResponse(self::getInfoForUser($feeds, $year, $collegeIndex));
        $this->setResponseVersion(1);
        break;
    }        
  }
}