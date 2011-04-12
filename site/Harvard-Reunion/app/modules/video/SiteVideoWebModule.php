<?php
/**
  * @package Module
  * @subpackage Schedule
  */
/**
  */
includePackage('Authentication');
  
class SiteVideoWebModule extends WebModule {
  protected $id = 'video';
  protected $schedule = null;
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
        'url'   => $facebook->getLoginURL(),
        'items' => 'videos',
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
    if ($this->pagetype == 'basic' || $this->pagetype == 'touch') {
      $this->redirectToModule('home', 'index');
    }
  
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
      
        $view = $this->getArg('view', 'all');

        $myId = $facebook->getMyId();

        $posts = $facebook->getGroupVideos();
        foreach ($posts as $i => $post) {
          if (!$this->postMatchesView($post, $view, $myId)) {
            unset($posts[$i]);
            continue;
          }
          
          $posts[$i]['url'] = $this->buildBreadcrumbURL('detail', array( 
            'id' => $post['id'],
          ));
        }
        
        $views = array(
          'all'        => $this->buildViewURL('all'),
          'mine'       => $this->buildViewURL('mine'),
          'bookmarked' => $this->buildViewURL('bookmarked'),
        );

        $this->assign('user',          $facebook->getUserFullName());
        $this->assign('switchUserURL', $facebook->getSwitchUserURL());

        $this->assign('views',         $views);
        $this->assign('videos',        $posts);
        break;
              
      case 'detail':
        if (!$this->checkLoginStatus($facebook)) { break; }

        $postId = $this->getArg('id');
        
        $this->addOnOrientationChange('setTimeout(resizeVideoFrame, 0);');
        $this->generateBookmarkOptions($postId);
        
        $postDetails = $facebook->getVideoPost($postId);
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
        $posts = $facebook->getGroupVideoOrder();
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
        
        $commentURL = URL_BASE.API_URL_PREFIX."/home/comments?".http_build_query(array(
          'id' => $postId,
        ));
        $this->addInternalJavascript('/common/javascript/lib/messagelist.js');
        $this->addInlineJavascript('var MESSAGE_LIST_AJAX_URL = "'.$commentURL.'"');
        $this->addOnLoad('initMessageList();');

        $this->assign('video', $postDetails);
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
