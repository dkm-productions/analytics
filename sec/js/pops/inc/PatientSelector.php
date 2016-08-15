<?
set_include_path('../../../');
require_once "inc/uiFunctions.php";
/**
 * Patient Selector
 */
?>
<div id="pop-ps" class="pop" style="width:500px">
  <div id="pop-ps-cap" class="pop-cap">
    <div id="pop-ps-cap-text">
      Patient Selector
    </div>
    <a href="javascript:Pop.close()" class="pop-close"></a>
  </div>
  <div class="pop-content" onkeypress="return ifCrClick('pop-ps-search')">
    <div class="pop-frame">
      <div class="pop-frame-head">
        <h1 id="pop-ps-h1">Search for a patient</h1>
        <a class="pencil custom" href="javascript:PatientSelector._singleton.psCustomize()">Customize</a>
      </div>
      <div class="pop-frame-content">
        <ul id="search-ul" class="entry">
          <li id="search-li-pid">
            <label class="first">Patient ID</label>
            <input id="search-pid" type="text" size="20" value="" />
          </li>
          <li id="search-li-name">
            <label class="first">Last Name</label>
            <input id="search-last" type="text" size="25" value="" />
            <label>First</label>
            <input id="search-first" type="text" size="15" value="" />
            <label></label>
          </li>
          <li id="search-li-more" style="font-size:0;line-height:0;height:10px">
          </li>
          <li id="search-li-address">
            <label class="first">Address</label>
            <input id="search-address" type="text" size="40" value="" />
          </li>
          <li id="search-li-phone">
            <label class="first">Phone</label>
            <input id="search-phone" type="text" size="20" value="" />
          </li>
          <li id="search-li-email">
            <label class="first">Email</label>
            <input id="search-email" type="text" size="30" value="" />
          </li>
          <li id="search-li-custom">
            <label class="first">Custom</label>
            <input id="search-custom" type="text" size="40" value="" />
          </li>
        </ul>
        <div class="pop-cmd">
          <a id="pop-ps-search" href="javascript:" onclick="PatientSelector._singleton.psSearch()" class="cmd search">Search</a>
          <span>&nbsp;</span>
          <a id="pop-ps-add" href="javascript:" onclick="PatientSelector._singleton.psAdd()" class="cmd new">Create New Patient...</a>
          <span>&nbsp;</span>
          <a href="javascript:Pop.close()" class="cmd none">Cancel</a>
        </div>
      </div>
    </div>
    <div id="working-ps" class="working">
      <div id="working-msg-ps" class="working-msg">
      </div>
    </div>
    <div id="ss" class="spreadsheet">
      <div id="ss-searching">
        Searching...
      </div>
      <table id="ss-table">
        <tbody id="ss-tbody">
          <tr class="fixed">
            <th>ID</th>
            <th>Name</th>
            <th>Birth</th>
          </tr>
        </tbody>
      </table>
    </div>
    <div id="pop-ps-unavail" class="pop-frame" style="display:none;padding-top:10px">
      <h1>Or, mark time as unavailable</h1>
      <div class="pop-frame-content">
        <div class="pop-cmd" style="margin:0">
          <a id="pop-ps-unavail" href="javascript:unavailable()" class="cmd none">Unavailable...</a>
          <span>&nbsp;</span>
          <a href="javascript:Pop.close()" class="cmd none">Cancel</a>
        </div>        
      </div>
    </div>
  </div>
</div>
<?
/**
 * Custom Client Search
 */
?>
<div id="pop-ccs" class="pop" onmousedown="event.cancelBubble = true">
  <div id="pop-ccs-cap" class="pop-cap">
    <div id="pop-ccs-ccs-text"> 
      Customize Patient Search
    </div>
    <a href="javascript:Pop.close()" class="pop-close"></a>
  </div>
  <div id="pop-css-content" class="pop-content">
    <div class="pop-frame">
      <div class="pop-frame-content">
        <ul class="entry">
          <li>
            <label class="first2"><b>Primary Search</b></label>
            <input type="radio" name="ccs-by" id="ccs-by-id" value="0" />
            <label class="nopad">By ID</label>
          </li>
          <li class="pull">
            <label class="first2">&nbsp;</label>
            <input type="radio" name="ccs-by" id="ccs-by-name" value="1" />
            <label class="nopad">By Name</label>
          </li>
          <li class="push">
            <label class="first2"><b>Include in Search</b></label>
            <? renderLabelCheck("ccs-inc-address", "Address") ?>
            <label>&nbsp;</label>
            <label>&nbsp;</label>
            <label>&nbsp;</label>
            <label>&nbsp;</label>
            <label>&nbsp;</label>
          </li>
          <li class="pull">
            <label class="first2">&nbsp;</label>
            <? renderLabelCheck("ccs-inc-phone", "Phone") ?>
          </li>
          <li class="pull">
            <label class="first2">&nbsp;</label>
            <? renderLabelCheck("ccs-inc-email", "Email") ?>
          </li>
          <li class="pull">
            <label class="first2">&nbsp;</label>
            <? renderLabelCheck("ccs-inc-custom", "Custom") ?>
          </li>
        </ul>
      </div>
    </div>
    <div class="pop-cmd">
      <a href="javascript:" onclick="PatientSelector._singleton.ccsSave()" class="cmd save">Save Changes</a>
      <span>&nbsp;</span>
      <a href="javascript:" onclick="PatientSelector._singleton.ccsReset()" class="cmd none">Reset to Default</a>
      <span>&nbsp;</span>
      <a href="javascript:Pop.close()" class="cmd none">Cancel</a>
    </div>
  </div>
</div>
