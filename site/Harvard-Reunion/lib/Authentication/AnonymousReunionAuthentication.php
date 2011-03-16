<?php

class AnonymousReunionAuthentication extends AuthenticationAuthority
{
    protected function auth($login, $password, &$user) {
        if ($user = $this->getUser($login)) {
            return AUTH_OK;
        } else {
            return AUTH_FAILED;
        }
    }
    
    protected function isValidYear($year) {
        return preg_match("/^(19|20)\d\d$/", $year);
    }

    public function getUser($login) {
        if ($this->isValidYear($login)) {
            $user = new AnonymousReunionUser($this);
            $user->setClass_year($login);
            $user->setUserID($login);
            $user->setFirstName('Class of'); //need input on this
            $user->setLastName($login);
            return $user;
        }
        
        return false;
    }
    
    public function getGroup($group) {
        return false;
    }
}

class AnonymousReunionUser extends HarvardReunionUser 
{
}