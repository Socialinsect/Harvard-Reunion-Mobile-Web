<?php

/****************************************************************
 *
 *  Copyright 2011 The President and Fellows of Harvard College
 *  Copyright 2011 Modo Labs Inc.
 *
 *****************************************************************/

require_once(LIB_DIR . '/RSS.php');

class GazetteRSSController extends RSSDataController
{
    protected $DEFAULT_PARSER_CLASS='GazetteRSSDataParser';
    protected $loadMore=true;
    const GAZETTE_NEWS_MAX_PAGES = 20;
    
    public function addFilter($var, $value)
    {
        switch ($var)
        {
            case 'search':
                $this->addFilter('s',$value);
                $this->addFilter('feed', 'rss2');
                $this->loadMore = false;
                break;
            default:
                return parent::addFilter($var, $value);
        }
    }
    
    public function getItem($id, $page=1)
    {
        $maxPages = self::GAZETTE_NEWS_MAX_PAGES; // to prevent runaway trains
        
        while ($page < $maxPages) {
            $items = $this->loadPage($page++);
            foreach ($items as $item) {
                if ($item->getGUID()==$id) {
                    return $item;
                }
            }
        }            
        
        return null;
    }
    
    public function items(&$start=0, $limit=null) 
    {
        if ($limit && $start % $limit != 0) {
            $start = floor($start/$limit)*$limit;
        }
        
        $items = parent::items(0, null); // get all the items
        $totalItems = count($items);
        
        if ($this->loadMore) {
            $page = 1;

            $maxPages = self::GAZETTE_NEWS_MAX_PAGES; // to prevent runaway trains
            
            // load new pages until we have enough content
            while ( ($start > $totalItems) && ($page < $maxPages)) {
                $moreItems = $this->loadPage(++$page);
                $totalItems += count($moreItems);
                $items = array_merge(array_values($items), array_values($moreItems));
            }
            
            if ($limit) {
                $items = array_slice($items, $start, $limit); // slice off what's not needed
                
                // see if we need to fill it out at the end
                if (count($items) < $limit && $page < $maxPages) {
                    $moreItems = $this->loadPage(++$page);
                    $items = array_merge(array_values($items), array_slice($moreItems, 0, $limit-count($items)));
                    $totalItems += count($moreItems);
                }
            }
            if ($page < $maxPages) {
              // let the caller know there are more items available on the next page
              $totalItems++;
            }
        } elseif ($limit) {
            $items = array_slice($items, $start, $limit); // slice off what's not needed
        }
        $this->totalItems = $totalItems; // override default item count
        
        return $items;
    }
    
    private function loadPage($page)
    {
        $this->addFilter('paged',$page);
        $items = parent::items(); // get non-paged view
        return $items;   
    }

    private function getRSSPageItems($page) {
        if ($page > 1) {
            $this->addFilter('paged', $page);
        } else {
            $this->removeFilter('paged');
        }

        $dom = new DomDocument();
        $dom->loadXML($this->getData());
        $items = $dom->getElementsByTagName('item');
        $totalCount = $items->length;
        
        $pageItems = array();
        
        for ($i = 0; $i < $items->length; $i++) {
            $item = $items->item($i);
            
            // Skip multimedia items for now
            $isMultimedia = false;
            $categoryTags = $item->getElementsByTagName('category');
            foreach ($categoryTags as $categoryTag) {
                if ($categoryTag->nodeValue == 'Multimedia') {
                    $isMultimedia = true;
                    break;
                }
            }
            if ($isMultimedia) {
                continue;
            }
            
            // translate enclosures to image tags for API compatibility
            $content = $item->getElementsByTagName('encoded')->item(0);
            $contentValue = $content->nodeValue;
            $contentHTML = new DOMDocument();
            $contentHTML->loadHTML('<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head>'.$contentValue);
            
            foreach ($contentHTML->getElementsByTagName('img') as $img) {
              if ($img->getAttribute('width') == '1') {
                $img->parentNode->removeChild($img);
                continue;
              }
              
              $newSrc = GazetteRSSEnclosure::getImageLoaderURL($img->getAttribute('src'), $w, $h);
              if ($newSrc) {
                $img->setAttribute('src', $newSrc);
                $img->setAttribute('width', 300);
                $img->setAttribute('height', 'auto');
    
              } else {
                $img->parentNode->removeChild($img);
              }
            }
            $newContent = $dom->createCDATASection($contentHTML->saveHTML());
            $content->replaceChild($newContent, $content->firstChild);
            
            // translate enclosures to image tags for API compatibility
            $enclosures = $item->getElementsByTagName('enclosure');
            foreach ($enclosures as $enclosure) {
                $type = $enclosure->getAttributeNode('type')->value;
                if (strpos($type, 'image/') === FALSE) { continue; }
                
                $url = $enclosure->getAttributeNode('url')->value;
                $width = 0;
                $height = 0;
                
                $url = GazetteRSSEnclosure::getImageLoaderURL($url, $width, $height);
                
                // only send real images
                if ($width > 1 && $height > 1) {
                    $enclosure->setAttributeNode(new DOMAttr('url', $url));
                    $image = $dom->createElement('image');
                    $image_url = $dom->createElement('url', $url);
                    $image->appendChild($image_url);
                    $item->appendChild($image);
                }
            }
            
            $pageItems[] = $item;
        }

        return $pageItems;
    }

