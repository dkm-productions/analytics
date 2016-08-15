<?php 
require_once "inc/uiFunctions.php";
require_once "php/data/LoginSession.php";
require_once "php/data/rec/sql/UserRegistrations.php";
require_once "php/forms/RegistrationForm.php";
require_once "inc/captchaValue.php";
require_once "php/data/csv/ip-country/IpValidator.php";
//
$badip = IpValidator::isBad();
import_request_variables("p", "p_");
$trial_text = "FREE 30 Day Trial";
$regul_text = "Regular Subscription";
$form = new RegistrationForm();
if (geta($_POST, 'fromPop') == '1') {
  $style = 'display:none';
  $focus = "state";
} else {
  $style = '';
  $focus = "name";
}
@session_start();
if (! isset($p_formSubmitted)) {
  $image_value = genImageValue();
  //LoginSession::clear();
  $_SESSION["image_text"] = $image_value;
  session_write_close();
  if (isset($p_fromPop)) {
    $form->setFromPost();
  }
} else {
	try {
    $form->setFromPost();
    $form->validate();
    $registration = $form->buildRegistration();
    UserRegistrations::create($registration);
    if (LoginSession::isProdEnv())
      $form->sendMail();
    LoginSession::login($form->id, $form->pw)->setUi($p_tablet);
    session_write_close();
    header("Location: welcome.php?qh=1");
  } catch (ValidationException $e) {
    $errors = $e->getErrors();
    if (geta($errors, 'imgval')) {
      $image_value = genImageValue();
      $_SESSION["image_text"] = $image_value;
      session_write_close();
      $form->imgval = "";
    }
  } catch (RecValidatorException $e) {
    $errors = $e->errors;
    $image_value = genImageValue();
    $_SESSION["image_text"] = $image_value;
    session_write_close();
    $form->imgval = "";
  } catch (Exception $e) {
    $errors = array('' => 'Your registration could not be handled at this time. Please call 1-888-825-4258 for assistance.');    
  }
}
$prac1 = "form.practice_name.disabled = true; form.practice_id.disabled = false;";
$prac2 = "form.practice_name.disabled = false; form.practice_id.disabled = true;" .
         "alert('Please see (what\'s this?) for information on using Practice Name vs Practice ID');";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head>
    <? renderHead("Welcome") ?>
    <link rel="stylesheet" type="text/css" href="css/page.css?" media="screen" />
    <link rel="stylesheet" type="text/css" href="css/clicktate.css?" media="screen" />
    <link rel="stylesheet" type="text/css" href="css/schedule.css?" media="screen" />
    <link rel="stylesheet" type="text/css" href="css/pop.css?" media="screen" />
    <link rel="stylesheet" type="text/css" href="css/data-tables.css?" media="screen" />
    <link rel="stylesheet" type="text/css" href="css/template-pops.css?" />
    <link rel="stylesheet" type="text/css" href="css/home.css" media="screen" />
<!--[if lte IE 6]>    
    <link rel="stylesheet" type="text/css" href="css/clicktate-font.css?" media="screen" />
    <link rel="stylesheet" type="text/css" href="css/schedule-font.css?" media="screen" />
    <link rel="stylesheet" type="text/css" href="css/pop-font.css?" media="screen" />
    <link rel="stylesheet" type="text/css" href="css/data-tables-font.css?" media="screen" />
    <link rel="stylesheet" type="text/css" href="css/template-pops-font.css?" />
