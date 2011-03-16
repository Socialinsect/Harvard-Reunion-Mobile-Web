<?php

class HarvardReunionUser extends User 
{
    protected $class_year;
    protected $collegeIndex = 0;

    public function setClass_year($class_year) {
        $this->class_year = $class_year;
    }

    public function getClass_year() {
        return $this->class_year;
    }
    
    public function getGraduationClass() {
        return $this->getClass_year();
    }
    
    public function setCollegeIndex($collegeIndex) {
      $this->collegeIndex = $collegeIndex;
    }

    public function getCollegeIndex() {
      return $this->collegeIndex;
    }

    protected function standardAttributes() {
        return array('class_year');
    }
}