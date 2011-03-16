<?php

class HarvardReunionUser extends User 
{
    protected $class_year;

    public function setClass_year($class_year) {
        $this->class_year = $class_year;
    }

    public function getClass_year() {
        return $this->class_year;
    }

    protected function standardAttributes() {
        return array('class_year');
    }
}