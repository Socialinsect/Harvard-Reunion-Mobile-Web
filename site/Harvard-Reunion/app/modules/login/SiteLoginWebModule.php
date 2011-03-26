<?php

class SiteLoginWebModule extends LoginWebModule
{

  protected function initializeForPage() {
    // return url
    $url = $this->getArg('url');
    if (!$url || strpos($url, URL_PREFIX.'info/') === 0) {
      $url = URL_PREFIX.ltrim($this->buildURLForModule('home', 'index'), '/');
    }
    $session  = $this->getSession();

    switch ($this->page) {
        case 'logoutConfirm':
            $authorityIndex = $this->getArg('authority');
            
            if (!$this->isLoggedIn($authorityIndex)) {
                $this->redirectTo('index', array());
                
            } elseif ($user = $this->getUser($authorityIndex)) {
                $authority = $user->getAuthenticationAuthority();
                
                $this->setTemplatePage('message');
                $this->assign('message', "You are logged in as ".$user->getFullName().
                    ($multipleAuthorities ? ' (' . $authority->getAuthorityTitle() . ')' : ''));
                $this->assign('url', $this->buildURL('logout', array(
                    'authority' => $authorityIndex
                )));
                $this->assign('linkText', 'Logout');
                
            } else {
                $this->redirectTo('index', array());
            }
            break;
            
        case 'logout':
            $this->setTemplatePage('message');
            $authorityIndex = $this->getArg('authority');
            $hard = $this->getArg('hard', false);

            if ($this->isLoggedIn($authorityIndex) && 
                $authority = AuthenticationAuthority::getAuthenticationAuthority($authorityIndex)) {
                $result = $session->logout($authority, $hard);
                
                // The following flushes facebook cookies so that the next
                // time the user logs in it will go back to facebook to check
                // if the app is still authorized.  We do not log the user 
                // completely out of facebook, just the app session.
                FacebookGroup::appLogout(); 
            }
            
            if (isset($this->args['url'])) {
              header("Location: $url");
              exit();
            } else {
              $this->redirectTo('index', array());
            }
            break;
            
        case 'login':
            $login          = $this->argVal($_POST, 'loginUser', '');
            $password       = $this->argVal($_POST, 'loginPassword', '');
            $authorityIndex = $this->getArg('authority', AuthenticationAuthority::getDefaultAuthenticationAuthorityIndex());
            
            $options = array(
                'url'       => $url,
                'authority' => $authorityIndex
            );
            
            $referrer = $this->argVal($_SERVER, 'HTTP_REFERER', '');
            $session->setRemainLoggedIn($this->getArg('remainLoggedIn', 0));
            
            $this->assign('authority', $authorityIndex);
            $this->assign('cancelURL', $this->buildURL('logout', array('authority'=>$authorityIndex)));
            $this->assign('url', $url);

            if ($this->isLoggedIn($authorityIndex)) {
                $user = $this->getUser($authorityIndex);
                if ($authorityIndex == 'harris' && $user->needsCollegeIndex() && isset($_POST['collegeIndex'])) {
                    $user->setCollegeIndex($_POST['collegeIndex']);
                    error_log(print_r($this->getUser($authorityIndex), true));
                }
                
                if ($authorityIndex == 'harris' && $user->needsCollegeIndex()) {
                    $this->setTemplatePage('college');
                } else {
                    $this->redirectTo('index', $options);
                }
                
            } else {
                if (empty($login)) {
                    $this->redirectTo('index', $options);
                }
                
                if ($authority = AuthenticationAuthority::getAuthenticationAuthority($authorityIndex)) {
                    $result = $authority->login($login, $password, $session, $options);
                } else {
                    error_log("Invalid authority $authorityIndex");
                    $this->redirectTo('index', $options);
                }
    
                switch ($result) {
                    case AUTH_OK:
                        $user = $this->getUser($authorityIndex);
                        if ($authorityIndex == 'harris' && $user->needsCollegeIndex()) {
                            $this->setTemplatePage('college');
                        } else {
                            header("Location: $url");
                            exit();
                        }
                        break;
    
                    case AUTH_FAILED:
                    case AUTH_USER_NOT_FOUND:
                        $this->setTemplatePage($authorityIndex);
                        $this->assign('authFailed', true);
                        break;
    
                    default:
                        $this->setTemplatePage('login');
                        $this->assign('message', "Login Failed. An unknown error occurred ($result)");
                }
            }
            break;
            
        case 'index':
            if ($this->isLoggedIn()) {
                header("Location: $url");
                exit();
            
            } elseif ($authority = $this->getArg('authority')) {
                if ($authority == 'anonymous') {
                  $this->assign('reunionYears', Schedule::getAllReunionYears());
                }
            
                $this->setTemplatePage($authority);
                $this->assign('url', $url);
                $this->assign('cancelURL', $this->buildURL('index', array('url'=>$url)));
            
            } else {
                $this->assign('harrisURL',    $this->buildURL($this->page, array('authority'=>'harris', 'url'=>$url)));
                $this->assign('anonymousURL', $this->buildURL($this->page, array('authority'=>'anonymous', 'url'=>$url)));
            }
            break;
    }
  }

}

