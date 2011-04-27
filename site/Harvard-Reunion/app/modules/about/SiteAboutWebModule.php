<?php
/**
  * @package Module
  * @subpackage About
  */

class SiteAboutWebModule extends AboutWebModule {

  protected function initializeForPage() {
    $user = $this->getUser('HarvardReunionUser');
    $schedule = new Schedule($user);
    
    switch ($this->page) {
      case 'index':
        $info = $schedule->getInfo();
        
        foreach ($info['sections'] as $s => $section) {
          foreach ($section['links'] as $l => $link) {
            if (isset($link['class'])) {
              if (strpos('phone', $link['class']) !== false) {
                $info['sections'][$s]['links'][$l]['subtitle'] = 
                  str_replace('-', '-&shy;', $info['sections'][$s]['links'][$l]['subtitle']);
                  
              } else if (strpos('email', $link['class']) !== false) {
                $info['sections'][$s]['links'][$l]['subtitle'] = 
                  str_replace('@', '@&shy;', $info['sections'][$s]['links'][$l]['subtitle']);
              }
            }
          }
        }
        
        $this->assign('info', $info);
        break;
    }
  }
}
