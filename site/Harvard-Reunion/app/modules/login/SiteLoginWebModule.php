<?php

class SiteLoginWebModule extends LoginWebModule
{

  protected function initializeForPage() {
    
    $url = $this->getArg('url', ''); //return url

    switch ($this->page)
    {
        case 'index':
            if ($this->isLoggedIn()) {
                $this->redirectToModule('home', 'index');
            } elseif ($authority = $this->getArg('authority')) {
                $this->setTemplatePage($authority);          
            } else {

                $this->assign('harrisURL',   $this->buildURL($this->page, array('authority'=>'harris', 'url'=>$url)));
                $this->assign('anonymousURL',$this->buildURL($this->page, array('authority'=>'anonymous', 'url'=>$url)));
            }
            break;
    }
  }

}

