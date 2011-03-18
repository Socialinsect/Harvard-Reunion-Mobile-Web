<?php

class HarvardReunionUser extends User 
{
    protected $class_year;
    protected $collegeIndex = null;

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
      if ($collegeIndex == 0 || $collegeIndex == 1) {
        $this->collegeIndex = $collegeIndex;
      }
    }

    public function getCollegeIndex() {
      return isset($this->collegeIndex) ? $this->collegeIndex : 0;  // default to Harvard
    }

    protected function standardAttributes() {
        return array('class_year');
    }
    
    public function needsCollegeIndex() {
      return Schedule::reunionClassesAreSeparate($this->class_year) && $this->collegeIndex === null;
    }
}
