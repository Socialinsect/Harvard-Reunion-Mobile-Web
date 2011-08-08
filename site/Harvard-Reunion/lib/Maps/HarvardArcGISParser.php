<?php

/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
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


