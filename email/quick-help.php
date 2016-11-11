<?php
$name = null;
if (isset($_GET["n"])) {
  $name = $_GET["n"];
}
$type = 0;
if (isset($_GET["t"])) {
  $type = $_GET["t"];
}
?>
<html>
  <head>
    <title>Quick Start Guide - Clicktate</title>
  </head>
  <body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" bgcolor="#D2E3E0">
    <table width="100%" cellpadding="10" cellspacing="0" bgcolor="#D2E3E0">
      <tr>
        <td valign="top" align="center">
          <table width="600" cellpadding="0" cellspacing="0">
            <tr>
              <td style="background-color:#FFFFFF;text-align:center;font-size:12pt;font-family:Arial;" align="center">
                &nbsp;
              </td>
            </tr>
            <tr>
              <td style="background-color:#EAEEED;border-bottom:5px solid #B3C9C6;border-top:5px solid #B3C9C6;">
                <center>
                  <IMG SRC="img/clicktate-2.png" BORDER="0" title="Clicktate" alt="Clicktate" height="101" width="600">
                </center>
              </td>
            </tr>
            <tr>
              <td bgcolor="#FFFFFF">
                <table width="600" cellpadding="20" cellspacing="0" bgcolor="#FFFFFF">
                  <tr>
                    <td bgcolor="#FFFFFF" valign="top" align="left" style="font-size:10pt;color:#000000;line-height:1.5em;font-family:Arial;text-align:left">
                      <?php if ($name != null) { ?>
                        <h3 style="font-family:'Lucida Grande','Trebuchet MS',Arial;font-size:13pt;font-weight:bold;color:#008C7B;margin-top:1em;margin-bottom:0">
                          Dear <?=$name ?>,
                        </h3>
                        <p style="margin-top:0.6em;margin-bottom:0.7em;">
                          Thank you for registering and welcome to Clicktate!
                          <?php if ($type == 0) { ?>
                          Your trial account is now activated with thirty days of full access to the system.
                          <?php } ?>
                          This <b>Quick Start Guide</b> contains some tips to help get you started.
                        </p>
                      <?php } else { ?>
                        <h3 style="font-family:'Lucida Grande','Trebuchet MS',Arial;font-size:13pt;font-weight:bold;color:#008C7B;margin-top:1em;margin-bottom:0">
                          Quick Start Guide
                        </h3>
                        <p style="margin-top:0.6em;margin-bottom:0.7em;">
                          This page contains some tips for getting started with Clicktate.
                        </p>                        
                      <?php } ?>
                      <p style="margin-top:0.6em;margin-bottom:0.7em;">
                        First, if you haven't already done so, we recommend downloading the <b>Clicktate User Guide</b> (PDF format, 3MB).
                        which details and walks you through the major functions of Clicktate.
                      </p>  
                      <center>
                        <a style="margin-top:1em;margin-bottom:1em;display:block;width:200px;text-align:center;border:1px solid #BCC88E;background:url(http://www.clicktate.com/img/tour-background.png) repeat-x;background-color:#EEF1E1;padding:5px 10px;font-family:'Lucida Grande','Trebuchet MS';font-weight:bold;font-size:12pt;color:#000000;text-decoration:none;" href="http://www.clicktate.com/ClicktateUserGuide.pdf" target="_blank">Download User Guide</a>
                      </center>
                      <p style="margin-top:0.6em;margin-bottom:0.7em;">
                        At any time, if you have questions, or would like to <b>schedule a free, online demo</b>, please don't hesitate to contact us by phone or email: 
                      </p>  
                      <center>
                        <table border="0" cellpadding="0" cellspacing="0" style="text-align:center">
                          <tr>
                            <td style="font-family:Arial;font-weight:bold;font-size:8pt;padding-bottom:3px;">Phone</td>
                            <td></td>
                            <td style="font-family:Arial;font-weight:bold;font-size:8pt;padding-bottom:3px;">Email</td>
                          </tr>
                          <tr>
                            <td valign="top">
                              <h3 style="color:blue;font-family:'Lucida Grande','Trebuchet MS',Arial;font-size:13pt;font-weight:bold;margin-top:0;margin-bottom:0;">
                                1-888-8CLICK8
                              </h3>
                              <div style="font-family:Arial;font-size:8pt;padding-bottom:5px;">(1-888-825-4258)</div>
                            </td>
                            <td width="40">&nbsp;&nbsp;&nbsp;</td>
                            <td valign="top">
                              <a style="font-family:'Lucida Grande','Trebuchet MS',Arial;color:blue;font-size:13pt;font-weight:bold;margin:0" href="mailto:info@clicktate.com">info@clicktate.com</a>
                            </td>
                          </tr>
                        </table>
                      </center>
                      <h3 style="font-family:'Lucida Grande','Trebuchet MS',Arial;font-size:13pt;font-weight:bold;color:#008C7B;margin-top:1.5em;margin-bottom:0">
                        Browser requirements
                      </h3>
                      <ul>
                        <li><b>Internet Explorer</b> (version 6, 7 or 8)</li>
                        <li><b>Popup-blocker</b> <span style="color:red;"><b>off</b></span></li>
                      </ul>
                      <p style="margin-top:0.6em;margin-bottom:0.7em;">
                        Clicktate's note generator is produced as a popup window, so it is important that your Internet Explorer is configured to <b>allow</b> popups from <b>clicktate.com</b>.
                        If you do not know how to do this, refer to our <a href="http://www.clicktate.com/ClicktateUserGuide.pdf" target="_blank">User Guide</a> which explains this procedure. 
                      </p>
                      <h3 style="font-family:'Lucida Grande','Trebuchet MS',Arial;font-size:13pt;font-weight:bold;color:#008C7B;margin-top:1.8em;margin-bottom:0">
                        Info and tips
                      </h3>
                      <p style="margin-top:0.6em;margin-bottom:0.7em;">
                        This section describes the main pages of Clicktate and walks you through creating a patient and starting a regular SOAP note. 
                      </p>
                      <h4 style="font-size:11pt;font-weight:bold;margin-top:1.5em;margin-bottom:0">The "Welcome" (home) page</h4>
                      <p style="margin-top:0.6em;margin-bottom:0.7em;">
                        Your account's home page serves as a "launching pad" to general Clicktate functions, grouped in categories (patient, documents, scheduling, and profile). 
                      </p>
                      <center>
                        <IMG style="border:1px solid #c0c0c0;margin-top:0.3em" SRC="img/welcome.png" BORDER="0" title="Home Page" alt="Home Page" height="313" width="411">
                      </center>
                      <p style="margin-top:0.6em;margin-bottom:0.7em;">
                        The <b>navigation bar</b> located at the top of each page (including the home page) is another way to navigate throughout the system. 
                        This is also where you <b>logout</b>.     
                      </p>
                      <center>
                        <IMG style="border:1px solid #c0c0c0;margin-top:0.3em" SRC="img/nav-bar.png" BORDER="0" title="Navigation Bar" alt="Navigation Bar" height="46" width="497">
                      </center>
                      <h4 style="font-size:11pt;font-weight:bold;margin-top:1.5em;margin-bottom:0">Create a patient!</h4> 
                      <p style="margin-top:0.6em;margin-bottom:0.7em;">
                        Most Clicktate functions (including note generation) are tied to patients. 
                        Because your trial account starts out empty, one of the first things you should do is create a test patient.
                        From the <b>Home</b> page, click "Create a new patient" to bring up the New Patient entry form. 
                      </p>
                      <p style="margin-top:0.6em;margin-bottom:0.7em;">
                        When you've entered the required fields for a new patient and click "Create and Continue", the patient record is created and you will be brought to that patient's <b>facesheet</b>. 
                      </p>
                      <h4 style="font-size:11pt;font-weight:bold;margin-top:1.5em;margin-bottom:0">The facesheet</h4> 
                      <p style="margin-top:0.6em;margin-bottom:0.7em;">
                        The <b>facesheet</b> is both an overview of that patient's medical record and a "launching pad" for patient-specific functions (e.g. creating a medical note, or adding an allergy).   
                      </p>
                      <center>
                        <IMG style="border:1px solid #c0c0c0;" SRC="img/facesheet.png" BORDER="0" title="Facesheet" alt="Facesheet" height="427" width="360">
                      </center>
                      <p style="margin-top:0.6em;margin-bottom:0.7em;">
                        The <b>workflow</b> area of the facesheet contains shortcuts for creating an appointment, recording vitals, and managing documents for that patient.
                      </p>
                      <center>
                        <IMG style="border:1px solid #c0c0c0;margin-top:0.3em" SRC="img/workflow.png" BORDER="0" title="Workflow" alt="Workflow" height="152" width="168">
                      </center>
                      <p style="margin-top:0.6em;margin-bottom:0.7em;">
                        To create a standard SOAP note, click "Create New Document" here, followed by the <b>"Blank Medical Note"</b> button, which will bring up the <b>console</b>. 
                      </p>
                      <h4 style="font-size:11pt;font-weight:bold;margin-top:1.5em;margin-bottom:0">The console</h4> 
                      <p style="margin-top:0.6em;margin-bottom:0.7em;">
                        The <b>console</b> is Clicktate's document builder.
                        This is brought up as as a <b>popup</b>, so please make sure your Internet Explorer is configured to allow popups for clicktate.com (see "Browser Requirements" above).   
                      </p>
                      <center>
                        <IMG SRC="img/console.png" BORDER="0" title="Console" alt="Console" height="447" width="500">
                      </center>                      
                      <p style="margin-top:0.6em;margin-bottom:0.7em;">
                        Paragraphs may be inserted into the document by clicking them in the <b>Template Map</b> on the left. 
                        You can also type into the Template Map's <b>search box</b> to search for a particular diagnosis, impression, or set of keywords.   
                        Refer to our <a href="http://www.clicktate.com/ClicktateUserGuide.pdf" target="_blank">User Guide</a> for complete details.
                      </p>                      
                      <h4 style="font-size:11pt;font-weight:bold;margin-top:1.5em;margin-bottom:0">Other pages</h4> 
                      <p style="margin-top:0.6em;margin-bottom:0.7em;">
                        The remaining pages available in the navigation bar include:   
                      </p>
                      <ul>
                        <li>
                          <b>Patients</b> - Search and manage entire patient database  
                        </li>
                        <li>
                          <b>Documents</b> - Search and manage documents entered for patients   
                        </li>
                        <li>
                          <b>Scheduling</b> - Appointments and calendar management   
                        </li>
                        <li>
                          <b>Profile</b> - Update personal/practice info, manage support accounts  
                        </li>
                      </ul>
                      <?php if ($type == 0) { ?>
                        <h3 style="font-family:'Lucida Grande','Trebuchet MS',Arial;font-size:13pt;font-weight:bold;color:#008C7B;margin-top:1.5em;margin-bottom:0">
                          Ready?
                        </h3>
                        <p style="margin-top:0.6em;margin-bottom:1em;">
                          Thank you again for registering with <b>Clicktate</b>.
                          We encourage you to use Clicktate extensively during this trial period to become familiar with its many features.
                          Remember, if you have any problems, questions, or would like to set up an online demo with a Clicktate representative, don't hesitate to contact us.
                        </p>
                      <?php } else { ?>
                        <h3 style="font-family:'Lucida Grande','Trebuchet MS',Arial;font-size:13pt;font-weight:bold;color:#008C7B;margin-top:1.5em;margin-bottom:0">
                          Need more help?
                        </h3>
                        <p style="margin-top:0.6em;margin-bottom:1em;">
                          Don't forget, if you have any problems, questions, or would like to set up an online demo with a Clicktate representative, don't hesitate to contact us.
                        </p>
                      <?php } ?>                      
                        <center>
                        <table border="0" cellpadding="0" cellspacing="0" style="text-align:center">
                          <tr>
                            <td style="font-family:Arial;font-weight:bold;font-size:8pt;padding-bottom:3px;">Phone</td>
                            <td></td>
                            <td style="font-family:Arial;font-weight:bold;font-size:8pt;padding-bottom:3px;">Email</td>
                          </tr>
                          <tr>
                            <td valign="top">
                              <h3 style="color:blue;font-family:'Lucida Grande','Trebuchet MS',Arial;font-size:13pt;font-weight:bold;margin-top:0;margin-bottom:0;">
                                1-888-8CLICK8
                              </h3>
                              <div style="font-family:Arial;font-size:8pt;padding-bottom:5px;">(1-888-825-4258)</div>
                            </td>
                            <td width="40">&nbsp;&nbsp;&nbsp;</td>
                            <td valign="top">
                              <a style="font-family:'Lucida Grande','Trebuchet MS',Arial;font-size:13pt;font-weight:bold;margin:0;color:blue" href="mailto:info@clicktate.com">info@clicktate.com</a>
                            </td>
                          </tr>
                        </table>
                      </center>
                      <?php if ($name != null) { ?>
                        <h3 style="padding-left:400px;font-family:'Lucida Grande','Trebuchet MS',Arial;font-size:13pt;font-weight:bold;color:#008C7B;margin-top:1.5em;margin-bottom:0">
                          Thank you,
                        </h3>
                        <h3 style="padding-left:400px;font-family:'Lucida Grande','Trebuchet MS',Arial;font-size:11pt;font-weight:bold;color:#008C7B;margin-top:0.1em;margin-bottom:0">
                          Clicktate Staff
                        </h3>
                      <?php } ?>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>