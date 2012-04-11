<?php

/****************************************************************
 *
 *  Copyright 2010-2012 The President and Fellows of Harvard College
 *  Copyright 2010-2012 Modo Labs Inc.
 *
 *****************************************************************/

class GazetteDataRetriever extends URLDataRetriever {
    protected $supportsSearch = true;
    protected $hasSearchFilter = false;
    protected $compatAPIMode = false;
    
    public function hasSearchFilter() {
        return $this->hasSearchFilter;
    }

    public function setCompatAPIMode($compatAPIMode) {
        $this->compatAPIMode = $compatAPIMode;
    }
    
    public function addFilter($var, $value) {
        switch ($var) {
            case 'search':
                $this->addFilter('s', $value);
                $this->addFilter('feed', 'rss2');
                $this->hasSearchFilter = true;
                break;
                
            default:
                return parent::addFilter($var, $value);
        }
    }
    
    public function removeFilter($var) {
        switch ($var) {
            case 'search':
                parent::removeFilter('s');
                parent::removeFilter('feed');
                $this->hasSearchFilter = false;
                break;
            
            case 's':
                $this->hasSearchFilter = false;
                // fallthrough
            default:
                return parent::removeFilter($var);
        }
    }
    
    public function removeAllFilters() {
        parent::removeAllFilters();
        $this->hasSearchFilter = false;
    }

    public function search($searchTerms) {
        $this->addFilter('search', $searchTerms);
        $data = $this->getData();
        $this->removeFilter('search');
        
        return $data;
    }
    
    public function getData(&$response=null) {
        $parser = $this->parser();
        
        if ($parser->getParseMode() == DataParser::PARSE_MODE_RESPONSE && $this->compatAPIMode) {
            $response = $this->getResponse();
            $data = $parser->compatAPIParseResponse($response);
            
        } else {
            $data = parent::getData($response);
        }
        
        return $data;
    }
}
