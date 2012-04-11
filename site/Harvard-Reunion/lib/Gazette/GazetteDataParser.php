<?php

/****************************************************************
 *
 *  Copyright 2010-2012 The President and Fellows of Harvard College
 *  Copyright 2010-2012 Modo Labs Inc.
 *
 *****************************************************************/

class GazetteDataParser extends RSSDataParser {
    protected $itemClass='GazetteItem';
    protected $imageEnclosureClass='GazetteImageEnclosure';
    
    public function parseData($contents) {
        parent::parseData(str_replace('&nbsp;', ' ', $contents));
        
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
    
    protected function enclosureIsImage($name, $attribs) {
        if (strpos($attribs['URL'], '/photo-placeholder.') !== false) {
            return false; // blank placeholder photo, ignore
        }
        return parent::enclosureIsImage($name, $attribs);
    }
    
    //
    // Compat API
    // The old Harvard native app API returns an RSS feed of the items
    // rather than the item objects.  The function below supports this behavior.
    //

    public function compatAPIParseResponse($response) {
        $dom = new DomDocument();
        $dom->loadXML(str_replace('&nbsp;', ' ', $response->getResponse()));
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
            $contentHTML->loadHTML('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head><body>'.$contentValue.'</body></html>');
            
            // note: can't remove items in DOM list from their parent when iterating
            $itemsToRemove = array();
            foreach ($contentHTML->getElementsByTagName('img') as $img) {
                if ($img->getAttribute('width') == '1') {
                    $itemsToRemove[] = $img;
                    continue;
                }
                
                $newSrc = self::compatGetImageLoaderURL($img->getAttribute('src'));
                if ($newSrc) {
                    // only send real images
                    $img->setAttribute('src', $newSrc);
                    $img->setAttribute('width', 200);
                    $img->setAttribute('height', 'auto');
      
                } else {
                    $itemsToRemove[] = $img;
                }
            }
            foreach ($contentHTML->getElementsByTagName('iframe') as $iframe) {
                $itemsToRemove[] = $iframe;
            }
            foreach ($itemsToRemove as $itemToRemove) {
                $itemToRemove->parentNode->removeChild($itemToRemove);
            }
            $newContent = $dom->createCDATASection($contentHTML->saveHTML());
            $content->replaceChild($newContent, $content->firstChild);
            
            // translate enclosures to image tags for API compatibility
            $enclosures = $item->getElementsByTagName('enclosure');
            foreach ($enclosures as $enclosure) {
                $type = $enclosure->getAttributeNode('type')->value;
                if (strpos($type, 'image/') === FALSE) { continue; }
                
                $url = self::compatGetImageLoaderURL($enclosure->getAttributeNode('url')->value, true);
                if ($url) {
                    // only send real images
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
    
    public static function compatGetImageLoaderURL($url, $cropToSquareThumb=false) {
        if ($url && strpos($url, '/photo-placeholder.') !== FALSE) {
            $url = ''; // skip empty placeholder image
        }
        
        if ($url) {
            $options = array();
            if ($cropToSquareThumb) {
                $options['max_width'] = 140;
                $options['max_height'] = 140;
                $options['crop'] = true;
            }
            $url = ImageLoader::cacheImage($url, $options);
        }
        
        return $url;
    }

}

class GazetteItem extends RSSItem
{
    public function addElement(XMLElement $element) {
        $name = $element->name();
        $value = $element->value();
        
        switch ($name) {
            case 'CONTENT:ENCODED':
                $this->content = $this->cleanEncodedContent($value);
                break;
                
            default:
                parent::addElement($element);
                break;
        }
    }
    
    protected function cleanEncodedContent($content) {
        $contentHTML = new DOMDocument();
        $contentHTML->loadHTML('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head><body>'.$content.'</body></html>');
        
        // note: can't remove items in DOM list from their parent when iterating
        $itemsToRemove = array();
        foreach ($contentHTML->getElementsByTagName('iframe') as $iframe) {
            $itemsToRemove[] = $iframe;
        }
        foreach ($itemsToRemove as $item) {
            $item->parentNode->removeChild($item);
        }
        return $contentHTML->saveHTML();
    }
    
    protected function elementMap() {
        return array_merge(parent::elementMap(), array(
            'HARVARD:AUTHOR'=>'author'
        ));
    }

    public function getFullSizeImage() {
        return $this->getProperty('harvard:featured_photo');
    }
}

class GazetteImageEnclosure extends RSSImageEnclosure
{
    public function init($args) {
        $pagetype = Kurogo::deviceClassifier()->getPagetype();
        $thumbSize = ($pagetype == 'basic' || $pagetype == 'touch') ? 70 : 140;
        
        if (!isset($args['THUMB_MAX_WIDTH'])) {
            $args['THUMB_MAX_WIDTH'] = $thumbSize;
        }
        if (!isset($args['THUMB_MAX_HEIGHT'])) {
            $args['THUMB_MAX_HEIGHT'] = $thumbSize;
        }
        if (!isset($args['THUMB_CROP'])) {
            $args['THUMB_CROP'] = true;
        }
        parent::init($args);
    }
}
