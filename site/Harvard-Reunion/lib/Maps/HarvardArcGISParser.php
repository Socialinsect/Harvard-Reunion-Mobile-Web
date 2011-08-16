<?php

/****************************************************************
 *
 *  Copyright 2011 The President and Fellows of Harvard College
 *  Copyright 2011 Modo Labs Inc.
 *
 *****************************************************************/

class HarvardArcGISParser extends ArcGISParser
{
    protected $layerClass = 'HarvardArcGISLayer';
}

class HarvardArcGISLayer extends ArcGISLayer
{
    public function getCategory() {
        return $this->parentCategory;
    }
}


