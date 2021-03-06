<?php
require_once "inc/uiFunctions.php";
require_once "php/data/LoginSession.php";
require_once "php/data/Version.php";
require_once "inc/captchaValue.php";
require_once 'php/data/rec/sql/dao/Logger.php';
//
//import_request_variables("p", "p_");
$p_id = geta($_POST, 'id');
$p_pw = geta($_POST, 'pw');
$p_tablet = geta($_POST, 'tablet');
//import_request_variables("g", "g_");
$g_logout = geta($_GET, 'logout');
$g_timeout = geta($_GET, 'timeout');
$g_cp = geta($_GET, 'cp');
//
$login = null;
session_start();
if (isset($_SESSION['post'])) { // redirected from emr login
  $p_id = $_SESSION['post']['id'];
  $p_pw = $_SESSION['post']['pw'];
  $p_tablet = $_SESSION['post']['tablet'];
  $p_cap = geta($_POST, 'cap');
  unset($_SESSION['post']);
}
$captcha = false;
if (isset($g_logout))
  LoginSession::clear();
else if (isset($g_cp))
  $login = LoginSession::get();
if (! isset($p_id)) {
  $p_id = "";
  unset($_SESSION['captcha']);
} else if (isset($p_pw)) {
  $captcha = isset($_SESSION['captcha']);
  if (empty($p_id)) {
    $errors = array('User ID is required.');
  } else if (empty($p_pw)) {
    $errors = array('Password is required.');
  } else if ($captcha && (! isset($p_cap) || $p_cap != $_SESSION['captcha'])) {
    $errors = array('Text does not match.');
    $_SESSION["captcha"] = genImageValue();
    sleep(2);
  } else {
    try {
	  Logger::debug('analytics/sec/index.php: Trying login');
      $login = LoginSession::login($p_id, $p_pw);
	  Logger::debug('analytics/sec/index.php: Login success!');
      if ($login->User->needsNewBilling()) {
        $url = 'registerCard.php';
      } else {
        if ($login->cerberus)
          $url = 'cerberus-login.php';
        else
        // $url = 'welcome.php';  // crs 6/29/2016
         $url = 'patients.php';
      }
	  Logger::debug('analytics/sec/index.php: Redirect to ' . $url);
      header("Location: $url");
      exit;
    } catch (LoginEmrException $e) {
      $_SESSION['post2'] = $_POST;
      //header('Location: ../../prod-clicktate/sec');
      //header('Location: ../../sec/index.php'); 11/1/16
	  //header('Location: ');
	  header('Location: index.php');
      exit;
    } catch (LoginInvalidException $e) {
      if ($e->locked)
        header("Location: forgot-login.php?locked=1");
      else
        $errors = array("ID or password is incorrect, please re-enter.");
      if ($e->attempts > 2) {
        $captcha = true;
        $_SESSION["captcha"] = genImageValue();
      }
    } catch (LoginDisallowedException $e) {
      $errors = array("This account is currently inactive.<BR>Please call 888-825-4258 for more information.");
    } catch (AppUnavailableException $e) {
      $errors = array("The system is currently unavailable.<BR>Please try your request later.<br>Call 888-825-4258 if you continue to have problems.");
    } catch (Exception $e) {
      logit_r($e);
      $errors = array("This ID cannot be logged into at this time.<BR>Please call 888-825-4258 if you continue to have problems.");
    }
  }
}
session_write_close();
?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <!-- Copyright (c)2012 by Cerberus Healthcare, Inc.  All rights reserved. -->
  <!-- http://www.clicktate.com -->
  <head>
 <!-- ****** crs 6/29/2016    <title>
      Clicktate - Login
    </title>  -->
    <title>
      Papyrus - Login
    </title>


    <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
   <!-- ****** crs 6/29/2016  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" /> ****** crs 6/29/2016   -->
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Content-Style-Type" content="text/css" />
    <meta http-equiv="Content-Script-Type" content="text/javascript" />
    <meta http-equiv="Content-Language" content="en-us" />
    <meta name="keywords" content="dictate, dictation, medical note, document generation, note generation, medical office notes, medical transcription, emr, ehr, medical documentation, progress notes, medical progress notes, soap notes, medical soap notes, medical note generation, medical notes, medical dictation, medical transcription, family practice notes, internal medicine notes, pediatric notes, urgent care notes, urgent care documentation, internal medicine documentation, pediatric documentation, family practice documentation, small office emr, small office ehr" />
    <meta name="description" content="Automated document generation." />
    <link rel="stylesheet" type="text/css" href="css/home.css" />
    <link rel="stylesheet" type="text/css" href="../css/home.css" />
    <script type="text/javascript" src="http://code.jquery.com/jquery-1.11.0.js"></script>
    <script type="text/javascript" src="js/toggle.js"></script>
 <!-- ****** crs 6/29/2016
    <link rel="stylesheet" type="text/css" href="http://test.clicktate.com/sec/css/xb/pop.css" />
    <link rel="stylesheet" type="text/css" href="http://test.clicktate.com/sec/js/_ui/PasswordEntry.css" />
    <script type='text/javascript' src='http://test.clicktate.com/sec/js/_lcd_core.js?2'></script>
    <script type='text/javascript' src='http://test.clicktate.com/sec/js/_lcd_html.js?2'></script>
    <script language="JavaScript1.2" src="http://test.clicktate.com/sec/js/_ui/PasswordEntry.js?1"></script>
    <script language="JavaScript1.2" src="http://test.clicktate.com/sec/js/pages/Page.js"></script>
    <script language="JavaScript1.2" src="http://test.clicktate.com/sec/js/pages/Ajax.js"></script>
    <script language="JavaScript1.2" src="http://test.clicktate.com/sec/js/pages/Pop.js"></script>
    <script language="JavaScript1.2" src="http://test.clicktate.com/sec/js/old-ajax.js"></script>
    <script language="JavaScript1.2" src="http://test.clicktate.com/sec/js/yahoo-min.js"></script>
    <script language="JavaScript1.2" src="http://test.clicktate.com/sec/js/connection-min.js"></script>
    <script type='text/javascript' src='http://test.clicktate.com/sec/js/components/CmdBar.js'></script>
    <script type="text/javascript" src="../inc/alertpop.js"></script>
  ****** crs 6/29/2016   -->
    <link rel="icon" href="img/icons/favicon.ico"> <!-- ****** crs 6/29/2016 ****** -->
    <link rel="stylesheet" type="text/css" href="css/xb/pop.css" />
    <link rel="stylesheet" type="text/css" href="js/_ui/PasswordEntry.css" />
    <script type='text/javascript' src='js/_lcd_core.js?2'></script>
    <script type='text/javascript' src='js/_lcd_html.js?2'></script>
    <script language="JavaScript1.2" src="js/_ui/PasswordEntry.js?1"></script>
    <script language="JavaScript1.2" src="js/pages/Page.js"></script>
    <script language="JavaScript1.2" src="js/pages/Ajax.js"></script>
    <script language="JavaScript1.2" src="js/pages/Pop.js"></script>
    <script language="JavaScript1.2" src="js/old-ajax.js"></script>
    <script language="JavaScript1.2" src="js/yahoo-min.js"></script>
    <script language="JavaScript1.2" src="js/connection-min.js"></script>
    <script type='text/javascript' src='js/components/CmdBar.js'></script>
    <script type="text/javascript" src="js/alertpop.js"></script>
    <style>a.tour{background-color:#008C7B !important;border-radius:30px;color:#FFFFFF !important;}a.tour:hover{background-color:#FFFFFF !important;}</style>
	
		<script type='text/javascript'>
var tablet = 0;//Boolean.toInt(document.ontouchstart === null);
Html.Input.$('tablet').setValue(tablet);
<?php if ($login && isset($g_cp)) { ?>
ChangePasswordPop_Expired.pop(<?php echo $login->userId; ?>, tablet);
<?php } ?>
Cookies.expire('NC_STATUS');
function setpw() {
  hide("pop-cp-errors");
  if (validpw()) {
    var u = {pw:value("pop-cp-pw")};
    postRequest(4, "action=updateMyPw&obj=" + jsonUrl(u));
    Pop.Working.show();
  }
}
function updateMyUserCallback(errorMsg) {
  Pop.Working.close();
  if (errorMsg == null) {
    Pop.close();
    Pop.Working.show();
    window.location = "welcome.php";
  } else {
    Pop.Msg.showCritical(errorMsg, updateErrorCallback, true);
  }
}
function updateErrorCallback() {
  focus("pop-cp-pw");
}
function validpw() {
  var errs = [];
  validateRequired(errs, "pop-cp-pw", "New Password");
  validateRequired(errs, "pop-cp-pw2", "New Password (Repeat)");
  var pw = value("pop-cp-pw");
  if (errs.length == 0) {
    if (pw != value("pop-cp-pw2")) {
      errs.push(errMsg("pop-cp-pw", "New password fields do not match."));
    }
  }
  if (errs.length == 0) {
    if (pw.length < 6) {
      errs.push(errMsg("pop-cp-pw", "New password must be at least 6 characters long."));
    }
    if (pw.length < 6) {
      errs.push(errMsg("pop-cp-pw", "New password must be at least 6 characters long."));
    }
    if (pw.match(/[0-9]/) == null) {
      errs.push(errMsg("pop-cp-pw", "New password must contain at least 1 numeric character."));
    }
  }
  if (errs.length > 0) {
    showErrors("pop-cp-errors", errs);
    focus(errs[0].id);
    return false;
  }
  return true;
}
function working() {
  var a = document.getElementById('alog');
  a.className = 'tour working';
  a.innerText = "";
}
function sub() {
  working();
  setTimeout('sub2()',1);
}
function sub2() {
  document.getElementById('frm').submit();
}
function initfocus() {
<?php if (! isset($g_cp)) { ?>
  Html.InputText.$('uid').setFocus();
<?php } ?>
}
var me = null;
var td = _$('td');
var msg = _$('msg');
var h = td.getHeight();
//msg.setHeight(h - 10);
</script>


  </head>
  <body style='background-color:#000000;' onload="initfocus()">
  <div id="alertpop">
    <center><p><b><span id="alerttext"></span></b></p></center>
  </div>
  <div id="bodyContainer">
    <div id="curtain" class="cdark"></div>
    <div id="head">
      <div class="content">
        <div id="nav">
          <table cellpadding="0" cellspacing="0">
            <tr>
              <td>
              </td>
              <td style="text-align:right">
              </td>
            </tr>
          </table>
        </div>
      </div>
    </div>
    <div id="body">
      <div class="content">
        <div class="center">


        <table id="loginbox1" cellpadding='0' cellspacing='0' width='100%'>
          <tr>
            <td id='td'>
              <div style="margin-top:100px;">
              <h1 align="center">
		<span align="center" style='color:#008C7B'>Papyrus Clinical Information Network</span>
		 </h1>
                <div class="login" style=''>
                  <?php require_once "inc/errors.php" ?>
                <?php if (isset($g_timeout)) { ?>
                <div style='padding-bottom:1em;font-family:Arial;font-weight:bold;color:red'>Your session has expired from inactivity.<br>Please login to continue.</div>
                <?php } ?>
                </div>
                <table cellpadding='0' cellspacing='0'>
                  <tr>
                    <td class='wmp' style='padding-right:0;'>
                      <table  class="box " cellpadding="0" cellspacing="0">
		        <td  class="content"  valign="top">
		          <div class="box-content">
                          <div id="login">
                          <form id="frm" method="post" action="index.php" autocomplete="off">

                            <input name="tablet" id="tablet" type="hidden" value="" />
                            <input style="display:none" />
                            <input type="password" style="display:none" /> <!-- to elim cached autocompletes -->

                            <div class="l">
                              <label>User ID</label><br/>
                              <input type='text' id='uid' size='20' name='id' value="" autocomplete="off" />
                            </div>

                            <div class="l">
                              <label>Password</label><br/>
                              <input name="pw" id='pw' type="password" size="20" autocomplete="off" onkeydown="if ((event.which && event.which == 13) || (event.keyCode && event.keyCode == 13)) {sub();return false;} else return true;" />
                            </div>
                            <?php if ($captcha) { ?>
                            <div class="l" style="margin-top:10px;padding-top:10px;border:1px solid #c0c0c0; background-color:white;text-align:center">
                              <img src="inc/captchaGen.php?sid=captcha&<?php echo time() ?>"><br/><br/>
                              <label>Enter the text above</label><br/>
                              <input name="cap" id='cap' type="text" size="20" onkeydown="if ((event.which && event.which == 13) || (event.keyCode && event.keyCode == 13)) {sub();return false;} else return true;" />
                            </div>
                            <?php } ?>

                            <div id="trial" style="padding-bottom:10px">
                              <a id="alog" href="javascript:sub()" class="tour">Login ></a>
                            </div>
                          </form>
                        </div>
                            </div>
			    </td>
			</table>
                    </td>
                  </tr>
                </table>
                <div id="forgot">
                  <div>
                    <a href="forgot-login.php" class="gb">Forgot your login ID or password</a>?
                  </div>
                </div>
              </div>
            </td>
          </tr>
        </table>






        <table id="loginbox2" cellpadding='0' cellspacing='0' width='100%' style="display:none;">
          <tr>
            <td id='td'>
              <div style="margin-top:100px;">
              <h1 align="center">
		<span align="center" style='color:#008C7B'>Clicktate 5.0</span>
		 </h1>
                <div class="login" style='padding-left:100px'>
                                                    </div>
                <table cellpadding='0' cellspacing='0'>
                  <td>
                    <td class='wm' style='padding-right:0;'>
                      <table  class="box " cellpadding="0" cellspacing="0">
		        <td  class="content"  valign="top">
		          <div class="box-content">
                          <div id="login">
                          <form id="frm" method="post" action="forgot-login.php">
	                    <div class="l" style="margin-top:10px">
	                      <table border="0" cellpadding="0" cellspacing="0">
	                        <tbody>
                  		<tr>
                  		Enter your e-mail to retrieve your UserID
                  		</td>
                  		<tr>
	                          <td>
	                            <label>Email</label><br>
	                            <input id="email" type="text" size="35" name="email"></td>

	                        </tr>
	                      </tbody></table>
	                    </div>
	                    <div id="trial" style="padding-bottom:10px">
	                      <a id="asub" href="." class="tour">Submit &gt;</a>
	                    </div>
	                  </form>
                        </div>
                            </div>
			    </td>
			</table>
                    </td>
                  </tr>
                </table>
                <div id="forgot">
                  <div>
                    <a href="#" id="toggle2" class="gb">Back to Login</a>
                  </div>
                </div>
              </div>
            </td>
          </tr>
        </table>


        <table id="loginbox3" cellpadding='0' cellspacing='0' width='100%' style="display:none;">
          <tr>
            <td id='td'>
              <div style="margin-top:100px;">
              <h1 align="center">
		<span align="center" style='color:#008C7B'>Clicktate 5.0</span>
		 </h1>
                <div class="login" style='padding-left:100px'>
                                                    </div>
                <table cellpadding='0' cellspacing='0'>
                  <td>
                    <td class='wm' style='padding-right:0;'>
                      <table  class="box " cellpadding="0" cellspacing="0">
		        <td  class="content"  valign="top">
		          <div class="box-content">
                          <div id="login">
                          <form id="frm" method="post" action="forgot-pw.php">
                    <div class="l" style="margin-top:10px">
                      <table border="0" cellpadding="0" cellspacing="0">
                        <tbody><tr>
                          <td>
                            <label>User ID</label><br>
                            <input id="id" type="text" size="15" name="id" value=""></td>

                          <td width="10"></td>
                          <td>
                            <label>State</label><br>
                            <select id="state" name="state"><option value="" selected=""></option><option value="AK">AK</option><option value="AL">AL</option><option value="AR">AR</option><option value="AZ">AZ</option><option value="CA">CA</option><option value="CO">CO</option><option value="CT">CT</option><option value="DC">DC</option><option value="DE">DE</option><option value="FL">FL</option><option value="GA">GA</option><option value="HI">HI</option><option value="IA">IA</option><option value="ID">ID</option><option value="IL">IL</option><option value="IN">IN</option><option value="KS">KS</option><option value="KY">KY</option><option value="LA">LA</option><option value="MA">MA</option><option value="MD">MD</option><option value="ME">ME</option><option value="MI">MI</option><option value="MN">MN</option><option value="MO">MO</option><option value="MS">MS</option><option value="MT">MT</option><option value="NC">NC</option><option value="ND">ND</option><option value="NE">NE</option><option value="NH">NH</option><option value="NJ">NJ</option><option value="NM">NM</option><option value="NY">NY</option><option value="NV">NV</option><option value="OH">OH</option><option value="OK">OK</option><option value="OR">OR</option><option value="PA">PA</option><option value="RI">RI</option><option value="SC">SC</option><option value="SD">SD</option><option value="TN">TN</option><option value="TX">TX</option><option value="UT">UT</option><option value="VA">VA</option><option value="VT">VT</option><option value="WA">WA</option><option value="WI">WI</option><option value="WV">WV</option><option value="WY">WY</option></select>                          </td>
                        </tr>
                      </tbody>
                    </table>
                    </div>
                    <div id="trial" style="padding-bottom:10px">
                      <a href="javascript:submit()" class="tour">Submit &gt;</a>
                    </div>
                  </form>
                        </div>
                            </div>
			    </td>
			</table>
                    </td>
                  </tr>
                </table>
                <div id="forgot">
                  <div>
                    <a href="#" id="toggle4" class="gb">Back to Login</a>
                  </div>
                </div>
              </div>
            </td>
          </tr>
        </table>





        </div>
      </div>
    </div>
    <div id="foot">
      <div class="content">
          <div>
              <center>

                              <div class="foot-text" style="padding:0px;">
                                  v1.0 &copy;2016 Cerberus Healthcare, Inc.<br />
                                  All rights reserved.
                              </div>
              </center>
          </div>
          <center>
              <table>
                  <tbody>
                      <tr>
                          <td><a href="#openPrivacy">Privacy Policy</a></td>
                          <td><a href="#openTerms">Terms of Service</a></td>
                          <td><a style="background:url(http://clicktate.com/img/pdf.gif) no-repeat; padding-left:20px" target="_blank" href="../BAA.pdf">Business Associate Agreement</a></td>
                          <td><a href="#openContact">Contact Us</a></td>
                      </tr>
                  </tbody>
              </table>
          </center>
      </div>
  </div>
  </div>
  <div id="openContact" class="modalDialog">
                  <div>
                      <a href="#close" title="Close" class="close">X</a>
                      <div style="align-content:center">
                          <h1 style=" text-align:center;">Contact Us</h1>
                      </div>
                      <div>
                          <table style="width:100%;">
                              <tbody>
                                  <tr>
                                      <td style="width:33%; vertical-align:text-top;">
                                          <h2>Cerberus Healthcare, Inc.</h2>
                                          <p>
                                              Clicktate<br />
                                              4350 Brownsboro Road<br />
                                              Suite 110<br />
                                              Louisville, KY 40207<br />
                                          </p>

                                      </td>
                                      <td style="width:33%; vertical-align:text-top;">
                                          <h2 style="margin-top:2em">Email</h2>
                                          <p><a href="mailto:info@clicktatemail.info">info@clicktatemail.info</a></p>

                                      </td>
                                      <td style="width:33%; vertical-align:text-top;">
                                          <h2 style="margin-top:2em">Support Line</h2>
                                          <p><b>1-888-8CLICK8</b> (1-888-825-4258)</p>
                                      </td>
                                  </tr>
                              </tbody>
                          </table>




                      </div>

                  </div>
              </div>
              <!--ToS-->
              <div id="openTerms" class="modalDialog">
                  <div style="height:800px">
                      <a href="#close" title="Close" class="close">X</a>
                      <center><h1>Terms of Service</h1></center>
                      <div style="overflow:auto; height:700px; margin:10px;">
                          <p>Clicktate.com is a medical documentation, management and medical record tool only. It is designed to assist the user with the creation of medical office notes, the creation and maintenance of patient records, the facilitation of processes that a medical provider would use to administer patient care on a day to day basis, and certain administrative functions that are part of the normal running of a medical practice. It is not meant to replace the knowledge, experience, sound clinical judgment, and expertise of a medical provider. It is not a diagnostic tool, nor is it meant to suggest a diagnosis based upon default settings which may appear in notes. It is not a tool to assist in or suggest a course of therapy.  </p>
                          <p>Nothing contained in Clicktate.com is intended to be instructional or for medical diagnosis and/or treatment, nor is the information in Clicktate.com intended to provide advice on coding or billing. The information in Clicktate.com should not be considered complete.  The information in Clicktate.com should not be consulted with or relied upon to suggest a course of treatment for a particular individual or a particular disease condition. It should not be used in place of a medical visit, call, consultation or the advice of a physician or other qualified health care provider. Information obtained in Clicktate.com is not exhaustive and does not cover all diseases and physical conditions or their treatment.</p>
                          <center><b>Patient Demographics and Information</b></center>
                          <p>It is the responsibility of each user of Clicktate.com to ensure the accuracy of patient demographic information including but not limited to name, address, phone number, contact information, preferred contact information, advanced directive information, insurance information and chart restrictions.  Clicktate.com has no way to track or realize changes in the contact information for each patient.  As such, Cerberus Healthcare, Inc. and Clicktate.com make no warranty as to the reliability, accuracy, timeliness, usefulness, adequacy, suitability or completeness of patient demographics and other patient information for any purpose.  It is strongly recommended that each office and provider utilizing Clicktate.com establish practices to ensure the accuracy and up to date nature of patient demographic information for each patient during each contact with that patient.</p>
                          <p>Cerberus Healthcare, Inc. and Clicktate.com also have no control over the information that is sent to patients or the method with which that information is sent to patients and therefore Cerberus Healthcare, Inc. and Clicktate.com take no responsibility for the information that is provided to patients by providers and the method with which that information is shared.  It is recommended that each office and provider utilizing Clicktate.com establish practices to ensure the accuracy and up to date nature of patient preferred contact methods as well as patient demographic data and other patient information and to ensure that this data and the methods of using this data are appropriately utilized.</p>
                          <center><b>Diagnoses</b></center>
                          <p>It is the responsibility of each user of Clicktate.com to ensure the accuracy of patient diagnostic information found in Clicktate.com.  Clicktate.com is designed to assist users by automatically populating the patient facesheet with diagnoses placed by providers into signed and locked notes, but Cerberus Healthcare, Inc. and Clicktate.com make no warranty as to the reliability, accuracy, timeliness, usefulness, adequacy, suitability or completeness for any purpose.  Cerberus Healthcare, Inc. and Clicktate.com have no way of diagnosing patient conditions, determining or knowing changes in the diagnoses of patients, or tracking the status of patient diagnoses.  All diagnoses are populated to the patient facesheet with a status of â€œActiveâ€?.  It is the responsibility of each provider to develop methods and workflows to determine the accuracy of and ensure that diagnoses and diagnoses statuses are up to date.  </p>
                          <p>Providers should be aware that patients who utilize the patient portal feature of Clicktate.com have access to their diagnosis list, and providers should ensure that patients are aware of the information contained in the diagnosis list. </p>
                          <p>Clicktate.com templates will at times automatically insert a diagnosis into a note and onto a facesheet at the time of the signing and locking of a patient note.  The diagnoses inserted into notes by default are not to be construed as a suggestion for a diagnosis for a patient, nor are the default plans meant to suggest a course of treatment.  Each provider MUST use clinical judgment as they evaluate and manage patients, including diagnosing and treating illnesses.  Clicktate.com DOES NOT replace the knowledge, training and experience of a healthcare professional and MUST NOT be used to replace this knowledge in the diagnosis and treatment of patients.</p>
                          <center><b>Medical Documentation</b></center>
                          <p>The medical documentation section (the â€œConsoleâ€?) of Clicktate.com is designed to allow providers to quickly and accurately document patient encounters.  Predesigned templates may contain items that are not relevant to a particular patient and/or items which were not performed in the course of an office visit.  Cerberus Healthcare, Inc. and Clicktate.com make no warranty as to the reliability, accuracy, timeliness, usefulness, adequacy, suitability or completeness for any purpose.  Cerberus Healthcare, Inc. and Clicktate.com have no way to determine what history items, physical exam items and other historical issues were reviewed with a particular patient during a particular visit.  As such, it is important that providers be aware of the contents of the notes found in the Clicktate.com console and utilize Clicktate.com features to delete any historical items, physical exam items, laboratory data, radiologic data, diagnoses, procedures, plans or time elements from pre-populated templates which were not performed in the course of the patient encounter.  Failure to do so may result in inaccuracies of patient medical data as well as billing and coding inaccuracies.</p>
                          <center><b>Medication and Allergy List</b></center>
                          <p>It is the responsibility of each user of Clicktate.com to ensure the accuracy of patient medication and allergy information found in the patient electronic record. Clicktate.com is designed to assist users by automatically inserting documented medications and allergies onto the patient Facesheet, but Cerberus Healthcare, Inc. and Clicktate.com make no warranty as to the reliability, accuracy, timeliness, usefulness, adequacy, suitability or completeness for any purpose.  Cerberus Healthcare, Inc. and Clicktate.com have no way of determining or knowing changes in the medications and allergies of patients.  As such, it is the responsibility of each provider to develop methods and workflows to determine the accuracy of and ensure that medications and allergy information are up to date.  Providers are also encouraged to develop internal workflows to review Drug-Drug, Drug-Allergy and Drug-Disease interaction at the point of service.  </p>
                          <p>Providers should also be aware that patients who utilize the patient portal feature have access to their medication and allergy lists and providers should ensure that patients are aware of the information contained in the medication and allergy lists. In addition, a separate â€œterms of serviceâ€? agreement is provided when providers initially log-in to the electronic prescribing system.  These separate electronic prescribing terms of service are also binding to this agreement.  It is the responsibility of each provider to know the indications for each medication and prescribe medications appropriately.  It is the responsibility of each provider to be knowledgeable regarding the uses, interactions and possible side effects for each medication prescribed.  Cerberus Healthcare, Inc. and Clicktate.com take no responsibility for any medication prescribed, side effects, interactions or other adverse events associated with the prescribing of medications from within the system.  In addition, no item found within the Clicktate.com system is meant to imply the appropriateness of a medication for a particular purpose, nor is any item found in Clicktate.com meant to suggest a particular medication for any particular purpose.  Cerberus Healthcare, Inc. and Clicktate.com assume no responsibility for any medication prescribed or any adverse event associated with a prescribed medication.</p>
                          <p>Electronic refill requests generated by pharmacies and sent electronically to the prescriber do not always exactly match the patient demographics found in the Clicktate.com patient record.  Clicktate.com will make an attempt to match the pharmacy refill request to what is considered to be the most appropriate match in the appropriate provider or facility Clicktate.com patient database.  It is the responsibility of anyone refilling a prescription to ensure the accuracy of the patient matched to the refill request, and to select a different patient if the patient found is not the correct patient.  Cerberus Healthcare, Inc. and Clicktate.com can take no responsibility for the accuracy of patients matched to pharmacy requests or the medications requested by the pharmacy.  It is recommended that each office and provider utilizing Clicktate.com establish practices and procedures to ensure the accuracy, up to date nature of information contained in pharmacy requests and the accuracy of patient mapping to the provider or facility Clicktate.com patient database.</p>
                          <center><b>Immunizations</b></center>
                          <p>It is the responsibility of each user of Clicktate.com to ensure the accuracy of patient immunization information found in the patient electronic record.  Cerberus Healthcare, Inc. and Clicktate.com make no warranty as to the reliability, accuracy, timeliness, usefulness, adequacy, suitability or completeness for any purpose.  Cerberus Healthcare, Inc. and Clicktate.com have no way of determining or knowing changes in the immunization status of patients.  As such, it is the responsibility of each provider to develop methods and workflows to determine the accuracy of and ensure that immunization information is up to date.</p>
                          <p>Providers should also be aware that patients who utilize the patient portal feature have access to their immunization record and providers should ensure that patients are aware of the information contained in the immunization record.  It is the responsibility of each provider to know the indications for each immunization and prescribe immunizations appropriately.  It is the responsibility of each provider to be knowledgeable regarding the uses, interactions and possible side effects for each immunization prescribed.  Cerberus Healthcare, Inc. and Clicktate.com take no responsibility for any immunization administered, side effects, interactions or other adverse events associated with the administration of immunizations.  In addition, it is the responsibility of each user of Clicktate.com to ensure that patients are provided current and up to date Vaccination Information Statements (VIS) for each immunization provided.  The immunization module in Clicktate.com links to other sites for vaccination information (non-patient related) including immunization schedules and Vaccine Information Statements.  Cerberus Healthcare, Inc. and Clicktate.com take no responsibility for the accuracy of this information, the content of this information or the completeness of the information gathered or presented from other sources.  </p>
                          <center><b>Clinical Decision Support</b></center>
                          <p>The information contained in the Clinical Decision Support area of Clicktate.com is intended to assist the practitioner in the care of patients.  It is not intended to be a complete source of information regarding suggested tests or procedures for any individual patient.  Because clinical guidelines change from time to time and because clinical guidelines vary from source to source, the recommendations in the Clinical Decision Support area of Clicktate.com are intended to be general guidelines and are not intended to be conclusive or definitive treatment or monitoring guidelines for any patient, disease, prevention or process.  It is the responsibility of each user of Clicktate.com to be aware of the appropriate indications for each test or procedure in the context of the conditions, diseases, medications and demographics of each individual patient.  It is the responsibility of each user of Clicktate.com to order tests and procedures appropriately and to be aware of the appropriate use of each test or procedure.  </p>
                          <p>The items in the Clinical Decision Support section may be deleted, customized and altered as individual providers deem appropriate for each patient, and it is the responsibility of each provider to be aware of the use of these features and the methods to update and change, as appropriate, the Clinical Decision Support suggestions for each patient.  Cerberus Healthcare, Inc. and Clicktate.com make no warranty as to the reliability, accuracy, timeliness, usefulness, adequacy, suitability or completeness for any purpose.  As such, it is the responsibility of each provider to develop methods and workflows to determine the accuracy of and ensure that Clinical Decision Support information is up to date.  Cerberus Healthcare, Inc. and Clicktate.com take no responsibility for any Clinical Decision Support rule made or utilized in the Clinical Decision support section of Clicktate.com.</p>
                          <p>The Clicktate.com Clinical Decision Support module also offers a system to suggest past due tests and health maintenance procedures.  This system is dependent upon the accuracy and timeliness of information placed into the Clicktate.com system.  Cerberus Healthcare, Inc. and Clicktate.com have no way of ensuring the accuracy of information placed into the system.  In addition, Cerberus Healthcare, Inc. and Clicktate.com have no way to monitor the individual patient characteristics that may or may not make a given test appropriate for each patient.  The â€œdue listâ€? found in the clinical decision support module of Clicktate.com should in no way be considered an all encompassing list of every test due for a practice or patient.  As such, each provider and office should develop workflows to assure and monitor the accuracy of this information. </p>
                          <center><b>Order Entry and Tracking</b></center>
                          <p>It is the responsibility of each user of Clicktate.com to ensure the accuracy of patient orders found in Clicktate.com.  Clicktate.com is designed to assist users by automatically generating orders and saving these orders onto the patient Facesheet and into the Order Entry and Tracking system, but Cerberus Healthcare, Inc. and Clicktate.com make no warranty as to the reliability, accuracy, timeliness, usefulness, adequacy, suitability or completeness for any purpose.  Cerberus Healthcare, Inc. and Clicktate.com have no way of determining or knowing changes in the order status of patients other than automatically closing orders that have been received and entered into the system.  Cerberus Healthcare, Inc. and Clicktate.com make no warranty as to the reliability, accuracy, timeliness, usefulness, adequacy, suitability or completeness for any purpose.  As such, it is the responsibility of each provider to develop methods and workflows to determine the accuracy of and ensure that orders and order tracking items are up to date.  </p>
                          <p>In addition, Clicktate.com templates will at times automatically insert an order into a note and onto a facesheet at the time that the order entry is triggered.  The orders, treatments and tests inserted into notes by default are not to be construed as a suggestion for a test or treatment, nor are the default plans meant to suggest a course of treatment.  Each provider MUST use clinical judgment as they evaluate and manage patients, including diagnosing and treating illnesses.  Clicktate.com DOES NOT replace the knowledge, training and experience of a healthcare professional and MUST NOT be used to replace this knowledge in the treatment of patients which includes, but is not limited to, the ordering of tests.  Cerberus Healthcare, Inc. and Clicktate.com assume no responsibility or liability for treatment plans and orders placed into Clicktate.com either automatically or via a user input.</p>
                          <center><b>Patient Portal</b></center>
                          <p>Clicktate Online Access is a medical information and information exchange tool only. It is designed to assist patients by allowing them to review their medical information, to receive communications from their healthcare provider and to allow patients to communicate information to and request information from their healthcare provider. It is not meant to replace the knowledge, experience, sound clinical judgment, and expertise of a medical provider. It is not a diagnostic tool, nor is it meant to suggest a diagnosis or treatment for any disease, malady or condition.  It is not a tool to assist in or suggest a course of therapy.  The Patient Portal cannot and should not be used in lieu of an office visit and physical examination by a provider.  Cerberus Healthcare, Inc. and Clicktate.com take no responsibility for information obtained from the patient portal including the completeness, accuracy or suitability for any purpose.  </p>
                          <p>Nothing contained in Clicktate Online Access is intended to be instructional for medical diagnosis or treatment. The information in Clicktate Online Access should not be considered complete, nor should it be relied upon to suggest a course of treatment for a particular individual. It should not be used in place of a visit, call, consultation or the advice of a physician or other qualified health care provider. Information obtained in Clicktate Online Access is not exhaustive and does not cover all diseases and physical conditions or their treatment. </p>
                          <p>Information contained in Clicktate Online Access is â€œAS ENTEREDâ€? by the physician, provider and medical office staff of a patient.  The information is not obtained by Clicktate Online Access or Clicktate.com from any other source and as such Cerberus Healthcare, Inc., Clicktate.com and Clicktate Online Access are not responsible for the accurateness or completeness of the information.  The information contained in Clicktate Online Access is NOT considered a part of a patientâ€™s medical record and information contained therein should not be used to decide on a course of treatment without first assuring its accuracy.  Patient access to Clicktate Online Access may however be retained by the physician office as a part of the medical record and for audit purposes.  Messages sent by patients to providers and by providers to patients WILL be saved in Clicktate.com as a part of the patientâ€™s medical record.</p>
                          <center><b>Laboratory Module</b></center>
                          <p>Laboratory results generated by outside laboratories and sent electronically to the patient medical record do not always exactly match the patient demographics found in the Clicktate.com patient record.  Clicktate.com will make an attempt to match the information found in the laboratory system to what is considered to be the most appropriate match in the appropriate provider or facility Clicktate.com patient database.  It is the responsibility of anyone reviewing laboratory data to ensure the accuracy of the patient matched to the laboratory report, and to select a different patient if the patient found is not the correct patient.  Cerberus Healthcare, Inc. and Clicktate.com can take no responsibility for the accuracy of patients matched to laboratory reports or the laboratory data.  It is recommended that each office and provider utilizing Clicktate.com establish practices and procedures to ensure the accuracy, up to date nature of information contained in laboratory data and the accuracy of patient mapping to the provider or facility Clicktate.com patient database. Cerberus Healthcare, Inc. and Clicktate.com have no way of knowing the accuracy of laboratory data and no way of monitoring laboratories.  Cerberus Healthcare, Inc. and Clicktate.com make no warranty as to the reliability, accuracy, timeliness, usefulness, adequacy, suitability or completeness of laboratory data for any purpose.</p>
			<center><b>Business Associate Agreement</b></center>
			<p>All users of Clicktate.com (â€œCovered Entityâ€?) agree to and acknowledge that they have been informed that in order to satisfy government regulations and remain HIPAA compliant, a Business Associate Agreement must be instituted with each â€œBusiness Associateâ€? (in this case Cerberus Healthcare/ Clicktate.com).   A Business Associate Agreement is found as a link on the Clicktate.com website.  </p>
			<p>Clicktate.com also agrees to abide by all terms of the Business Associate Agreement when received by Cerberus Healthcare, Inc. as an electronically signed document.</p>
			<p>By agreeing on-line to this â€œterms of serviceâ€? agreement, users also agree to the terms of the Business Associate Agreement, and acknowledge that Cerberus Healthcare, Inc. and Clicktate.com have agreed to the terms found in the Business Associate Agreement. </p>
			<center><b>General Information and Indemnity</b></center>
			<p>The information from or to Clicktate.com and associated sites is provided "AS IS" and "AS AVAILABLE" and all warranties, expressed or implied, are disclaimed (including but not limited to the disclaimer of any implied warranties or merchantability and fitness for a particular purpose). The information may contain errors, problems, or other limitations. Cerberus Healthcare, Inc. and Clicktate.com make no warranty as to the reliability, accuracy, timeliness, usefulness, adequacy, suitability or completeness for any purpose. Cerberus Healthcare, Inc. and Clicktate.com cannot and do not warrant against human and machine errors, omissions, delays, interruptions or losses, including loss of data, loss of patient data, and loss of scheduling data. Users of Clicktate.com acknowledge and agree that Clicktate.com does not run or control the internet.  As such, Clicktate.com cannot control all variables that might arise from use of the internet.  Clicktate.com also has no control over internet usage habits of users of Clicktate.com.  Clicktate.com links and searches for the term â€œClicktateâ€? may inadvertently and unintentionally lead to sites containing information that some people may find inappropriate or offensive.  Clicktate.com links and searches for the term â€œClicktateâ€? may also inadvertently and unintentionally lead to sites which contain inaccurate information, false or misleading advertising, or information which violates copyright, libel or defamation laws. Clicktate.com and affiliated sites cannot and do not guarantee or warrant that files available for downloading from this online site will be free of infection by viruses, worms, Trojan horses, malware or other code that manifest contaminating or destructive properties. All responsibility and liability for any damages caused by viruses contained within the electronic files of this site are disclaimed.  Clicktate.com and Information Providers do not warrant or guarantee that the functions or services performed in Clicktate.com will be uninterrupted or error-free or that defects in Clicktate.com will be corrected. Users of Clicktate.com are responsible for implementing and maintaining adequate procedures and checkpoints to satisfy their particular requirements for accuracy of data input and output. </p>
			<p>Users of Clicktate.com agree to: (a) maintain all equipment required for access to and use of Clicktate.com ; (b) maintain the security of user identification, password and other confidential information relating to userâ€™s Clicktate.com account; and (c) be responsible for all charges resulting from use of userâ€™s Clicktate.com account, including unauthorized use prior to notifying Cerberus Healthcare and Clicktate.com of such use and taking steps to prevent its further occurrence by appropriate password changes.   </p>
			<p>Users of Clicktate.com agree to indemnify, defend and hold harmless Cerberus Healthcare, Inc., Clicktate.com, its officers, directors, employees, agents, and information providers from and against all losses, expenses, damages and costs, including reasonable attorneyâ€™s fees, resulting from any violation of this agreement or any activity related to your account by you or any other person accessing Clicktate.com on your behalf or using your service account.  You further agree that neither Cerberus Healthcare, Inc., Clicktate.com, its officers, directors, employees, agents nor information providers shall have any liability to you under any theory of liability or indemnity in connection with your use of Clicktate.com.  You hereby release and forever waive any and all claims you may have against Cerberus Healthcare, Inc., Clicktate.com, its officers, directors, employees, agents, and information providers, including but not limited to claims based upon negligence of Cerberus Healthcare, Inc., Clicktate.com, its officers, directors, employees, agents, and information providers for losses or damages you sustain in connection with your use of Clicktate.com.</p>
			<p>Notwithstanding the foregoing paragraph, the sole and entire maximum liability of Cerberus Healthcare, Inc., Clicktate.com, its officers, directors, employees, agents, and information providers, if any, for any inaccurate information and losses or damages for any reason, and users sole and exclusive remedy for any cause whatsoever, shall be limited to the amount paid by the customer for the information received (if any).</p>
			<p> By electronically agreeing to this â€œTerms of Serviceâ€? you agree that:  In no event shall Cerberus Healthcare, Inc., Clicktate.com, its officers, directors, employees, agents, and information providers be liable to you or your agents for any losses or damages other than the amount referenced above.  Cerberus Healthcare, Inc., Clicktate.com, its officers, directors, employees, agents, and information providers are not liable for any indirect, special, incidental, or consequential damages (including damages for loss of business, loss of profits, litigation, or the like), whether based on breach of contract, breach of warranty, tort (including negligence), product liability, or otherwise, even if Cerberus Healthcare, Inc., Clicktate.com, its officers, directors, employees, agents, and information providers advised of the possibility of such damage. The limitations of damages set forth above are fundamental elements of the basis of the bargain between Cerberus Healthcare, Inc. and Clicktate.com and you. We would not provide this site and information without such limitations. No representations, warranties or guarantees whatsoever are made as to the accuracy, adequacy, reliability, currentness, completeness, suitability or applicability of the information to a particular situation.</p>
			<p>Clicktate.com is a medical documentation and management tool only. It is designed to assist the user with the creation of medical office notes, the creation and maintenance of patient records, the facilitation of processes that a medical provider would use to administer patient care on a day to day basis, and certain administrative functions that are part of the normal running of a medical practice. It is not meant to replace the knowledge, experience, sound clinical judgment, and expertise of a medical provider. It is not a diagnostic tool, nor is it meant to suggest a diagnosis based upon default settings which may appear in notes. It is not a tool to suggest a diagnostic pathway.  It is not a tool to assist in or suggest a course of therapy.  </p>
			<p>Prescriptions written utilizing the Clicktate.com prescription writing module are at the sole discretion of the provider, and it is the responsibility of the prescribing provider to be aware of the use and indications for the medications prescribed, the possible drug interactions of each prescribed medication, and the particular patientsâ€™ profiles for whom medications are prescribed, included but not limited to the possibility of pre-existing medication allergies and prior adverse medication  reactions.  Cerberus Healthcare, Inc., Clicktate.com, its officers, directors, employees, agents, and information providers disclaim all liability for any medication errors or interactions that may occur while using Clicktate.com or other affiliated prescription writing sites.  By electronically agreeing to this â€œTerms of Serviceâ€? you agree that:  In no event shall Cerberus Healthcare, Inc., Clicktate.com, its officers, directors, employees, agents, and information providers be liable to you or your agents for any losses or damages other than the amount referenced above.  Cerberus Healthcare, Inc., Clicktate.com, its officers, directors, employees, agents, and information providers are not liable for any indirect, special, incidental, or consequential damages (including damages for loss of business, loss of profits, litigation, or the like), whether based on breach of contract, breach of warranty, tort (including negligence), product liability, or otherwise, even if Cerberus Healthcare, Inc., Clicktate.com, its officers, directors, employees, agents, and information providers advised of the possibility of such damage.</p>
			<p>By electronically agreeing to this â€œTerms of Serviceâ€? you agree that:  Cerberus Healthcare, Inc., Clicktate.com, its officers, directors, employees, agents, and information providers are not liable for any injury, damage, omission, or loss which occurs directly or indirectly from the use of this system. In addition, Cerberus Healthcare, Inc. and Clicktate.com are not responsible for any errors or omissions which may be perceived to be a part of the system. </p>
			<p>As with any printed material or computer program or system, sound clinical judgment by a licensed and competent provider of medical care should be the ultimate guide to patient care, diagnosis, and therapy. This product is not designed to replace this care. </p>
			<center><b>Removal of Information for Clicktate.com</b></center>
			<p>Information placed into Clicktate.com in error, may be removed from the system or moved to an appropriate area of the system upon written request of the provider.  An audit log will be generated and maintained to indicate that the information was altered.</p>
			<p>Cerberus Healthcare, Inc. and Clicktate.com does not and cannot review all communications and materials posted to or uploaded to Clicktate.com.  As such, Cerberus Healthcare, Inc., Clicktate.com, its officers, directors, employees, agents, and information providers are not responsible for the content of these communications and materials. However, Cerberus Healthcare, Inc. and Clicktate.com reserve the right to block or remove communications or materials that it determines, in its sole discretion, to be (a) abusive, libelous, defamatory or obscene, (b) fraudulent, deceptive, or misleading, (c) in violation of a copyright or trademark, other intellectual property right of another or (d) offensive or otherwise unacceptable to Cerberus Healthcare and/or Clicktate.com.</p>
			<center><b>Treatment and Retention of Data after Termination or Discontinuation of Service
</b></center>
			<p>Information entered into Clicktate.com by TRIAL users who have NOT converted to an active account (by returning a Business Associate Agreement and entering valid billing information) will be retained in the system for a period of 14 days after the completion of the trial.  After this time, the account will be purged and permanently removed from the system.  Clicktate.com is only considered the Electronic Health Record for a practice AFTER a Business Associate Agreement is signed and returned to Cerberus Healthcare, Inc. AND after the provider account has been activated to a full account.  Prior to this date, the use of Clicktate.com is considered to be a trial and should not be considered the official medical record for a practice, provider or patient.</p>
			<p>Information entered into Clicktate.com by active subscribers will be retained for a period of at least 25 years (28 years from the date of birth for minors) UNLESS the data is transferred in its entirety into another system or some other form and provided to the provider.  During this time, at the userâ€™s request, a read-only version of the data will be made available.  Cerberus Healthcare, Inc. and Clicktate.com reserve the right to remove the data from our servers after providing the data on appropriate electronic media to the user.</p>
			<center><b>Meaningful Use</b></center>
			<p>Clicktate.com is an ONC certified electronic medical record and meets the requirements for a Complete Ambulatory EMR (2014) for Stage 2 of Meaningful Use.  It is the responsibility of the users to ensure that they are familiar with meaningful use requirements and utilize Clicktate.com in such a way to meet their meaningful use requirements.  This includes but is not limited to using all modules of Clicktate.com including electronic prescribing modules and laboratory modules.  To meet Meaningful Use requirements, users must be proficient at entering and recording certain data, generating reports and utilizing Clicktate.com to enter patient data into the Electronic Health Record.  Cerberus Healthcare, Inc. and Clicktate.com cannot and do not force users to utilize the system in such a way as to ensure that all meaningful use requirements are met and as such we take no responsibility for ensuring that providers meet their individual meaningful use obligations.  It is the responsibility of each Provider or Practice to be familiar with methods of meeting Meaningful Use Requirements and reporting (attesting) those requirements as necessary.  In addition, Cerberus Healthcare, Inc. and clicktate.com do not warrant or assure that the Clicktate EMR will meet all future Meaningful Use requirements other than those currently certified.</p>
			<center><b>Termination of Usage</b></center>
			<p>Either you or Clicktate.com may terminate your right to use Clicktate.com at any time, with or without cause, upon notice. Clicktate.com also reserves the right to terminate or suspend your Clicktate.com membership without prior notice, but Cerberus Healthcare, Inc. and Clicktate.com will confirm such termination or suspension by subsequent notice.  </p>


                      </div>
                  </div>
              </div>
              <!--PP-->
              <div id="openPrivacy" class="modalDialog">
                  <div style="height:800px">
                      <a href="#close" title="Close" class="close">X</a>
                      <center><h1>Privacy Policy</h1></center>
                      <div style="height:700px; overflow:auto;margin:10px;">
                          <p>
                              Our Privacy Policy is designed to assist you in understanding how we collect and use the personal information you provide to us and to assist you in making informed decisions when using our site and our products and services.
                          </p>
                          <h2 style="margin-top:1em">What information do we collect?</h2>
                          <p class='i2'>
                              When you visit our Web site you may provide us with three types of information: personal information you knowingly choose to disclose that is collected on an individual basis, Web site use information collected on an aggregate basis as you and others browse our Web site, and PHI (Protected Health Information) regarding patients and patient care that you obtain and utilize in your day to day activities.
                          </p>
                          <ol>
                              <li>
                                  <b>Personal Information You Choose to Provide</b>
                                  <p>
                                      <b>Registration Information.</b> You will provide us information about yourself, your firm or company, and your practices when you register to be a member of Cerberus Healthcare, Inc. and clicktate.com, register for certain services, or register for email newsletters and alerts. You may also provide additional comments on how you see Cerberus Healthcare, Inc. and clicktate.com servicing your needs and interests.
                                      <b>Email Information.</b> If you choose to correspond with us through email, we may retain the content of your email messages together with your email address and our responses.
                                  </p>
                              </li>
                              <li>
                                  <b>Web Site Use Information</b>
                                  <p>
                                      <b>How Do We Use Information We Collect?</b> As you utilize the online resources of Cerberus Healthcare, Inc. and clicktate.com, any information that we collect from you is maintained in the strictest confidentiality. The information is only used to improve service to you via clicktate.com. By gathering information regarding the utilization of clicktate.com including but not limited to such information as time of usage, aggregate amount of usage, number of notes generated, length of documents and number of smart templates utilized, and also by gathering user feedback we are able to constantly improve our Web site and better serve our customers.
                                  </p>
                              </li>
                              <li>
                                  <b>PHI (Protected Health Information)</b>
                                  <p>
                                      As you utilize clicktate.com, you will provide us certain Protected Health Information so that we can assist you in creating patient documentation and maintaining a patient record.   We maintain compliance with HIPAA by assuring that your patient information is secured in a secure server site on secure servers maintained with 256-bit SSL encryption.  In addition, only those employees and personnel of Cerberus Healthcare, Inc who require access to PHI in order to perform essential functions of maintaining Clicktate and patient health records have access to PHI.   PHI is only accessed by personnel of Cerberus Healthcare, Inc. in the capacity of performing essential duties.  This information is held in the strictest of confidence and administrative audit logs are maintained and available to customers upon request. A complete copy of our HIPAA policy is available upon request.  If you have questions or comments regarding our HIPAA policy, or if you have any concerns or complaints regarding PHI or our HIPAA policy, contact the Medical Director of clicktate.com at 1-888-825-4258 or at info@clicktatemail.info.
                                  </p>
                              </li>
                              <li>
                                  <b>Sharing Information with Third Parties</b>
                                  <p>
                                      We may enter into alliances, partnerships or other business arrangements with third parties who may provide services which could expand or improve clicktate.com, or who are necessary for the routine day to day operation of our business.   We also use third parties to facilitate our business, including, but not limited to, sending email and processing credit card payments. In connection with these offerings and business operations, our partners and other third parties may have access to your personal information for use in connection with business activities. As we develop our business, we may buy or sell assets or business offerings. Customer, email, and visitor information is generally one of the transferred business assets in these types of transactions. We may also transfer such information in the course of corporate divestitures, mergers, or any dissolution.
                                  </p>
                              </li>
                              <li>
                                  <b>Protection of Information</b>
                                  <p>
                                      <b>How Do We Secure Information Transmissions?</b> Email is not recognized as a secure medium of communication. For this reason, we request that you do not send private information or PHI to us by email. The information you may enter on our Web site will be transmitted securely via Secure Sockets Layer SSL, 256 bit encryption services, which are enabled by VeriSign, Inc. Pages utilizing this technology will have URLs that start with HTTPS instead of HTTP. Please contact info@clicktatemail.info or by calling 1-888-8click8 if you have any questions or concerns.
                                      <b>How Can You Access and Correct Your Information?</b> You may request access to all your personally identifiable information that we collect online and maintain in our database by emailing info@clicktatemail.info or by calling 1-888-8click8. Information will only be sent to you via an address or email that you have previously registered.
                                      <b>Certain Disclosures:</b> We may disclosure your personal information if required to do so by law or subpoena or if we believe that such action is necessary to (a) conform to the law or comply with legal process served on us or Affiliated Parties; (b) protect and defend our rights and property, the Site, the users of the Site, and/or our Affiliated Parties; (c) act under circumstances to protect the safety of users of the Site, us, or third parties.
                                  </p>
                              </li>
                              <li>
                                  <b>Links to other Web Sites</b>
                                  <p>
                                      We are not responsible for the practices employed by Web sites linked to or from our Web site nor the information or content contained therein. Often links to other Web sites are provided solely as pointers to information on topics that may be useful to the users of our Web site. Please remember that when you use a link to go from our Web site to another Web site, our Privacy Policy is no longer in effect. Your browsing and interaction on any other Web site, including Web sites which have a link to or from our Web site, is subject to that Web site's own rules and policies. Please review those rules and policies before proceeding.
                                  </p>
                              </li>
                              <li>
                                  <b>Access to Information</b>
                                  <p>
                                      Only those administrative members of Cerberus Healthcare, Inc. who are required to access information in order to maintain or improve the system, perform routine business activities, or to perform security functions will have access to any information on clicktate.com. Other outside vendors may have access to certain information to assist us in our day to day business activities as defined above. A log of all interactions with clicktate.com is maintained for security purposes.
                                  </p>
                              </li>
                              <li>
                                  <b>Your Consent</b>
                                  <p>
                                      By using our Web site you consent to our collection and use of your personal information as described in this Privacy Policy. If we change our privacy policies and procedures, we will post those changes on our Web site to keep you aware of what information we collect, how we use it and under what circumstances we may disclose it.
                                  </p>
                              </li>
                          </ol>
                      </div>

                  </div>
                  </div>
  </body>
</html>