<![endif]-->    
    <script language="JavaScript1.2" src="js/ui.js?"></script>
  </head>
  <body>
    <div id="bodyContainer">
      <div id="logo-head">
        <div style='background:#D2E3E0;'>
          <table border="0" cellpadding="0" cellspacing="0">
            <tr>
              <td><img src="img/lhdLogoTop2.png" /></td>
              <td class="logo-right" />
                <div class="loginfo">
                  <? if (isset($login)) { ?>
                    Logged in as <b><?=$login->uid ?></b>
                    | <a href=".?logout=Y">Logout</a>
                  <? } ?>
                </div>
              </td>
            </tr>
          </table>
        </div>
        <table border=0 cellpadding=0 cellspacing=0 width="100%">
          <tr>
            <td>
              <img src="img/lhdLogoBottomL.png" style='display:block'/>
            </td>
            <td class="loginfo2">
            </td>
          </tr>
        </table>
      </div>
      <div style="padding:0 20px 10px 20px;">
        <table class='w100'>
          <tr>
            <td style='width:250px;padding:20px 10px 0 0;vertical-align:top'>
              <ul class="list">
                <li>
                  <div class="q">Am I under any obligation when my trial is up?</div>
                  No.
                </li>
                <li>
                  <div class="q">Any browser requirements?</div>
                  Clicktate works with modern versions of Internet Explorer, Chrome and Safari (including iPad).               
                </li>
                <li>
                  <div class="q">Any hardware requirements?</div>
                  All you need is a PC or Mac connected to the Internet.
                  We have had great success operating Clicktate on Tablet PCs.  
                </li>
                <li>
                  <div class="q">Is there a user guide?</div>
                  Yes, and we recommend you download it now to help you along:<br/><br/>
                  <a id="ug" target="_blank" href="/ClicktateUserGuide.pdf">Clicktate User Guide</a> 
                </li>
                <li>
                  <div class="q">Will my popup blocker interfere?</div>
                  Yes, because Clicktate opens its document builder inside a popup browser window.
                  If you don't know how to turn off your popup blocker for www.clicktate.com, our user guide (available above) explains how. 
                </li>
              </ul>
            </td>
            <td>
              <form id="frm" name="trial" method="post" action="trial.php" onSubmit="return CheckTrial(this)">
              <input name="tablet" id="tablet" type="hidden" value="" />
              <input name='formSubmitted' type='hidden' value='1' />
              <input name='fromPop' type='hidden' value='<?=$form->fromPop ?>' />
              <input name="vista" id="vista" type="hidden" value="" />
              <input name="ie" id="ie" type="hidden" value="" />
              <table class="box" cellpadding=0 cellspacing=0 width="100%">
                <tr>
                  <td class="tl"></td>
                  <td class="t"></td>
                  <td class="tr"></td>
                </tr>
                <tr>
                  <td class="l" nowrap></td>
                  <td class="content">
                  <?php require_once "inc/errors.php" ?>
                  <?php if ($badip) { ?>
                  <div style='height:440px;vertical-align:middle;text-align:center;font-size:14pt;font-weight:bold'>
                    <br><br><br><br><br><br>Cannot register at this time.<br><br>Please contact 1-888-825-4258 to register.
                  </div>
                  <?php } else { ?>
                  <table border=0 cellpadding=0 cellspacing=0>
                    <tr style='<?=$style ?>'>
                      <td>
                        <label>Name</label><br>
                        <input id="name" name="name" type="text" size="35" maxlength="35" value="<?=$form->name ?>"/>
                      </td>
                      <td width=5></td>
                      <td class="help">
                        
                      </td>
                    </tr>
                  </table>
                  <table border=0 cellpadding=0 cellspacing=0 class='mt5' style='<?=$style ?>'>
                    <tr>
                      <td>
                        <label>Email Address</label><br>
                        <input id="email" type="text" size="45" name="email" maxlength="45" value="<?=$form->email ?>" />
                      </td>
                      <td width=5></td>
                      <td class="help"><br>
                        Must be a valid address.
                      </td>
                    </tr>
                </table>
                  <table border=0 cellpadding=0 cellspacing=0 class='mt5'>
                    <tr>
                      <td>
                        <label>State</label><br>
                        <? renderCombo("state", $form->states, $form->state) ?>
                      </td>
                      <td width=5></td>
                      <td>
                        <label>Medical License<label><br>
                        <input id="license" name="license" type="text" size="20" maxlength="20" value="<?=$form->license ?>"/>
                      </td>
                      <td width=5></td>
                      <td class="help"><br>

                      </td>
                    </tr>
                  </table>
  
                  <table border=0 cellpadding=0 cellspacing=0 class='mt5'>
                    <tr>
                      <td>
                        <label>Practice Name</label><br>
                        <input id="practice_name" name="practice_name" type="text" size="45" maxlength="45" value="<?=$form->practice_name ?>"/>
                        <input type="hidden" name="practice" value="2">
                        <input type="hidden" name="practice_id" value="">
                        <input type="hidden" id="practice_pw" name="practice_pw" size="18" maxlength="18" value="init1"/>
                      </td>
                      <td width=5></td>
                      <td class="help"><br>

                      </td>
                    </tr>
                  </table>
                  <table border=0 cellpadding=0 cellspacing=0 class='mt5'>
                     <tr>
                        <td>
                          <label>Phone</label><br>
                          <input id="phone_num" name="phone_num" type="text" size="30" maxlength="30" value="<?=$form->phone_num ?>"/>
                        </td>
                        <td width=5></td>
                          <td class="help">
                            
                          </td>
                      </tr>
                   </table>
  
                  <br><br>
                  <table border=0 cellpadding=0 cellspacing=0>
                    <tr>
                      <td>
                        <label>User ID</label><br>
                        <input id="id" type="text" size="18" maxlength="18" name="id" value="<?=$form->id ?>" />
                      </td>
                      <td width=5></td>
                      <td class="help pt5"><br>
                        Create a new user ID for yourself.
                      </td>
                    </tr>
                    <tr style='display:none'>
                      <td>
                        <label>Password</label><br>
                        <input name="pw" type="password" size="18" maxlength="18" value="<?=$form->pw ?>" />
                      </td>
                      <td width=5></td>
                      <td class="help"><br>
                        Create a password for this ID. Must be at least six characters long, containing at least one numeric digit.
                        Be careful, this is <div style="display:inline; color:black; font-weight:bold">case-sensitive</div>.
                      </td>
                    </tr>
                    <tr style='display:none'>
                      <td>
                        <input name="rpw" type="password" size="18" maxlength="18" value="" />
                      </td>
                      <td width=5></td>
                      <td class="help pt5">
                        Type in your password again.
                      </td>
                    </tr>
                  </table>
                  <table border=0 cellpadding=0 cellspacing=0 style='display:none'>
                    <tr style="padding-top:15px">
                      <td>
                        <? renderCombo("found", $form->foundMethods, $form->found) ?>
                      </td>
                      <td width=5></td>
                      <td class="help pt5">
                        We're interested!
                      </td>
                    </tr>
                  </table>
                  <table border=0 cellpadding=0 cellspacing=0 style='display:none'>
                    <tr style="padding-top:15px">
                      <td>
                        <input id="refname" name="refname" type="text" size="35" maxlength="35" value="<?=$form->refname ?>"/>
                      </td>
                      <td width=5></td>
                      <td class="help pt5">
                        If referred, please tell us the referring user.
                      </td>
                    </tr>
                  </table>
                  <?php
                  if (strcasecmp($form->imgval, $_SESSION["image_text"]) == 0) {
                    $imgstyle = 'display:none';
                  } else {
                    $imgstyle = '';
                  }
                  ?>
                  <table border=0 cellpadding=0 cellspacing=0 style='<?=$imgstyle?>'>
                    <tr>
                      <td>
                        <br><br>
                        <img src="inc/captchaGen.php?<? echo time() ?>">
                      </td>
                    </tr>
                  </table>
                  <table border=0 cellpadding=0 cellspacing=0 style='<?=$imgstyle?>'>
                    <tr>
                      <td>
                        <input id="imgval" name="imgval" type"text" size="6" value="<?=$form->imgval?>">
                      </td>
                      <td width=5></td>
                      <td class="help pt5">
                        Please type the verification code from the image above 
                        <div style="display:inline; color:black; font-weight:bold">exactly as it appears</div>. 
                      </td>
                    </tr>
                  </table>
                  <br><br>
                  <table border=0 cellpadding=0 cellspacing=0>
                    <tr>
                      <td style="vertical-align:top">
                        <input type="checkbox" name="cbAgree" id="cbAgree" value="X" />
                      </td>
                      <td width=2></td>
                      <td class='help'>
                        By checking this box, I agree and consent to LCD Solutions Inc. 
                        <a target="_blank" href="http://www.clicktate.com/terms.php">Terms of Service</a>, 
                        <a target="_blank" href="http://www.clicktate.com/Clicktate-BAA-1.0.pdf">Business Associate Agreement</a>, 
                        and <a target="_blank" href="http://www.clicktate.com/privacy.php">Privacy Policy</a>.
                        I understand that in order to meet HIPAA and HITECH compliance 
                        <b>actual patient data should not be entered into the system</b>
                        until an executed written Business Associate Agreement has been provided to LCD Solutions, Inc.
                      </td>
                    </tr>
                  </table>
                  <br>
                  <div class='cj'>
                  <a class="tour video cm" style="padding-top:10px;padding-bottom:10px" href="javascript:submit()">Create my free trial account ></a>
                  </div>
                  </td>
                  <?php } ?>
                  <td class="r" nowrap></td>
                </tr>
                <tr>
                  <td class="bl"></td>
                  <td class="b"></td>
                  <td class="br"></td>
                </tr>
              </table>
              </form>
            </td>
          </tr>
        </table>
      </div> 
    <div id='bottom'><img src='img/brb.png' /></div>
    </div>
    <? include "inc/footer.php" ?>
