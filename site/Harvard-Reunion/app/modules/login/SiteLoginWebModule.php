<?php

/****************************************************************
 *
 *  Copyright 2011 The President and Fellows of Harvard College
 *  Copyright 2011 Modo Labs Inc.
 *
 *****************************************************************/

class SiteLoginWebModule extends LoginWebModule
{
    private function logLogin(HarvardReunionUser $user, $nativeApp, $tabletDisplay) {
        $logFile = Kurogo::getSiteVar('LOGIN_LOG');
        $school = $user->getCollegeIndex() == 0 ? 'Harvard' : 'Radcliffe';
        $pagetype = $nativeApp ? 'native' : $this->pagetype;
        $userID = $user instanceOf AnonymousReunionUser  ? 'anonymous' : $user->getUserID();
        $platform = $tabletDisplay ? 'ipad' : $this->platform;
        $line = implode(",", array(
            date('Y-m-d H:i:s'), 
            $userID,
            $user->getClass_year(), 
            $school, 
            $pagetype, 
            $platform,
        )). PHP_EOL;
        $fh = fopen($logFile,'ab');
        fwrite($fh, $line);
        fclose($fh);
    }

  protected function initializeForPage() {
    $tabletDisplay = stripos($_SERVER['HTTP_USER_AGENT'], '(ipad;') !== FALSE;
    $nativeApp = $this->getArg('nativeApp', false);
    
    $this->assign('tabletDisplay', $tabletDisplay);
    
    // Default args to pass through forms and urls
    $defaultArgs = array();
    if ($nativeApp) {
        $defaultArgs['nativeApp'] = 1;
    }
    
    // If this is a native app, use the native app GA id
    if ($nativeApp) {
        $this->assign('GOOGLE_ANALYTICS_ID', 
          Kurogo::getOptionalSiteVar('GOOGLE_ANALYTICS_NATIVE_ID'));
    }

    // return url
    $url = $this->getArg('url');
    if ($nativeApp) {
        // send native apps to special loading page
        $url = URL_PREFIX.ltrim($this->buildURLForModule($this->id, 'loading', $defaultArgs), '/');
        
    } else if (!$url || strpos($url, URL_PREFIX.'info/') === 0) {
        $url = URL_PREFIX.ltrim($this->buildURLForModule('home', 'index', $defaultArgs), '/');
    }

    $defaultArgs['url'] = $url;

    $this->assign('defaultArgs', $defaultArgs);

    $session  = $this->getSession();

    // Always allow the user to remain logged in for the full duration
    $remainLoggedInTime = Kurogo::getOptionalSiteVar('AUTHENTICATION_REMAIN_LOGGED_IN_TIME', 0);
    $session->setRemainLoggedIn($remainLoggedInTime);
    
    switch ($this->page) {
        case 'loading':
            // Do not track this page.  It reloads every second!
            $this->assign('GOOGLE_ANALYTICS_ID', '');
            break;
            
        case 'logoutConfirm':
            $authorityIndex = $this->getArg('authority');
            
            if (!$this->isLoggedIn($authorityIndex)) {
                $this->redirectTo('index', $defaultArgs);
                
            } elseif ($user = $this->getUser($authorityIndex)) {
                $authority = $user->getAuthenticationAuthority();
                
                $this->setTemplatePage('message');
                $this->assign('message', "You are logged in as ".$user->getFullName().
                    ($multipleAuthorities ? ' (' . $authority->getAuthorityTitle() . ')' : ''));
                $this->assign('url', $this->buildURL('logout', array_merge($defaultArgs, array(
                    'authority' => $authorityIndex,
                ))));
                $this->assign('linkText', 'Logout');
                
            } else {
                $this->redirectTo('index', $defaultArgs);
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
              $this->redirectTo('index', $defaultArgs);
            }
            break;
            
        case 'login':
            $login          = $this->argVal($_POST, 'loginUser', '');
            $password       = $this->argVal($_POST, 'loginPassword', '');
            $authorityIndex = $this->getArg('authority', AuthenticationAuthority::getDefaultAuthenticationAuthorityIndex());
            
            $options = array_merge($defaultArgs, array(
                'authority' => $authorityIndex,
            ));
            
            $logoutOptions = array_merge($defaultArgs, array(
                'authority' => $authorityIndex,
                'hard'      => true,
            ));
            
            $noReunionOptions = array_merge($defaultArgs, array(
                'authority' => $authorityIndex,
                'hard'      => true,
                'url'       => URL_PREFIX.ltrim($this->buildURL('index', array_merge($defaultArgs, array(
                    'noreunion' => 'true',
                ))), '/'),
            ));
            
            if (isset($this->args['login_cancel'])) {
                if ($this->isLoggedIn($authorityIndex)) {
                    $this->redirectTo('logout', $logoutOptions);
                
                } else {
                    $this->redirectTo('index', $defaultArgs);
                }
            }
            
            $this->assign('cancelURL', $this->buildURL('logout', $logoutOptions));

            if ($this->isLoggedIn($authorityIndex)) {
                $user = $this->getUser($authorityIndex);
                if ($user->needsCollegeIndex() && isset($_POST['collegeIndex'])) {
                    $user->setCollegeIndex($_POST['collegeIndex']);
                }
                
                if ($user->needsCollegeIndex()) {
                    $this->setTemplatePage('college');
                } else if (!Schedule::userHasReunion($user)) {
                    if ($nativeApp && $authority = AuthenticationAuthority::getAuthenticationAuthority($authorityIndex)) {
                        $result = $session->logout($authority, true);
                        $this->redirectTo('index', array_merge($defaultArgs, array(
                            'noreunion' => 'true',
                        )));
                    }
                    $this->redirectTo('logout', $noReunionOptions);
                } else {
                    if (isset($_POST['collegeIndex'])) {
                        $this->logLogin($user, $nativeApp, $tabletDisplay);
                    }
                    $this->redirectTo('index', $options);
                }
                
            } else {
                if ($authorityIndex != 'anonymous' && (empty($login) || empty($password))) {
                  $this->setTemplatePage($authorityIndex);
                  $this->assign('authFailed', true);
                  break;
                
                } else if ($authorityIndex == 'anonymous' && empty($login)) {
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
                        if ($user->needsCollegeIndex()) {
                            $this->setTemplatePage('college');
                        } else if (!Schedule::userHasReunion($user)) {
                            if ($nativeApp && $authority = AuthenticationAuthority::getAuthenticationAuthority($authorityIndex)) {
                                $result = $session->logout($authority, true);
                                $this->redirectTo('index', array_merge($defaultArgs, array(
                                    'noreunion' => 'true',
                                )));
                            }
                            $this->redirectTo('logout', $noReunionOptions);
                        } else {
                            $this->logLogin($user, $nativeApp, $tabletDisplay);
                            header("Location: $url");
                            exit();
                        }
                        break;
    
                    case AUTH_FAILED:
                    case AUTH_USER_NOT_FOUND:
                        $this->setTemplatePage($authorityIndex);
                        $this->assign('authFailed', true);
                        break;
                    
                    case AUTH_HARRIS_ERROR:
                        $this->setTemplatePage($authorityIndex);
                        $this->assign('authHarrisError', true);
                        break;
                    
                    default:
                        $this->setTemplatePage('error');
                        $this->assign('continueURL', URL_PREFIX.ltrim($this->buildURL('index', $options)));
                        $this->assign('resultCode',  $result);
                }
            }
            break;
            
        case 'index':
            if ($this->isLoggedIn()) {
                header("Location: $url");
                exit();
            
            } elseif ($this->getArg('noreunion', false)) {
                $this->setTemplatePage('noreunion');
                $this->assign('loginURL', $this->buildURL('index', $defaultArgs));
            
            } elseif ($tabletDisplay) {
                $this->assign('reunionYears', Schedule::getAllReunionYears());
                $this->assign('suppressiOSLink', $nativeApp);
                $this->assign('isNative', $nativeApp);

            } elseif ($authority = $this->getArg('authority')) {
                if ($authority == 'anonymous') {
                    $this->assign('reunionYears', Schedule::getAllReunionYears());
                }
                $this->setTemplatePage($authority);
            
            } else {
                $harrisArgs = array_merge($defaultArgs, array(
                    'authority' => 'harris', 
                ));
                $anonymousArgs = array_merge($defaultArgs, array(
                    'authority' => 'anonymous', 
                ));
            
                $this->assign('harrisURL',       $this->buildURL($this->page, $harrisArgs));
                $this->assign('anonymousURL',    $this->buildURL($this->page, $anonymousArgs));
                $this->assign('suppressiOSLink', $nativeApp);
                $this->assign('isNative', $nativeApp);
            }
            break;
      }
  }

}

