<?php

/****************************************************************
 *
 *  Copyright 2011 The President and Fellows of Harvard College
 *  Copyright 2011 Modo Labs Inc.
 *
 *****************************************************************/

class SiteAttendeesWebModule extends WebModule
{
  protected $id = 'attendees';
  
  protected function initializeForPage() {
    $user = $this->getUser('HarvardReunionUser');
    $schedule = new Schedule($user);
    
    switch ($this->page) {
      case 'filter':
      case 'index':
        $this->setTemplatePage('index');
      
        $range = $this->getArg('range', null);
        $allAttendees = $schedule->getAllAttendees();
      
        $letterGroups = $schedule->getAttendeeFirstLetterGroups($allAttendees);
        if (!$letterGroups || $range) {
          $filtered = $allAttendees;
          if ($range) {
            $printableRange = implode(' - ', explode('-', $range));
            $this->setPageTitle($this->getPageTitle()." ($printableRange)");
            $this->setBreadcrumbTitle($this->getBreadcrumbTitle()." ($printableRange)");
            $this->setBreadcrumbLongTitle($this->getBreadcrumbLongTitle()." ($printableRange)");
            $filtered = $schedule->getAttendeesForLetterRange($allAttendees, $range);
          }
          
          $attendees = array();
          foreach ($filtered as $attendee) {
            if ($attendee['display_name']) {
              $attendees[] = array(
                'title' => $attendee['display_name'],
              );
            }
          }
          $this->assign('attendees',  $attendees);
           
        } else {
          $groups = array();
          foreach ($letterGroups as $range => $info) {
            $info['url'] = $this->buildBreadcrumbURL('filter', array(
              'range' => $range,
            ));
            $groups[] = $info;
          }
          
          $this->assign('groups',  $groups);
        }
        
        $authority = $user->getAuthenticationAuthorityIndex();
        
        if ($authority == 'anonymous') {
          $this->assign('signinURL', $this->buildURLForModule('login', 'logout', array(
            'authority' => $authority,
            'hard'      => 1,
            'url'       => URL_PREFIX.'login/?'.http_build_query(array(
              'authority' => 'harris',
              'url'       => URL_PREFIX.ltrim($this->buildBreadcrumbURL($this->page, $this->args, false), '/'),
            ), null, '&'),
          )));
        }

        $this->assign('reunionTitle', $schedule->getReunionTitle());
        $this->assign('authority',    $user->getAuthenticationAuthorityIndex());
        break;
    }
  }
}
