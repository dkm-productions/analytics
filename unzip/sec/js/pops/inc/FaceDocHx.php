<?
/**
 * Facesheet Doc/Appt History 
 * Controller: FaceDocHx.js
 */
?>
<div id="fsp-his" class="pop">
  <div id="fsp-his-cap" class="pop-cap">
    <div id="fsp-his-cap-text">
      Documentation/Visit History
    </div>
    <a href="javascript:Pop.close()" class="pop-close"></a>
  </div>
  <div class="pop-content" style="width:720px;padding:0">
    <div class="tabbar">
    </div>
    <div class="tabpanels">
      <div class="tabpanel"> 
        <table border="0" cellpadding="0" cellspacing="0" width="100%">
          <tr>
            <td style="width:150px; vertical-align:top">
              <ul id="his-filter-ul" class="filter"></ul>
            </td>
            <td style="padding-left:5px; vertical-align:top">
              <div id="fsp-his-div" class="fstab" style="height:420px">
                <table id="fsp-his-tbl" class="fsgr single grid">
                  <thead>
                    <tr id="fsp-his-head" class="fixed head">
                      <th>Date</th>
                      <th style="width:80%">Document</th>
                    </tr>
                  </thead>
                  <tbody id="fsp-his-tbody">
                  </tbody>
                </table>
              </div>
            </td>
          </tr>
        </table>
      </div>
      <div class="tabpanel"> 
        <table border="0" cellpadding="0" cellspacing="0" width="100%">
          <tr>
            <td style="width:150px; vertical-align:top">
              <ul id="hisa-filter-ul" class="filter"></ul>
            </td>
            <td style="padding-left:5px; vertical-align:top">
              <div id="fsp-hisa-div" class="fstab" style="height:420px">
                <table id="fsp-hisa-tbl" class="fsgr single grid">
                  <thead>
                    <tr id="fsp-hisa-head" class="fixed head">
                      <th>Date</th>
                      <th style="width:80%">Appointment</th>
                    </tr>
                  </thead>
                  <tbody id="fsp-hisa-tbody">
                  </tbody>
                </table>
              </div>
            </td>
          </tr>
        </table>
      </div>
      <div class="tabpanel"> 
        <table border="0" cellpadding="0" cellspacing="0" width="100%">
          <tr>
            <td style="width:150px; vertical-align:top">
              <ul id="hism-filter-ul" class="filter"></ul>
            </td>
            <td style="padding-left:5px; vertical-align:top">
              <div id="fsp-hism-div" class="fstab" style="height:420px">
                <table id="fsp-hism-tbl" class="fsgr single grid">
                  <thead>
                    <tr id="fsp-hism-head" class="fixed head">
                      <th>Date</th>
                      <th style="width:80%">Subject</th>
                    </tr>
                  </thead>
                  <tbody id="fsp-hism-tbody">
                  </tbody>
                </table>
              </div>
            </td>
          </tr>
        </table>
      </div>
      <div class="pop-cmd cmd-right">
        <table border="0" cellpadding="0" cellspacing="0" width="100%">
          <tr>
            <td nowrap="nowrap">
              <a id='cmd-remote' style='display:none' href='javascript:FaceDocHx.fpTiani()' class='cmd folder'>Remote Documents...</a>
            </td>
            <td style="width:100%">
              <a href="javascript:Pop.close()" class="cmd none">&nbsp;&nbsp;&nbsp;Exit&nbsp;&nbsp;&nbsp;</a>
            </td>
          </tr>
        </table>
      </div>
    </div>
  </div>
</div>
