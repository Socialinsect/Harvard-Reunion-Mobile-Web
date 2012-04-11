<?php

/****************************************************************
 *
 *  Copyright 2010-2012 The President and Fellows of Harvard College
 *  Copyright 2010-2012 Modo Labs Inc.
 *
 *****************************************************************/

includePackage('Gazette');

class GazetteDataModel extends ItemListDataModel {
    protected $DEFAULT_RETRIEVER_CLASS='GazetteDataRetriever';
    protected $DEFAULT_PARSER_CLASS='GazetteDataParser';
    protected $compatAPIMode = false;

    public function setCompatAPIMode($compatAPIMode) {
        $this->compatAPIMode = $compatAPIMode;
        $this->retriever->setCompatAPIMode($compatAPIMode);
    }

    protected function getMaxPages() {
        if ($this->retriever->hasSearchFilter()) {
            return 4; // searches load all pages... small to avoid timeouts
        } else {
            return 20; // non-searches only load 1 page at a time
        }
    }

    protected function itemHasId($item, $id) {
        if ($this->compatAPIMode) {
            $wpid = $item->getElementsByTagName('WPID');
            $wpid = $wpid->length ? $wpid->item(0)->nodeValue : '';

            $guid = $item->getElementsByTagName('guid');
            $guid = $guid->length ? $guid->item(0)->nodeValue : '';
            
            if ($guid == $id || $wpid == $id) {
                return true;
            }
        } else if ($item->getGUID() == $id) {
            return true;
        }
        
        return false;
    }

    protected function getItemAndIndex($id) {
        $itemIndex = 0;
        for ($page = 1; $page <= $this->getMaxPages(); $page++) {
            foreach ($this->itemsForPage($page) as $item) {
                if ($this->itemHasId($item, $id)) {
                    return array($item, $itemIndex);
                }
                $itemIndex++;
            }
        }
        
        return array(null, false);
    }

    public function getItem($id) {
        list($item, $index) = $this->getItemAndIndex($id);
        return $item;
    }

    public function getIndexForItem($id) {
        list($item, $index) = $this->getItemAndIndex($id);
        return $index;
    }

    public function items() {
        $start = $this->getStart() > 0 ? $this->getStart() : 0;
        $limit = $this->getLimit();
        $end = isset($limit) && $limit > 0 ? $start + $limit - 1 : PHP_INT_MAX;
        
        $totalItems = 0;
        $items = array();
        
        // load new pages until we have enough content
        $page = 1;
        $itemIndex = 0;
        $gotAllItems = false;
        $reachedLimit = false;
        for ($page = 1; $page <= $this->getMaxPages(); $page++) {
            $pageItems = $this->itemsForPage($page);

            $totalItems += count($pageItems);
            
            if ($this->retriever->hasSearchFilter() && !$pageItems) {
                // When we are searching we want to load all pages to get the
                // full item count.  This stops us at the first empty page.
                break;
            }
            
            foreach ($pageItems as $i => $item) {
                if (($itemIndex >= $start) && ($itemIndex <= $end)) {
                    $items[] = $item;
                }
                $itemIndex++;
            }
            
            if (!$this->retriever->hasSearchFilter() && $itemIndex > $end) {
                // If we are not searching stop when we get to the limit
                // or we may load the maximum number of pages
                if ($totalItems <= $itemIndex) {
                    // let the caller know there are more items on the next page
                    $totalItems += 10;
                }
                break;
            }
        }
        $this->setTotalItems($totalItems);
        
        if ($this->compatAPIMode) {
            return $this->compatAPIXMLForItems($items);
        } else {
            return $items;
        }
    }

    protected function itemsForPage($page) {
        $this->retriever->addFilter('paged', $page);
        $items = $this->retriever->getData();
        $this->retriever->removeFilter('paged');
        
        return $items;
    }

    public function search($searchTerms) {
        // Leverage paging code in items()
        $this->addFilter('search', $searchTerms);
        $items = $this->items();
        $this->removeFilter('search');
        
        return $items;
    }

    //
    // Compat API
    // The old Harvard native app API returns an RSS feed of the items
    // rather than the item objects.  The function below support this behavior.
    //

    protected function compatAPIXMLForItems($items) {
        // Use the first page to get the RSS wrapper for the items
        $dom = new DomDocument();
        $dom->loadXML($this->retriever->getResponse()->getResponse());
        
        // remove all items... we will add them back
        // note: can't remove items in DOM list from their parent when iterating
        $itemsToRemove = array();
        $pageItems = $dom->getElementsByTagName('item');
        foreach ($pageItems as $pageItem) {
            $itemsToRemove[] = $pageItem;
        }
        foreach ($itemsToRemove as $pageItem) {
            $pageItem->parentNode->removeChild($pageItem);
        }
        
        // Add the items to the now empty dom
        $channel = $dom->getElementsByTagName('channel')->item(0);
        foreach ($items as $item) {
            $newItem = $dom->importNode($item, true);
            $channel->appendChild($newItem);
        }
        
        // iPhone expects this items attr to be set on the channel
        $channel->setAttributeNode(new DOMAttr('items', $this->getTotalItems()));
        
        return $dom->saveXML();
    }
}
