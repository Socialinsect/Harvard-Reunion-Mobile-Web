<?php

class SiteLoginWebModule extends LoginWebModule
{

  protected function initializeForPage() {
    
    $url = $this->getArg('url', ''); //return url
    $session  = $this->getSession();

    switch ($this->page)
    {
        case 'logoutConfirm':
            $authorityIndex = $this->getArg('authority');
            
            if (!$this->isLoggedIn($authorityIndex)) {
                $this->redirectTo('index', array());
            } elseif ($user = $this->getUser($authorityIndex)) {
                $authority = $user->getAuthenticationAuthority();
                $this->assign('message', sprintf("You are logged in as %s %s", $user->getFullName(), $multipleAuthorities ? '(' . $authority->getAuthorityTitle() . ')' : ''));
                $this->assign('url', $this->buildURL('logout', array('authority'=>$authorityIndex)));
                $this->assign('linkText', 'Logout');
                $this->setTemplatePage('message');
            } else {
                $this->redirectTo('index', array());
            }
            
            break;
        case 'logout':
            $this->setTemplatePage('message');
            $authorityIndex = $this->getArg('authority');
            $hard = $this->getArg('hard', false);

            if (!$this->isLoggedIn($authorityIndex)) {
                $this->redirectTo('index', array());
            } elseif ($authority = AuthenticationAuthority::getAuthenticationAuthority($authorityIndex)) {
                $result = $session->logout($authority, $hard);
            } else {
                $this->redirectTo('index', array());
            }
                
            $this->assign('message', $result ? 'Logout Successful' : 'Logout failed');
        
            break;
        case 'login':
            $login          = $this->argVal($_POST, 'loginUser', '');
            $password       = $this->argVal($_POST, 'loginPassword', '');
            $authorityIndex = $this->getArg('authority', AuthenticationAuthority::getDefaultAuthenticationAuthorityIndex());

            $options = array(
                'url'=>$url,
                'authority'=>$authorityIndex
            );
            
            $referrer = $this->argVal($_SERVER, 'HTTP_REFERER', '');
            $session->setRemainLoggedIn($this->getArg('remainLoggedIn', 0));
            
            $this->assign('authority', $authorityIndex);

            if ($this->isLoggedIn($authorityIndex)) {
                $this->redirectTo('index', $options);
            }                    
            
            if (empty($login)) {
                $this->redirectTo('index', $options);
            }
            
            if ($authority = AuthenticationAuthority::getAuthenticationAuthority($authorityIndex)) {
                $result = $authority->login($login, $password, $session, $options);
            } else {
                error_log("Invalid authority $authorityIndex");
                $this->redirectTo('index', $options);
            }

            switch ($result)
            {
                case AUTH_OK:
                    if ($url) {
                        header("Location: $url");
                        exit();
                    } 
                    $this->setTemplatePage('message');
                    $this->assign('message', 'Login Successful');
                    break;

                case AUTH_FAILED:
                case AUTH_USER_NOT_FOUND:
                
                    $this->setTemplatePage('login');
                    $this->assign('message', 'Login Failed. Please check your login and password');
                    break;
                default:
                    $this->setTemplatePage('login');
                    $this->assign('message', "Login Failed. An unknown error occurred $result");
                    

            }
            break;
        case 'index':
            if ($this->isLoggedIn()) {
                if ($url) {
                    header("Location: $url");
                    exit();
                }

                $sessionUsers = $session->getUsers();
                $users = array();

                foreach ($sessionUsers as $authority=>$user) {
                    $users[] = array(
                        'title'=>sprintf("%s", $user->getFullName()),
                        'subtitle'=>$user->getAuthenticationAuthorityIndex(),
                        'url'  =>$this->buildBreadcrumbURL('logoutConfirm', array('authority'=>$user->getAuthenticationAuthorityIndex()), false)
                    );
                    if (isset($authenticationAuthorities[$authority])) {
                        unset($authenticationAuthorities[$authority]);
                    }

                    if (isset($authenticationAuthorityLinks[$authority])) {
                        unset($authenticationAuthorityLinks[$authority]);
                    }
                }

                $this->assign('users', $users);
                $this->assign('authenticationAuthorities', $authenticationAuthorities);
                $this->assign('authenticationAuthorityLinks', $authenticationAuthorityLinks);

                $this->setTemplatePage('loggedin');
            } elseif ($authority = $this->getArg('authority')) {
                $this->setTemplatePage($authority);
                $this->assign('url',$url);
                $this->assign('cancelURL',$this->buildURL('index', array('url'=>$url)));
            } else {

                $this->assign('harrisURL',   $this->buildURL($this->page, array('authority'=>'harris', 'url'=>$url)));
                $this->assign('anonymousURL',$this->buildURL($this->page, array('authority'=>'anonymous', 'url'=>$url)));
            }
            break;
    }
  }

}

