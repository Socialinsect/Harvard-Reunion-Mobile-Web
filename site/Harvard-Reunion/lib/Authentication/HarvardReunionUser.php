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
    
    public function getGraduationClass() {
        return $this->getClass_year();
    }
    
    public function setCollegeIndex($collegeIndex) {
      if ($collegeIndex == 0 || $collegeIndex == 1) {
        $this->setUserData('collegeIndex', $collegeIndex);
      }
    }

    public function getCollegeIndex() {
      $collegeIndex = $this->getUserData('collegeIndex');
      return isset($collegeIndex) ? $collegeIndex : 0;  // default to Harvard
    }
    
    public function clearCollegeIndex() {
      $this->setUserData('collegeIndex', null);
    }

    protected function standardAttributes() {
        return array('class_year');
    }
    
    public function needsCollegeIndex() {
      return Schedule::reunionClassesAreSeparate($this->class_year) && $this->getUserData('collegeIndex') === null;
    }
}