    public function getRSSItems($start=0, $limit=null)
    {
        $this->removeFilter('paged');
        
        // Use the first page to get the RSS wrapper for the items
        $dom = new DomDocument();
        $document->preserveWhiteSpace = false; 
        $dom->loadXML($this->getData());

        // remove all items... we will add them back
        // note: can't remove items in DOM list from their parent when iterating
        $itemsToRemove = array();
        $items = $dom->getElementsByTagName('item');
        foreach ($items as $item) {
            $itemsToRemove[] = $item;
        }
        foreach ($itemsToRemove as $item) {
            $item->parentNode->removeChild($item); 
        }
        
        $page = 1;
        $items = $this->getRSSPageItems($page); // get all the items
        $totalCount = count($items);
        
        $maxPages = self::GAZETTE_NEWS_MAX_PAGES; // to prevent runaway trains
        
        if ($this->loadMore) {
            // load new pages until we have enough content
            while (($start > $totalCount) && ($page < $maxPages)) {
                $moreItems = $this->getRSSPageItems(++$page);
                $items = array_merge($items, $moreItems);
                $totalCount += count($moreItems);
            }
            
            if ($limit) {
                $items = array_slice($items, $start, $limit); // slice off what's not needed
                
                // see if we need to fill it out at the end
                if (count($items) < $limit && $page < $maxPages) {
                    $moreItems = $this->getRSSPageItems(++$page);
                    $items = array_merge($items, array_slice($moreItems, 0, $limit - count($items)));
                    $totalCount += count($moreItems);
                }
            }
            if ($page < $maxPages) {
                // let the caller know there are more items available on the next page
                $totalCount += 10;
            }
        } elseif ($limit) {
            $items = array_slice($items, $start, $limit); //slice off what's not needed
        }

        $channel = $dom->getElementsByTagName('channel')->item(0);
        foreach ($items as $item) {
            $newItem = $dom->importNode($item, true);
            $channel->appendChild($newItem);
        }
        
        // iPhone expects this items attr to be set on the channel
        $channel->setAttributeNode(new DOMAttr('items', $totalCount));
        
        return $dom->saveXML();
    }

    public function getRSSIndexForItem($id)
    {
        if (!$id) {
            return null;
        }
        
        $maxPages = self::GAZETTE_NEWS_MAX_PAGES; // to prevent runaway trains
        $itemIndex = 0;
        
        for ($page = 1; $page <= $maxPages; $page++) {
            if ($page > 1 && !$this->loadMore) {
                break;
            }
            
            if ($page > 1) {
                $this->addFilter('paged', $page);
            } else {
                $this->removeFilter('paged');
            }
            
            $rssXML = $this->getRSSItems();
            $dom = new DomDocument();
            $dom->loadXML($rssXML);
            $items = $dom->getElementsByTagName('item');
            
            for ($i = 0; $i < $items->length; $i++) {
                $item = $items->item($i);
                
                $wpid = $item->getElementsByTagName('WPID');
                $wpid = $wpid->length ? $wpid->item(0)->nodeValue : '';
  
                $guid = $item->getElementsByTagName('guid');
                $guid = $guid->length ? $guid->item(0)->nodeValue : '';
                
                if ($guid == $id || $wpid == $id) {
                    return $itemIndex;
                }
                $itemIndex++;
            }
        }
        
        return null;
    }
}

class GazetteRSSDataParser extends RSSDataParser {
    public function parseData($contents) {
      parent::parseData($contents);
      
      foreach (array_keys($this->items) as $i) {
          $categories = $this->items[$i]->getCategories();
          foreach ($categories as $category) {
              if ($category == 'Multimedia') {
                  unset($this->items[$i]);
                  continue;
              }
          }
      }
      
      return $this->items;
    }
}

class GazetteRSSItem extends RSSItem
{
    public function addElement(XMLElement $element)
    {
        $name = $element->name();
        $value = $element->value();
        
        switch ($name)
        {
            case 'enclosure':
                if ($element->isImage()) {
                    if ($element->getProperty('width')>1) {
                        $this->enclosure = $element;
                    }
                }
                break;
            default:
                parent::addElement($element);
                break;
        }
        
    }
}

class GazetteRSSEnclosure extends RSSEnclosure
{
    protected $width = null;
    protected $height = null;
    
    public function __construct($attribs) {
        parent::__construct($attribs);
      
        if ($this->isImage()) {
          $this->url = self::getImageLoaderURL($this->url, $this->width, $this->height);
        }
    }
    
    protected function standardAttributes()
    {
        $attributes = array_merge(parent::standardAttributes(),array(
            'width',
            'height'));
        return $attributes;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function getWidth()
    {
        return $this->height;
    }

    public static function getImageLoaderURL($url, &$width, &$height) {
        if ($url && strpos($url, '/photo-placeholder.gif') !== FALSE) {
            $url = ''; // skip empty placeholder image 
        }
        
        if ($url) {
            switch ($GLOBALS['deviceClassifier']->getPagetype()) {
                case 'compliant':
                    $width = 140;
                    $height = 140;
                    break;
                
                case 'basic':
                case 'touch':
                default:
                    $width = 70;
                    $height = 70;
                    break;
            }
          
            $extension = pathinfo($url, PATHINFO_EXTENSION);
            if ($extension) { $extension = ".$extension"; }
  
            $url = ImageLoader::precache($url, $width, $height, 'Gazette_'.md5($url).$extension);
        }
        
        return $url;
    }
}
