<?php

/****************************************************************
 *
 *  Copyright 2011 The President and Fellows of Harvard College
 *  Copyright 2011 Modo Labs Inc.
 *
 *****************************************************************/

class SiteLoginAPIModule extends LoginAPIModule
{
    protected $id = 'login';
    public function initializeForCommand() {
        if (!Kurogo::getSiteVar('AUTHENTICATION_ENABLED')) {
            throw new Exception("Authentication is not enabled on this site");
        }

        switch ($this->command) {
           case 'session':
                $session = $this->getSession();
                $user = $this->getUser();
                
                $response = array(
                    'session_id'=>$session->getSessionID(),
                    'token'=>$session->getLoginToken(),
                    'user'=>array(
                        'authority'=>$user->getAuthenticationAuthorityIndex(),
                        'userID'=>$user->getUserID(),
                        'name'=>$user->getFullName(),
                        'sessiondata'=>$user->getSessionData()
                    )
                );
                
                if ($user instanceOf HarvardReunionUser && ($user->needsCollegeIndex() || !Schedule::userHasReunion($user))) {
                  // fake the user not being logged in yet so that the user is
                  // prompted to set a class (harvard or radcliffe)
                  $response['user']['authority'] = null;
                  $response['user']['userID'] = null;
                  $response['user']['name'] = null;
                  $response['user']['sessiondata'] = null;
                }

                $this->setResponse($response);
                $this->setResponseVersion(1);
                break;

          default:
            parent::initializeForCommand();
            break;
        }
    }
}
