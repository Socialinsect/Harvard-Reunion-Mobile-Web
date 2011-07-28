<?php

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


