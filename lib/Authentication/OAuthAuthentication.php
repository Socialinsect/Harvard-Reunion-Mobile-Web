<?php

Kurogo::includePackage('Authorization');

abstract class OAuthAuthentication extends AuthenticationAuthority
{
    protected $OAuthProviderClass;
    protected $OAuthProvider;
    abstract protected function getUserFromArray(array $array);

    protected function validUserLogins() { 
        return array('LINK', 'NONE');
    }
		
    protected function auth($login, $password, &$user) {
        return AUTH_FAILED;
    }

    //does not support groups
    public function getGroup($group) {
        return false;
    }

    public function login($login, $pass, Session $session, $options) {
        $oauth = $this->getOAuthProvider();
        $result = $oauth->auth($options, $userArray);
        if ($result == AUTH_OK) {
            if ($user = $this->getUserFromArray($userArray)) {
                $oauth->saveTokenForUser($user);
                $session->login($user);
            } else {
                $result = AUTH_FAILED;
            }
        }
        return $result;
    }

    public function getOAuthProvider() {
        if (!$this->OAuthProvider) {
            $this->OAuthProvider = OAuthProvider::factory($this->OAuthProviderClass, $this->initArgs);
        }
        return $this->OAuthProvider;
    }
}

class OAuthUser extends User
{
    protected function getOAuthProvider() {
        return $this->AuthenticationAuthority->getOAuthProvider();
    }
   
    public function __construct(AuthenticationAuthority $AuthenticationAuthority) {
        parent::__construct($AuthenticationAuthority);
        $oauth = $this->getOAuthProvider();
        $oauth->setTokenFromUser($this);
    }
   
}