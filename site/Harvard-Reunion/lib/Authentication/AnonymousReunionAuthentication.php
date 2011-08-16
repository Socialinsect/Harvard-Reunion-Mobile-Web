<?php

/****************************************************************
 *
 *  Copyright 2011 The President and Fellows of Harvard College
 *  Copyright 2011 Modo Labs Inc.
 *
 *****************************************************************/

class AnonymousReunionAuthentication extends AuthenticationAuthority
{
    protected function auth($login, $password, &$user) {
        if ($user = $this->getUser($login)) {
            return AUTH_OK;
        } else {
            return AUTH_FAILED;
        }
    }
    
    public function getUser($login) {
        if (preg_match("/^((19|20)\d\d)(|h|r)$/", $login, $matches)) {
            $year = $matches[1];
        
            $user = new AnonymousReunionUser($this);
            $user->setClass_year($year);
            $user->setUserID($login);
            
            if ($matches[3] == 'r') {
              $user->setCollegeIndex(1);  // Radcliffe
            } else {
              $user->setCollegeIndex(0);  // default to Harvard
            }
            return $user;
        }
        
        return false;
    }
    
    public function getGroup($group) {
        return false;
    }
    
    public function validate(&$error) {
        return true;
    }
}

class AnonymousReunionUser extends HarvardReunionUser 
{
    public function getFullName() {
        return '';
    }
    
    public function getFirstName() {
        return '';
    }

    public function getLastName() {
        return '';
    }
}
