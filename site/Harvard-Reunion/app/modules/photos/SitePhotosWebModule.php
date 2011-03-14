<?php
/**
  * @package Module
  * @subpackage Schedule
  */
/**
  */
includePackage('Authentication');
  
class SitePhotosWebModule extends WebModule {
  protected $id = 'photos';
  protected $schedule = null;
  protected $bookmarkCookie = 'mapbookmarks';
  protected $bookmarkLifespan = 25237;

  protected function generateBookmarkOptions($cookieID) {
    // compliant branch
    $this->addOnLoad("setBookmarkStates('{$this->bookmarkCookie}', '{$cookieID}')");
    $this->assign('cookieName', $this->bookmarkCookie);
    $this->assign('expireDate', $this->bookmarkLifespan);
    $this->assign('bookmarkItem', $cookieID);

    // the rest of this is all touch and basic branch
    if (isset($this->args['bookmark'])) {
      if ($this->args['bookmark'] == 'add') {
        $this->addBookmark($cookieID);
        $status = 'on';
        $bookmarkAction = 'remove';
      } else {
        $this->removeBookmark($cookieID);
        $status = 'off';
        $bookmarkAction = 'add';
      }

    } else {
      if ($this->hasBookmark($cookieID)) {
        $status = 'on';
        $bookmarkAction = 'remove';
      } else {
        $status = 'off';
        $bookmarkAction = 'add';
      }
    }

    $this->assign('bookmarkStatus', $status);
    $this->assign('bookmarkURL', $this->bookmarkToggleURL($bookmarkAction));
    $this->assign('bookmarkAction', $bookmarkAction);
  }

  private function bookmarkToggleURL($toggle) {
    $args = $this->args;
    $args['bookmark'] = $toggle;
    return $this->buildBreadcrumbURL($this->page, $args, false);
  }

  protected function getBookmarks() {
    $bookmarks = array();
    if (isset($_COOKIE[$this->bookmarkCookie])) {
      $bookmarks = explode(",", $_COOKIE[$this->bookmarkCookie]);
    }
    return $bookmarks;
  }
  
  protected function setBookmarks($bookmarks) {
    $values = implode(",", $bookmarks);
    $expireTime = time() + $this->bookmarkLifespan;
    setcookie($this->bookmarkCookie, $values, $expireTime, COOKIE_PATH);
  }
  
  protected function addBookmark($aBookmark) {
    $bookmarks = $this->getBookmarks();
    if (!in_array($aBookmark, $bookmarks)) {
      $bookmarks[] = $aBookmark;
      $this->setBookmarks($bookmarks);
    }
  }
  
  protected function removeBookmark($aBookmark) {
    $bookmarks = $this->getBookmarks();
    $index = array_search($aBookmark, $bookmarks);
    if ($index !== false) {
      array_splice($bookmarks, $index, 1);
      $this->setBookmarks($bookmarks);
    }
  }
  
  protected function hasBookmark($aBookmark) {
    return in_array($aBookmark, $this->getBookmarks());
  }
 
  private function buildViewURL($view='all') {
    $args = $this->args;
    $args['view'] = $view;
    
    return $this->buildBreadcrumbURL($this->page, $args, false);
  }

  protected function initializeForPage() {
    $user = $this->getUser();
    $session = $user->getSessionData();
    
    $this->schedule = new Schedule();
    $facebook = new FacebookGroup($this->schedule->getFacebookGroupId(), $session['fb_access_token']);
    
    switch ($this->page) {
      case 'help':
        break;

      case 'index':
        $view = $this->getArg('view', 'all');

        $myId = $facebook->getMyId();

        $photos = $facebook->getGroupPhotos();
        foreach ($photos as $i => $photo) {
          if (($view == 'mine'       && $photo['author']['id'] != $myId) ||
              ($view == 'bookmarked' && !$this->hasBookmark($photo['id']))) {
            unset($photos[$i]);
            continue;
          }
          
          $photos[$i]['url'] = $this->buildBreadcrumbURL('detail', array( 
            'id' => $photo['id'],
          ));
        }
        

        $views = array(
          'all'        => $this->buildViewURL('all'),
          'mine'       => $this->buildViewURL('mine'),
          'bookmarked' => $this->buildViewURL('bookmarked'),
        );
        

        $this->assign('user',        $user->getFullName());
        $this->assign('logoutURL',   self::buildURLForModule('login', 'logout', array(
          'authority' => 'facebook'
        )));

        $this->assign('views',       $views);
        $this->assign('currentView', $view);

        $this->assign('title',       $facebook->getGroupFullName());
        $this->assign('photos',      $photos);
        break;
              
      case 'detail':
        $postId = $this->getArg('id');
      
        $this->generateBookmarkOptions($postId);
        
        $this->assign('photo', $facebook->getPhotoPost($postId));
        break;
        
      case 'comment':
        $postId = $this->getArg('id');
        if (isset($this->args['comment'])) {
          $facebook->addComment($postId, $this->args['comment']);
        }
        $this->redirectTo('detail', array(
          'id' => $postId,
        ), true);
        break;
    }
  }
}
