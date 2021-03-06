<?php
require_once "php/forms/Form.php";
require_once "php/forms/utils/CommonCombos.php";

class RegistrationForm extends Form {

	// Enterable fields
	public $name;
	public $practice;
	public $practice_id;
	public $practice_name;
	public $practice_pw;
	public $state;
	public $license;
	public $email;
	public $phone_num;
//	public $ph_ac;
//    public $ph_pf;
//    public $ph_nm;
//    public $phone_ext;
    public $id;
	public $pw;
    public $imgval;
	public $cbAgree;
    public $action;
	public $found;
	public $refname;

	// Other user form fields
	public $active;
	public $date_created;
	public $udata4;
	public $udata5;
	public $user_type;
	public $trial_exp;
  
	// Combo lists
	public $states;
	public $foundMethods;

	public $fromPop;
	
	const BASIC_USAGE = 0;
	const EMR_USAGE = 1;
	
	public function __construct() {
		$this->buildCombos();
		$this->practice = "2";
		$this->found = "None";
	}
	
	// Functions
	public function validate() {
		$this->resetValidationException();
		$this->addRequired("name", "Name", $this->name);
		$this->addRequired("_email", "Email Address", $this->email);
		$this->addRequired("state", "State", $this->state);
		$this->addRequired("license", "Medical License", $this->license);
		$this->addRequired("practice_name", "Practice Name", $this->practice_name);
		if ($this->fromPop != '1')
  		if ( (! isBlank($this->email)) && (! isValidEmail($this->email)) ) {
  			$this->validationException->add("email", "Please enter a valid e-mail address.");
  		}
		$this->addRequired("id", "User ID", $this->id);
		$this->addRequired("pw", "Password", $this->pw);
		if ( (! isBlank($this->pw)) && (! isValidPassword($this->pw)) ) {
			$this->validationException->add("pw", "Password must be 6 or more in length with at least 1 alpha and 1 numeric character.");
		}
		$this->addRequired("rpw", "Re-type Password", $this->rpw);
		if ( (! isBlank($this->pw)) && (! isBlank($this->rpw)) && ($this->pw != $this->rpw) ) {
			$this->validationException->add("rpw", "Entered passwords do not match.");
		}
		$this->addRequired("found", "How did you hear about us?", $this->found);
		$this->addRequired("imgval", "Verification Code", $this->imgval);
		$imageText = isset($_SESSION['image_text']) ? $_SESSION['image_text'] : null;
		if (strcasecmp($this->imgval, $imageText) != 0) {
    			$this->validationException->add("imgval", "Incorrect verification code.");
		}
		if ($this->cbAgree != "X") {
    			$this->validationException->add("cbAgree", "You must agree to the Terms of Service and Privacy Policy.");
		}
//              Next line is for the Paola Montero types
		if ($_SERVER['REMOTE_ADDR'] == '68.194.109.22') {
    			$this->validationException->add("name", "Registration restricted.  Please call 1-888-8click8");
		}
		$this->throwValidationException();
	}

	public function buildRegistration() {
		return new Registration(null, $this->id, $this->pw, $this->name, $this->email, $this->practice_name,  
		                        $this->license, $this->state, $this->phone_num, null, $this->found, $this->date_created, $this->refname);
	}
	
	public function buildUserGroup() {
	  $tz = "0"; 
	  $tzs = CommonCombos::timezonesByState();
	  if (isset($tzs[$this->state])) {
	    $tz = $tzs[$this->state];
	  }
		return new UserGroup(null, $this->practice_name, 1, $tz);
	}
	
	public function buildUser($trial_text) {
		if ($this->action==$trial_text) {
			$this->active = 1;
		} else {
			$this->active = 0;
		}
		$this->active = 1;
		return new User(null, $this->id, $this->pw, $this->name, 0, 0,
		                $this->active, null, $this->trial_exp, null, 1, 
		                $this->date_created, $this->state, $this->license, null, null,
		                $this->email, null, null);
	}
	
	public function buildAddress($user_id) {
		return new Address0(null, "U", $user_id, 0, null, null, null,
							null, $this->state, null, null, 
							$this->phone_num, 9, null, null, null, null,
							$this->email, null, null);
	}
		
	public function setFromPost() {
		$this->name = geta($_POST, "name");
		$this->practice = geta($_POST, "practice");
		$this->practice_id = geta($_POST, "practice_id");
		$this->practice_name = geta($_POST, "practice_name");
		$this->practice_pw = geta($_POST, "practice_pw");
		$this->state = geta($_POST, "state");
		$this->license = geta($_POST, "license");
  	$this->email = geta($_POST, "email");
//		$this->phone_num = geta($_POST, "ph_ac") . "-" . geta($_POST, "ph_pf") . "-" . geta($_POST, "ph_nm");
//		$this->ph_ac = geta($_POST, "ph_ac");
//		$this->ph_pf = geta($_POST, "ph_pf");
//		$this->ph_nm = geta($_POST, "ph_nm");
//		$this->phone_ext = geta($_POST, "phone_ext");
		$this->phone_num = geta($_POST, "phone_num");
  	$this->id = geta($_POST, "id");
		//$this->pw = geta($_POST, "pw");
		//$this->rpw = geta($_POST, "rpw");
		$this->pw = 'clicktate1';
		$this->rpw = 'clicktate1';
		$this->imgval = geta($_POST, "imgval");
		$this->cbAgree = isset($_POST["cbAgree"]) ? $_POST["cbAgree"] : null;
		$this->action = isset($_POST["action"]) ? $_POST["action"] : null;
		$this->found = geta($_POST, "found");
		$this->active = 1;
		$this->date_created = now();
		$this->refname = geta($_POST, "refname");
		$this->udata4 = null;
		$this->udata5 = null;
		$this->user_type = 1;
		$this->trial_exp = date('Y-m-d H:m:s', strtotime("+14 days"));
		$this->fromPop = geta($_POST, 'fromPop');
	}

	public function sendMail() {

		// send mail
		$to = $this->email;
		$subject = "Clicktate Free Trial Confirmation";
		$message = file_get_contents("confirm-email.html");
		$message = str_replace("Trial User", $this->name, $message);
		$headers  = 'From: info@clicktatemail.info' . "\r\n";
		$headers .= 'Reply-To: info@clicktatemail.info' . "\r\n";
		$headers .= 'Return-Path: info@clicktatemail.info' . "\r\n";
		$headers .= 'Bcc: info@clicktatemail.info' . "\r\n";
		$headers .= 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";

		mail($to, $subject, $message, $headers);
	}

	private function buildCombos() {
		$this->states = CommonCombos::states();
		$c = array();
		$c["None"] = "How did you find out about us?";
		$c["GoogleAd"] = "Clicked on your Google ad";
		$c["GoogleSrch"] = "Did a Google search";
    $c["PrintAd"] = "Saw your print ad";
    $c["BannerAd"] = "Clicked on your banner ad";
		$c["Oral"] = "Recommended by friend/colleague";
		$c["SnailMail"] = "Saw your direct mailing";
		$c["Other"] = "Other";
		$this->foundMethods = $c;
	}
}
?>