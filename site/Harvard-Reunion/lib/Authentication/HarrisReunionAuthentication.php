<?php

class HarrisReunionAuthentication extends AuthenticationAuthority
{
    protected $returnHeaders=array();
    protected $testing = true;
    
    protected function auth($login, $password, &$user) {
    
        if ($this->testing) {
            $user = $this->getUser($login);
            
            $testUsers = array(
              'john.smith'     => 'johnsmith',
              'jason.park'     => 'jasonpark',
              'janet.leary'    => 'janetleary',
              'janice.fisher'  => 'janicefisher',
              'jane.doe'       => 'janedoe',
              'alex.smith'     => 'alexsmith',
              'cynthia.fisher' => 'cynthiafisher',
              'joe.leary'      => 'joeleary',
              'darin.fisher'   => 'darinfisher',
              'mary.park'      => 'marypark',
              'shanon.doe'     => 'shanondoe',
              'dana.park'      => 'danapark',
              'dan.smith'      => 'dansmith',
              'pat.leary'      => 'patleary',
              'sam.fisher'     => 'samfisher',
              'chris.doe'      => 'chrisdoe',
            );
            
            if (isset($testUsers[$login])) {
              return $testUsers[$login] == $password ? AUTH_OK : AUTH_FAILED;
            }
            
            // If not one of the test users, fall through to Harris
        }
    
        $url = 'https://cayman.alumniconnections.com/olc/pub/HAA/login/app.sph/olclogin.app';
        $params = array(
            'referer'        => "https://cayman.alumniconnections.com/olc/membersonly/HAA/login/dboard_access.cgi?key=harvard&amp;q=emreunion&amp;event_id=1763952",
            'SaFormName'     => 'SubmitLogin__Floginform_html',
            'error_redirect' => '/olc/pub/HAA/login/m_failure.cgi',
            'username'       => $login,
            'password'       => $password
        );
    
        if (!is_dir(CACHE_DIR . "/Harris")) {
            mkdir(CACHE_DIR . "/Harris");
        }
        
        $curl = curl_init();
        $opts = array(
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($params),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR      => CACHE_DIR . "/Harris/cookie.txt", // need cookies for it to work
            CURLOPT_COOKIEFILE     => CACHE_DIR . "/Harris/cookie.txt",
        );

        curl_setopt_array($curl, $opts);
        $result = curl_exec($curl);
        
        if (preg_match("#<data><status>403</status></data>#", $result)) {
            return AUTH_FAILED;
            
        } elseif (preg_match('/"first","last","lname_as_student","class_year","email","display"/', $result)) {
            file_put_contents(CACHE_DIR . "/Harris/" . md5($login), $result);
            $user = $this->getUser($login);
            return AUTH_OK;
            
        } elseif (empty($result)) {
            return AUTH_ERROR;
            
        } else {
            error_log("Unhandled Harris output: '$result'");
            throw new Exception("Unhandled Harris output");
        }        
    }

    public function getUser($login) {
        $file = CACHE_DIR . "/Harris/" . md5($login);
        if (is_file($file)) {
            if (($fh = fopen($file, 'r')) !== FALSE) {
                $row = 0;
                while (($data = fgetcsv($fh, 2000, ",")) !== FALSE) {
                    if ($row == 0) {
                        $fields = $data;
                        
                    } elseif (count($data) == count($fields)) {
                        $data = array_combine($fields, $data);
                        
                        if ($this->testing) {
                          switch ($login) {
                            case 'ormsbee':
                              $data['class_year'] = '1976';
                          }
                        }
                        
                        $user = new HarrisReunionUser($this);
                        $user->setUserID($login);
                        foreach ($data as $field => $value) {
                            $user->setAttribute($field, $value);
                        }
                    }
                    $row++;
                }
                fclose($fh);
                
                return $user;
            }
        }

        return false;
    }

    public function getGroup($group) {
        return false;
    }
    
    public function logout(Session $session, $hard=false)
    {
        if ($hard) {
          // On hard logout flush the user's college selection
          $user = $session->getUser($this);
          if ($user) {
            $user->clearCollegeIndex();
          }
        }

        parent::logout($session, $hard);
    }
    
    public function validate(&$error) {
        return true;
    }
}

class HarrisReunionUser extends HarvardReunionUser 
{
    protected $lname_as_student;
    protected $display;
    
    protected function setFirst($first) {
        $this->setFirstName($first);
    }

    protected function setLast($last) {
        $this->setLastName($last);
    }

    protected function setLname_as_student($lname_as_student) {
        $this->lname_as_student = $lname_as_student;
    }

    protected function setDisplay($display) {
        $this->display = $display;
    }
    
    public function setClass_year($class_year) {
      $this->class_year = $class_year;
    }
    
    protected function standardAttributes() {
        return array_merge(parent::standardAttributes(), array('first','last','lname_as_student','email','display'));
    }
}