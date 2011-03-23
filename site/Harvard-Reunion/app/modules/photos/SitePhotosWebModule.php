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
  
  private function checkLoginStatus($facebook) {
    if ($facebook->needsLogin()) {
      $this->assign('needsLogin', true);
      $this->assign('service', array(
        'type'  => 'facebook',
        'name'  => 'Facebook',
        'url'   => $facebook->getNeedsLoginURL(),
        'items' => 'posts',
      ));
      return false;
      
    } else if (!$facebook->isMemberOfGroup()) {
      $this->assign('needsJoinGroup', true);
      $this->assign('groupName', $facebook->getGroupFullName());
      $this->assign('groupURL', $facebook->getGroupURL());
      return false;
    }
    
    return true;
  }
  
  protected function postMatchesView($post, $view, $myId) {
    return ($view != 'mine'       || $post['author']['id'] == $myId) &&
           ($view != 'bookmarked' || $this->hasBookmark($post['id']));
  }
  
  protected function initializeForPage() {
    $user = $this->getUser('HarvardReunionUser');
    $this->schedule = new Schedule($user);
    $facebook = $this->schedule->getFacebookFeed();
    
    $view = $this->getArg('view', 'all');
    $this->assign('currentView', $view);
    
    switch ($this->page) {
      case 'help':
        break;
        
      case 'index':
        if (!$this->checkLoginStatus($facebook)) { break; }
      
        $views = array(
          'all'        => $this->buildViewURL('all'),
          'mine'       => $this->buildViewURL('mine'),
          'bookmarked' => $this->buildViewURL('bookmarked'),
        );

        $myId = $facebook->getMyId();
    
        $posts = $facebook->getGroupPhotos();
        foreach ($posts as $i => $post) {
          if (!$this->postMatchesView($post, $view, $myId)) {
            unset($posts[$i]);
            continue;
          }
          
          $posts[$i]['url'] = $this->buildBreadcrumbURL('detail', array( 
            'id'   => $post['id'],
            'view' => $view,
          ));
        }

        $this->assign('user',          $facebook->getUserFullName());
        $this->assign('switchUserURL', $facebook->getSwitchUserURL());

        $this->assign('views',  $views);
        $this->assign('photos', $posts);
        break;
              
      case 'detail':
        if (!$this->checkLoginStatus($facebook)) { break; }

        $postId = $this->getArg('id');
      
        $this->generateBookmarkOptions($postId);
        
        $postDetails = $facebook->getPhotoPost($postId);
        $postDetails['comments'] = $facebook->getComments($postId);
    
        $myId = $facebook->getMyId();        
        
        $postDetails['liked'] = false;
        foreach ($facebook->getLikes($postId) as $i => $like) {
          if ($like['id'] == $myId) {
            $postDetails['liked'] = true;
          }
        }
        $postDetails['likeURL'] = $this->buildBreadcrumbURL('like', array(
          'id'     => $postId,
          'view'   => $view,
          'action' => $postDetails['liked'] ? 'remove' : 'add',
        ), false);
  
        $postDetails['prevURL'] = '';
        $postDetails['nextURL'] = '';
        $posts = $facebook->getGroupPhotoOrder();
        foreach ($posts as $i => $post) {
          if ($post['id'] == $postId) {
            $last = count($posts)-1;
            
            $prev = $i-1;
            while (!$postDetails['prevURL'] && $prev >= 0) {
              if ($this->postMatchesView($posts[$prev], $view, $myId)) {
                $postDetails['prevURL'] = $this->buildBreadcrumbURL('detail', array( 
                  'id'   => $posts[$prev]['id'],
                  'view' => $view,
                ), false);
              }
              $prev--;
            }
            
            $next = $i+1;
            while (!$postDetails['nextURL'] && $next <= $last) {
              if ($this->postMatchesView($posts[$next], $view, $myId)) {
                $postDetails['nextURL'] = $this->buildBreadcrumbURL('detail', array( 
                  'id'   => $posts[$next]['id'],
                  'view' => $view,
                ), false);
              }
              $next++;
            }
            break;
          }
        }

        $this->assign('photo', $postDetails);
        break;
        
      case 'comment':
        $postId = $this->getArg('id');
        $action = $this->getArg('action', 'add');
        
        if ($action == 'add') {
          $facebook->addComment($postId, $this->getArg('message'));
          
        } else if ($action == 'remove') {
          $facebook->removeComment($this->getArg('commentId'));
        }
        
        $this->redirectTo('detail', array(
          'id'   => $postId,
          'view' => $view,
        ), true);
        break;
        
      case 'like':
        $postId = $this->getArg('id');
        $action = $this->getArg('action', 'like');
        
        if ($action == 'add') {
          $facebook->like($postId);
          
        } else if ($action == 'remove') {
          $facebook->unlike($postId);
        }
        
        $this->redirectTo('detail', array(
          'id'   => $postId,
          'view' => $view,
        ), true);
        break;
    }
  }
}
