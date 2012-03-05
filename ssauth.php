<?php 

  /*
   
   Stupid-Simple Authentication
   (c) 2012 Sporktronics, LLC
   http://www.sporktronics.com/
   
   Licensed under the Lesser GPL, version 3.0:
   http://www.gnu.org/licenses/lgpl-3.0.html
   
  */

// We get our very own Exception?? YAY! 
class SSAuthException extends Exception { }

class SSAuth {
  
  private $usersDir;
  private $sessionVar;
  private $users = array();
  
  // Takes the name of a directory to save user info in. If it's
  // empty, set it up. If it's not writable, or there's an error
  // setting it up, throw an exception.
  function __construct($usersDir) {
    session_start();
    
    $this->usersDir = realpath($usersDir);
    $this->sessionVar = 'SSAuthLoggedInUser:'.$this->usersDir;
    
    if (!is_writable($this->usersDir)) {
      throw new SSAuthException("Directory '$usersDir' is not writable");
    } else if (!$this->setupUsersDir()) {
      throw new SSAuthException("Couldn't set up users directory");
    }
    
  }
  
  /* Private functions */
  
  // If the users directory is empty, set it up.
  private function setupUsersDir() {
    if (!file_exists($this->usersDir.'/users.json')) {      
      return $this->saveUsers();
    } else {
      $this->users = $this->getUsers();
      return true;
    }
  }
  
  // Take the $users array and save it in the users directory.
  private function saveUsers() {
    return (file_put_contents($this->usersDir.'/users.json',
			      json_encode(array("users" => $this->users))));
  }
  
  /* Public functions */
  
  // Does the user exist?
  public function userExists($user) {
    $userId = $this->getUserId($user);
    return is_numeric($userId);
  }
  
  // Are we logged in?
  public function isLoggedIn() {
    return (!empty($_SESSION[$this->sessionVar]));
  }
  
  // Read the users.json file and return an array. This is the value
  // stored in $users.
  public function getUsers() {
    return json_decode(file_get_contents($this->usersDir.'/users.json'))->users;
  }
  
  // Get the path of the users directory.
  public function getUsersDir() {
    return $this->usersDir;
  }

  // Get the numerical user ID associated with this user false if the
  // user doesn't exist.
  public function getUserId($user) {
    $userId = array_search($user, $this->users);
    
    if (is_numeric($userId)) {
      return $userId;
    } else {
      return false;
    }
  }
  
  // Get the username associated with this numerical user ID, false if
  // the user ID doesn't exist.
  public function getUserById($userId) {
    if (is_readable($this->usersDir."/$userId/user.json") &&
	$userjson = file_get_contents($this->usersDir."/$userId/user.json")) {

      $user = json_decode($userjson);
      return $user->user;
    } else {
      return false;
    }
  } 
  
  // Get this user's info, false if the user doesn't exist.
  public function getUser($user) {
    return $this->getUserById($this->getUserId($user));
  }
  
  // Get the path of the subdirectory of this user, false if they
  // don't exist.
  public function getDirForUser($user) {
    $id = $this->getUserId($user);
    if (is_numeric($id)) {
      return $this->usersDir."/$id";
    } else {
      return false;
    }
  }
  
  // Get the subdirectory of the logged in user, false if not logged in.
  public function getDirForLoggedInUser() {
    return $this->getDirForUser($this->getLoggedInUser());
  }

  // Get the user ID of the logged in user, false if not logged in.
  public function getLoggedInUserId() {
    return $this->getUserId($_SESSION[$this->sessionVar]);
  }

  // Get the logged in user's info, false if not logged in.
  public function getLoggedInUserInfo() {
    return $this->getUser($_SESSION[$this->sessionVar]);
  }
  
  // Get the logged in username, false if not logged in.
  public function getLoggedInUser() {
    return $_SESSION[$this->sessionVar];
  }
  
  // Register a new user. Returns true on success, false if the user
  // already exists, or throws an exception if there's an error.
  public function register($user, $pass, $email) {
    
    if (!empty($user) && !empty($pass) && !empty($email)) {
      
      if (!$this->userExists($user)) {
	array_push($this->users, $user);      

	if (!$this->saveUsers()) {
	  throw new SSAuthException("Couldn't save user list");
	}
	
	// Create a directory for the user and save their info.
	try {
	  $dirForUser = $this->getDirForUser($user);
	  mkdir($dirForUser);
	  file_put_contents("$dirForUser/user.json", json_encode(
								 array("user" => array(
										       "user" => $user,
										       "pass" => crypt($pass),
										       "email" => $email
										       ))));
	  return true;
	} catch (Exception $e) {
	  // Something bad happened setting up the user.
	  throw new SSAuthException("Couldn't save user list");
	}
      } else {
	// User already exists.
	return false;
      }
    } else {
      throw new SSAuthException('Registration requires a username, password, and email address');
    }
  }
  
  // Change the password and/or email address of the logged in user.
  // Returns false if not logged in, throws exception on error.
  public function changeInfo($pass, $email) {
    
    if ($this->isLoggedIn()) {

      $userInfo = $this->getLoggedInUserInfo();
      
      if (!empty($pass)) {
	$userInfo->pass = crypt($pass);
      }
      
      if (!empty($email)) {
	$userInfo->email = $email;
      }
      
      if (!file_put_contents($this->getDirForUser($userInfo->user)."/user.json",
			     json_encode(array("user" => $userInfo)))) {
	throw new SSAuthException("Couldn't change user info");
      } else {
	return $userInfo;
      }
      
    } else {
      return false;
    }

  }

  // Log in, returns true on success, or false on failure.
  public function login($user, $pass) {
    
    if (!empty($user) && !empty($pass)) {
      
      if ($userInfo = $this->getUser($user)) {
	if (($userInfo->user == $user) &&
	    ($userInfo->pass == crypt($pass, $userInfo->pass))) {
	  
	  $_SESSION[$this->sessionVar] = $user;
	  return true;
	}
      }
    } elseif (empty($user) && empty($pass)) {
      throw new SSAuthException('Username and password required');
      return false;
    }

    return false;    
  }
  
  // Log out, always returns true.
  public function logout() {
    unset($_SESSION[$this->sessionVar]);
    return true;
  }

}

?>