<script type="text/javascript">
var capterra_vkey = "22cd5473e244a97176451f74a5cd7775";
var capterra_vid = "2049522";
var capterra_prefix  = (("https:" == document.location.protocol) ? "https://ct.capterra.com" : "http://ct.capterra.com"); document.write(unescape("%3Cscript src='" + capterra_prefix + "/capterra_tracker.js?vid=" + capterra_vid + "&vkey=" + capterra_vkey + "' type='text/javascript'%3E%3C/script%3E"));
</script>
  </body>
</html>

<script>
setValue('tablet', bool(document.ontouchstart === null));
function submit() {
  frm.submit();
}

function CheckTrial(theform) {

 return true;

// Check that all fields on form are filled
for (i=0; i<theform.elements.length; i++){
  if (theform.elements[i].type=="text" || theform.elements[i].type=="textarea" ||
      theform.elements[i].type=="password" ) {
    if (theform.elements[i].name=="practice_id" ||
        theform.elements[i].name=="practice_name") {
      for (j=0;j<theform.practice.length;j++) {
        if (theform.practice[j].checked==true) { 
          if (j==0 && theform.practice_id.value=="") {
            alert("Please enter a Practice ID.");
            theform.practice_id.focus();
            return false;
          }
          if (j==1 && theform.practice_name.value=="") {
            alert("Please enter a Practice Name.");
            theform.practice_name.focus();
            return false;
          }
        }
      } 
    } else {
      if (theform.elements[i].value==""){ 
        alert("Please complete all fields.");
        theform.elements[i].focus();
        return false;
      }
    }
  }
}

// Validate length of practice password and alpha-numeric content
if (theform.practice_pw.value.length < 6) {
   alert('Practice password must be at least 6 characters long.');
   theform.practice_pw.focus();
   return false;
   } 
if (theform.practice_pw.value.match(/[a-z]/i) == null) {
   alert('Practice password must contain at least 1 alpha character.');
   theform.practice_pw.focus();
   return false;
   }
if (theform.practice_pw.value.match(/[0-9]/) == null) {
   alert('Practice password must contain at least 1 numeric character.');
   theform.practice_pw.focus();
   return false;
   }
// Validate the e-mail address
if (theform.email.value.match(/^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i) == null) {
   alert('Please enter a valid email address.');
   theform.email.focus();
   return false;
   }
// Validate length of password and alpha-numeric content
if (theform.pw.value.length < 6) {
   alert('Password must be at least 6 characters long.');
   theform.pw.focus();
   return false;
   } 
if (theform.pw.value.match(/[a-z]/i) == null) {
   alert('Password must contain at least 1 alpha character.');
   theform.pw.focus();
   return false;
   }
if (theform.pw.value.match(/[0-9]/) == null) {
   alert('Password must contain at least 1 numeric character.');
   theform.pw.focus();
   return false;
   }
// Check that passwords are the same
if (theform.pw.value != theform.rpw.value) {
   alert('Entered passwords do not match.');
   theform.pw.focus();
   return false;
   }
// Make sure user agrees to ToS & privacy policy
if (! theform.cbAgree.checked) {
   alert('You must agree to the Terms of Service and Privacy Policy.');
   return false;
   }

return true;
}

</script>

<?php 
require_once "inc/focus.php"; 
