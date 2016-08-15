<?
// Question pop (regular/calculator/calendar/freetext)
// To call: showQuestion(q)  // JQuestion
// Callbacks: popQuestionCallback(q)  // JQuestion with updated sel,unsel,del
//            popQuestionDeleteCallback(q)
?>
<div id="pop-q" class="pop" unselectable="on" onmousedown="event.cancelBubble = true">
  <div id="pop-q-cap" class="pop-cap" unselectable="on">
    <div id="pop-q-cap-text" unselectable="on">
    </div>
    <a href="javascript:closeOverlayPop()" class="pop-close"></a>
  </div>
  <div class="pop-content" style="padding:5px">
    <div id="pop-q-clear" class="pop-cmd pop-cmd-fs" style="display:none">
      <a class="cmd delete" href="javascript:" onclick="pqClear(); return false">Clear</a>
      <span>&nbsp;</span>
      <a class="cmd none"href="javascript:" onclick='closeOverlayPop(); return false'>Cancel</a>
    </div>
    <div id="pop-cbo-p1">
      <div id="pop-csi-sing" style="display:none">
        <div id="pop-csi-sing-opts" class="options">
          <table border="0" cellpadding="0" cellspacing="0">
            <tbody id="pop-csi-sing-tbody">
            </tbody>
          </table>
        </div>
        <div id="pop-cbo-sing-cap" class="opthead">Or, select one of the following:</div>
      </div>
      <div id="pop-q-sing">
        <div id="pop-q-sing-opts" class="options">
          <table border="0" cellpadding="0" cellspacing="0">
            <tbody id="pop-q-sing-tbody">
            </tbody>
          </table>
        </div>
        <div id="pop-q-optlist" class="optionsList" style="display:none">
          <ul id="pop-q-optlist-ul">
          </ul>
        </div>
        <div id="pop-q-free" class="free" style="display:none">
          <input type="text" id="q-free" value="other" onkeypress="return ifCrClick('q-cmd-free')" onclick="this.select()">
          <div id="q-cmd" class="pop-cmd">
            <a id="q-cmd-free" href="javascript:" class="cmd none">Insert Free Text</a>
          </div>
        </div>
      </div>
    </div>
    <div id="pop-cbo-p2">
      <div id="pop-q-mult" style="display:none">
        <div id="pop-cbo-head" class="cbohead">
          <div id="pop-q-cbo-cap"></div>
          <a href="javascript:pqShowComboPage(true)" class="back">Back</a>
        </div>
        <div id="pop-q-mult-cap" class="opthead">
        </div>
        <div id="pop-q-mult-opts" class="multi">
          <div id='pop-q-mult-scrolling' class='scrolling' style='padding-right:17px'>
            <table border="0" cellpadding="0" cellspacing="0">
              <tbody id="pop-q-mult-tbody" onclick="quiMultiClick()" ondblclick="quiMultiClick()">
              </tbody>
            </table>
            <table border="0" cellpadding="0" cellspacing="0" style="margin-top:3px">
              <tbody id="pop-q-multfree-tbody" onclick="quiMultFreeClick()">
              </tbody>
            </table>
          </div>
          <div id="pop-q-mult-cmd" class="pop-cmd">
            <a style="display:none" class="cmd none" id="q-mult-apply" href="javascript:" onclick="quiSetMulti()">Apply Checkmarked</a>
            <!--
            <div style="display:none" id="q-mult-apply-info" class="pop-information" style="margin-top:5px;">
              To add multiple items to the note, check the appropriate boxes above.
            </div>
            -->
            <span id="q-clone-cmd" style="display:none">
              <a class="cmd save" href="javascript:" onclick="quiSetMulti()">Save Changes</a>
              <span id="q-delete-span">
                <span>&nbsp;</span>
                <a class="cmd delete-red" href="javascript:" onclick="quiDelete()">Delete</a>
              </span>
              <span>&nbsp;</span>
              <a class="cmd none" href="javascript:" onclick="quiCancel()">Cancel</a>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<div id="pop-q-calc" class="pop" onmousedown="event.cancelBubble = true">
  <div id="pop-q-calc-cap" class="pop-cap" unselectable="on">
    <div id="pop-q-calc-cap-text" unselectable="on">
    </div>
    <a href="javascript:closeOverlayPop()" class="pop-close"></a>
  </div>
  <div class="pop-content" style="width:230px;padding:5px;text-align:center;" onkeydown="qcKey(); return false">
    <div id="pop-calc-clear" class="pop-cmd pop-cmd-fs" style="padding-bottom:10px;display:none">
      <a class="cmd delete" href="javascript:" onclick="pqClear(); return false">Clear</a>
    </div>
    <div id='disp-declbs'>
      <? renderLabelRadio2('disp-dec', 'lbsdec', 'Decimal', true, null, 'lbsdecClick()') ?>
      &nbsp;
      <? renderLabelRadio2('disp-lbs', 'lbsdec', 'Lbs/Oz', null, null, 'lbsdecClick()') ?>
    </div>
    <div id="pop-calc-display" style="width:210px;">0</div>
    <div id="pop-calc-entry">
      <table border=0 cellpadding=0 cellspacing=0>
        <tr>
          <td width="44px">
            <a id="calckey7" class="key" ondblclick="qcPush('7')" href="javascript:qcPush('7')">7</a>
          </td>
          <td width=2 nowrap></td>
          <td width="44px">
            <a class="key" ondblclick="qcPush('8')" href="javascript:qcPush('8')">8</a>
          </td>
          <td width=2 nowrap></td>
          <td width="44px">
            <a class="key" ondblclick="qcPush('9')" href="javascript:qcPush('9')">9</a>
          </td>
          <td width=12 nowrap></td>
          <td>
            <a class="key calfn calback" ondblclick="qcPush('b')" href="javascript:qcPush('b')">&#217;</a>
          </td>
          <td width=2 nowrap></td>
        </tr>
        <tr>
          <td>
            <a class="key" ondblclick="qcPush('4')" href="javascript:qcPush('4')">4</a>
          </td>
          <td></td>
          <td>
            <a class="key" ondblclick="qcPush('5')" href="javascript:qcPush('5')">5</a>
          </td>
          <td></td>
          <td>
            <a class="key" ondblclick="qcPush('6')" href="javascript:qcPush('6')">6</a>
          </td>
          <td></td>
          <td>
            <a class="key calfn" ondblclick="qcPush('c')" href="javascript:qcPush('c')">C</a>
          </td>
        </tr>
        <tr>
          <td>
            <a class="key" ondblclick="qcPush('1')" href="javascript:qcPush('1')">1</a>
          </td>
          <td></td>
          <td>
            <a class="key" ondblclick="qcPush('2')" href="javascript:qcPush('2')">2</a>
          </td>
          <td></td>
          <td>
            <a class="key" ondblclick="qcPush('3')" href="javascript:qcPush('3')">3</a>
          </td>
          <td></td>
          <td>
            <a class="key calfn" ondblclick="qcPush('n')" href="javascript:qcPush('n')">N/A</a>
          </td>
        </tr>
        <tr>
          <td colspan=3>
            <a class="key calz" ondblclick="qcPush('0')" href="javascript:qcPush('0')">0</a>
          </td>
          <td></td>
          <td>
            <a class="key" ondblclick="qcPush('.')" href="javascript:qcPush('.')">.</a>
          </td>
          <td></td>
          <td>
            <a class="key calfn" ondblclick="qcPush('-')" href="javascript:qcPush('-')">+/-</a>
          </td>
        </tr>
      </table>
    </div>
    <div class="pop-cmd">
      <a class="cmd ok" style="width:120px" id="qc-cmd-ok" href="javascript:" onclick='qcOk(); return false'>OK</a>
      <span>&nbsp;</span>
      <a class="cmd none" href="javascript:" onclick='closeOverlayPop(); return false'>Cancel</a>
    </div>
  </div>
</div>
<div id="pop-q-calendar" class="pop" onmousedown="event.cancelBubble = true" onmousewheel="calMouseScroll()">
  <div id="pop-q-calendar-cap" class="pop-cap" unselectable="on">
    <div id="pop-q-calendar-cap-text" unselectable="on">
    </div>
    <a href="javascript:closeOverlayPop()" class="pop-close"></a>
  </div>
  <div id="pop-content" class="pop-content" style="padding:5px;">
    <div id="pop-cal-clear" class="pop-cmd pop-cmd-fs" style="padding-bottom:10px;display:none">
      <a class="cmd delete" href="javascript:" onclick="pqClear(); return false">Clear</a>
      <span>&nbsp;</span>
      <a class="cmd none" href="javascript:" onclick='closeOverlayPop(); return false'>Cancel</a>
    </div>
    <div id="popCal">
      <table border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td align="center"><table border="0" cellspacing="0">
            <tr>
              <td align="right">
                <select size="1" id="popcboMonth" onchange="msetDateByCombos()">
                  <option value="January" selected>January</option>
                </select></td>
              <td width="5"></td>
              <td>
                <select size="1" id="popcboYear" onchange="msetDateByCombos()" onmousewheel="event.cancelBubble=true">
                  <option value="January" selected>2001</option>
                </select></td>
            </tr>
          </table>
          </td>
        </tr>
        <tr>
          <td height="5"></td>
        </tr>
      </table>
      <div style="background:black;padding:10px;border:1px solid #c0c0c0">
        <table class="styleBody" style="border:1px solid #517973" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td><div align="center"><table border="0" cellpadding="3" cellspacing="1" class="styleBody" id="tblCal" onmouseover="monMouseOver()" onmouseout="monMouseOut()" onclick="calOnClick()">
              <tr>
                <td colspan="7"></td>
              </tr>
              <tr>
                <td align="center" colspan="7">
                  <table class="styleBody" border="0" cellpadding="0" cellspacing="0" bgcolor="#e0ecf5">
                    <tr>
                      <td width="3"></td>
                      <td><img alt="Previous Month" class="styleLink" src="img/prevMonth3.gif" ondblclick="mprevMonth()" onclick="mprevMonth()" WIDTH="12" HEIGHT="12"></td>
                      <td nowrap align="center" class="styleTitle">
                        <table border=0 cellpadding=0 cellspacing=0>
                          <tr>
                            <td><a href="javascript:" id="aMonth">January</a></td>
                            <td width=8></td>
                            <td><a href="javascript:" id="aYear">2001</a></td>
                          </tr>
                        </table>
                      </td>
                      <td align="right"><img alt="Next Month" class="styleLink" src="img/nextMonth3.gif" ondblclick="mnextMonth()" onclick="mnextMonth()" WIDTH="12" HEIGHT="12"></td>
                      <td width="3"></td>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr>
                <td class="styleDayTitle">S</td>
                <td class="styleDayTitle">M</td>
                <td class="styleDayTitle">T</td>
                <td class="styleDayTitle">W</td>
                <td class="styleDayTitle">T</td>
                <td class="styleDayTitle">F</td>
                <td class="styleDayTitle">S</td>
              </tr>
              <tr>
                <td class="styleOffDay">&nbsp;</td>
                <td class="styleOffDay">&nbsp;</td>
                <td class="styleDay">1</td>
                <td class="styleDay">2</td>
                <td class="styleDay">3</td>
                <td class="styleDay">4</td>
                <td class="styleDay">5</td>
              </tr>
              <tr>
                <td class="styleDay">6</td>
                <td class="styleDay">7</td>
                <td class="styleDay">8</td>
                <td class="styleDay">9</td>
                <td class="styleDay">10</td>
                <td class="styleDay">11</td>
                <td class="styleDay">12</td>
              </tr>
              <tr>
                <td class="styleDay">13</td>
                <td class="styleDay">14</td>
                <td class="styleDay">15</td>
                <td class="styleDay">16</td>
                <td class="styleDay">17</td>
                <td class="styleDay">18</td>
                <td class="styleDay">19</td>
              </tr>
              <tr>
                <td class="styleDay">20</td>
                <td class="styleDay">21</td>
                <td class="styleDay">22</td>
                <td class="styleDay">23</td>
                <td class="styleDay">24</td>
                <td class="styleDay">25</td>
                <td class="styleDay">26</td>
              </tr>
              <tr>
                <td class="styleDay">27</td>
                <td class="styleDay">28</td>
                <td class="styleDay">29</td>
                <td class="styleDay">30</td>
                <td class="styleDay">31</td>
                <td class="styleOffDay">&nbsp;</td>
                <td class="styleOffDay">&nbsp;</td>
              </tr>
              <tr id="mtrExtraRow">
                <td class="styleOffDay">&nbsp;</td>
                <td class="styleOffDay"></td>
                <td class="styleOffDay"></td>
                <td class="styleOffDay"></td>
                <td class="styleOffDay"></td>
                <td class="styleOffDay"></td>
                <td class="styleOffDay"></td>
              </tr>
            </table>
            </div></td>
          </tr>
        </table>
      </div>
      <div class="options" style="padding:5px 0">
        <table border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td><a id="calSelMonth" style="width:115px" href="javascript:calMonthOnly()">Month</a></td>
            <td><a id="calSelYear" style="width:60px" href="javascript:calYearOnly()" onmousewheel="calMouseScroll(1)">Year</a></td>
            <td><a href="javascript:" onclick='calUnk(); return false'>Date Unknown</a></td>
          </tr>
        </table>
      </div>
      <div id="chelp">
        For a specific date, click the appropriate day on the calendar.<br><br>
        If specific date not known, you may also click just the <b>month name</b> or the <b>year</b> at the top.
      </div>
      <div id="pop-cal-cancel" class="pop-cmd" style="padding-bottom:0">
        <a class="cmd none" href="javascript:" onclick='closeOverlayPop(); return false'>Cancel</a>
      </div>
    </div>
  </div>
</div>
<?
// Free text
// showFreetext(callback, text, caption, deletable)
// callback supplied with text (null if deleted)
?>
<div id="pop-free" class="pop" onmousedown="event.cancelBubble = true">
  <div id="pop-free-cap" class="pop-cap" unselectable="on">
    <div id="pop-free-cap-text" unselectable="on">
    </div>
    <a href="javascript:closeOverlayPop()" class="pop-close"></a>
  </div>
  <div id="pop-content" class="pop-content" style="padding:5px 10px 5px 10px">
    <div id="pop-free-clear" class="pop-cmd pop-cmd-fs" style="padding-bottom:10px;display:none">
      <a class="cmd delete" href="javascript:" onclick="pqClear(); return false">Clear</a>
    </div>
    <div id="popF" class="free" >
      <textarea rows="5" id="freetext" onkeypress="return ifCrClick('freeOk')"></textarea>
      <div class="pop-cmd">
        <a id="freeOk" class="cmd ok" href="javascript:" onclick='freeOk(); return false'>OK</a>
        <span id="free-delete-span">
          <span>&nbsp;</span>
          <a id="freeDelete" class="cmd none" href="javascript:" onclick='freeDelete(); return false'>Delete</a>
        </span>
        <span>&nbsp;</span>
        <a id="freeCancel" class="cmd none" href="javascript:" onclick='freeCancel(); return false'>Cancel</a>
      </div>
    </div>
  </div>
</div>
<?
// Prescription writer
// To call: showRx(rx)
//   rx
//     date
//     JClient client
//     JUser me
//     JUser[] docs  // group providers
//     docid         // selected provider (optional)
//     JDataMed[] meds
// Callback: rxCallback(meds)
//   JDataMed[] meds  // just the selected ones printed
//     rx             // new field: freetext, e.g. (RX 11/1/2009 Disp: 1200, Refills: None)
?>
<form id="frm-rx" method="post" action="print-rx.php" target="rxw">
  <div id="pop-rx" class="pop" onmousedown="event.cancelBubble = true" style="width:720px">
    <div id="pop-rx-cap" class="pop-cap">
      <div id="pop-rx-cap-text">
        Clicktate - Print Medications
      </div>
      <a href="javascript:closeOverlayPop()" class="pop-close"></a>
    </div>
    <div class="pop-content">
      <div class="pop-frame" style="display">
        <div id="rx-head" class="pop-frame-content">
          <input type="hidden" id="rx-submit-date" name="date" />
          <input type="hidden" id="rx-submit-client" name="client" />
          <input type="hidden" id="rx-submit-dob" name="dob" />
          <input type="hidden" id="rx-pp" name="pp" />
          <input type="hidden" id="rx-doc-type" name="doctype" />
          <input type="hidden" id="rx-doc-name" name="rxdocName" />
          <input type="hidden" id="rx-doc-lic" name="licLine" />
          <input type="hidden" id="rx-prac-name" name="prac" />
          <input type="hidden" id="rx-prac-addr" name="addrLine" />
          <input type="hidden" id="rx-prac-phone" name="phone" />
          <div id="rx-head-doc">
            <select id="rx-docs" onchange="setRxDoc()">
            </select>
          </div>
          <div id="rx-head-prac"></div>
          <div id="rx-head-prac-addr"></div>
          <div id="rx-head-prac-phone"></div>
          <div id="rx-head-lic"></div>
          <div id="rx-head-date"></div>
          <div id="rx-head-client"></div>
          <div id="rx-head-client-dob"></div>
        </div>
      </div>
      <div id="rx-med-div" class="fstab" style="margin-top:10px;height:360px">
        <table id="rx-med-tbl" class="fsb single">
          <thead>
            <tr class="fixed head nbp">
              <th></th>
              <th style="width:50%"></th>
              <th style="width:40%"></th>
              <th id='rx-med-th-disp0' style="width:5%">Disp</th>
              <th id='rx-med-th-refill0'>Refills</th>
              <th id='rx-med-th-dns0'>DNS</th>
              <th id='rx-med-th-daw0'>DAW</th>
            </tr>
            <tr class="fixed head">
              <th style="padding-bottom:0;">
                <input id="rx-med-tbl-ck" type="checkbox" onclick="checkAllCol1(this)" title="Check/uncheck all" />
              </th>
              <th style="vertical-align:bottom;padding-bottom:2px;">
                Medication
              </th>
              <th style="vertical-align:bottom;padding-bottom:2px;">
                Sig
              </th>
              <th id='rx-med-th-disp'>
                <select id="rx-disps" onchange="setDisps(this)">
                  <option value=""></option>
                  <option value="30 days">30-day</option>
                  <option value="90 days">90-day</option>
                </select>
              </th>
              <th id='rx-med-th-refill'>
                <select id="rx-refills" onchange="setRefills(this)">
                  <option>None</option>
                  <option>1</option>
                  <option>2</option>
                  <option>3</option>
                  <option>4</option>
                  <option>5</option>
                  <option>6</option>
                  <option>7</option>
                  <option>8</option>
                  <option>9</option>
                  <option>10</option>
                  <option>11</option>
                  <option>12</option>
                </select>
              </th>
              <th id='rx-med-th-dns'>
                <input id="rx-dnss" type="checkbox" onclick="checkAllDns(this)" title="Check/uncheck all" />
              </th>
              <th id='rx-med-th-daw'>
                <input id="rx-daws" type="checkbox" onclick="checkAllDaw(this)" title="Check/uncheck all" />
              </th>
            </tr>
            <tr class="head fixed"><td colspan="7" style="height:3px"></td></tr>
          </thead>
          <tbody id="rx-med-tbody">
          </tbody>
        </table>
      </div>
      <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
          <td nowrap="nowrap">
            <div class="pop-cmd">
              <label>
                Print checked as:
              </label>
              <span id='rx-med-cmd-rx'>
                <a id="med-cmd-print-rx4" href="javascript:printRx(0, 0)" class="cmd fpp">RX (4 per page)</a>
                <a id="med-cmd-print-rx1" href="javascript:printRx(0, 1)" class="cmd opp">RX (1 per page)</a>
                <span>&nbsp;</span>
                <span>&nbsp;</span>
                <span>&nbsp;</span>
              </span>
              <a id="med-cmd-print-list" href="javascript:printRx(1, 0)" class="cmd medlist">Med List for Patient</a>
            </div>
          </td>
          <td style="width:100%">
            <div class="pop-cmd cmd-right">
              <a href="javascript:closeOverlayPop()" class="cmd none">&nbsp;&nbsp;&nbsp;Exit&nbsp;&nbsp;&nbsp;</a>
            </div>
          </td>
        </tr>
      </table>
    </div>
  </div>
</form>
<?
// Medicine picker
// Before showing: loadMedHistory(meds)  // JDataMed[]
// For updates: showMed(id, name, amt, freq, asNeed, meals, route, length, disp)
// For adds: showMed()
// Callbacks: medOkCallback(med)
//            medDeleteCallback(medId)
?>
<div id="popMed" class="pop" style="width:488px" onmousedown="event.cancelBubble=true">
  <div id="popMed-cap" class="pop-cap" unselectable="on">
    <div id="medCap" unselectable="on">
      Med Selector
    </div>
    <a href="javascript:closeOverlayPop()" class="pop-close"></a>
  </div>
  <div class="pop-content small-pad">
    <div id="popM" class="med">
      <table border=0 cellpadding=0 cellspacing=0>
        <tr>
          <th>Name / Strength</th>
          <th></th>
        </tr>
        <tr>
          <td><input id="medName" type="text" size="55" onkeyup="testMedKey()" onfocus="medShow(0)"></td>
          <td><input class="medSearch" type="button" value="Search..." onclick="focus('medName');doMedSearch()" /></td>
        </tr>
      </table>
      <div id="popM2">
        <table border=0 cellpadding=0 cellspacing=0>
          <tr style="padding-top:4px">
            <th>Amount</th>
            <th></th>
            <th>Freq</th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
          </tr>
          <tr>
            <td><input id="medAmt" type="text" size="12" onfocus="medShow(1)"></td>
            <td></td>
            <td><input id="medFreq" type="text" size="15" onfocus="medShow(2)"></td>
            <td></td>
            <td><input id="medAsNeed"  type="checkbox">As needed</td>
            <td></td>
            <td><input id="medMeals"  type="checkbox">With meals</td>
          </tr>
        </table>
        <table border=0 cellpadding=0 cellspacing=0>
          <tr style="padding-top:4px">
            <th>Route</th>
            <th></th>
            <th>Length</th>
            <th></th>
            <th>Disp</th>
            <th></th>
          </tr>
          <tr>
            <td><input id="medRoute" type="text" size="18" onfocus="medShow(3)"></td>
            <td></td>
            <td><input id="medLength" type="text" size="15" onkeydown="if(event.keyCode==9){focus('medDisp');return false}" onfocus="medShow(4)"></td>
            <td></td>
            <td><input id="medDisp" type="text" size="15" onkeydown="if(event.keyCode==9){focus('medName');return false}" onfocus="onfocusMedDisp()"></td>
          </tr>
        </table>
      </div>
      <div id="m0" style="display:block">
        <div id="medOptTitle">Name Search Results</div>
        <div id="medList">
          <div id="medListTitle"></div>
          <div id="medListNone"></div>
          <ul id="medListUl"></ul>
          <div id="medListFoot"></div>
        </div>
      </div>
      <div id="m1" style="display:none">
        <div id="medOptTitle">Amount Options</div>
        <div class="medOptions">
          <ul style="margin-bottom:0;padding-bottom:0">
            <li><a href="javascript:" onclick="upMedAmt(this); return false">1/4</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">1/3</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">1/2</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">1/2 - 1</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">1</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">1 1/2</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">2</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">3</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">4</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">5</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">6</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">7</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">8</a></li>
          </ul>
          <ul style="clear:both">
            <li><a href="javascript:" onclick="upMedAmt(this); return false">1/4 tsp</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">1/3 tsp</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">1/2 tsp</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">3/4 tsp</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">1 tsp</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">1 1/4 tsp</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">1 1/3 tsp</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">1 1/2 tsp</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">1 3/4 tsp</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">2 tsp</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">3 tsp</a></li>
          </ul>
          <ul style="clear:both">
            <li><a href="javascript:" onclick="upMedAmt(this); return false">0.4 ml</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">0.5 ml</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">0.8 ml</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">1 ml</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">1.2 ml</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">1 1/2 ml</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">1.6 ml</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">2 ml</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">2 1/2 ml</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">3 ml</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">3 1/2 ml</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">4 ml</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">4 1/2 ml</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">5 ml</a></li>
          </ul>
          <ul style="clear:both">
            <li><a href="javascript:" onclick="upMedAmt(this); return false">1 drop</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">2 drops</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">3 drops</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">4 drops</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">5 drops</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">10 drops</a></li>
            <li><a style="visibility:hidden" href="javascript:" onclick="upMedAmt(this); return false">none</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">1 puff</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">2 puffs</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">4 puffs</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">8 puffs</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">16 puffs</a></li>
            <li><a style="visibility:hidden" href="javascript:" onclick="upMedAmt(this); return false">none</a></li>
            <li><a style="visibility:hidden" href="javascript:" onclick="upMedAmt(this); return false">none</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">1 spray</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">2 sprays</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">3 sprays</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">4 sprays</a></li>
            <li><a style="visibility:hidden" href="javascript:" onclick="upMedAmt(this); return false">none</a></li>
            <li><a style="visibility:hidden" href="javascript:" onclick="upMedAmt(this); return false">none</a></li>
            <li><a style="visibility:hidden" href="javascript:" onclick="upMedAmt(this); return false">none</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">1/2 capful</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">1 capful</a></li>
            <li><a href="javascript:" onclick="upMedAmt(this); return false">17 gms</a></li>
          </ul>
          <ul style="clear:both">
            <li><a class="big" href="javascript:" onclick="upMedAmt(this); return false">as directed</a></li>
          </ul>
        </div>
      </div>
      <div id="m2" style="display:none">
        <div id="medOptTitle">Frequency Options</div>
        <div class="medOptions">
          <ul>
            <li><a href="javascript:" onclick="upMedFreq(this); return false">every hour</a><div style="clear:both"></li>
            <li><a href="javascript:" onclick="upMedFreq(this); return false">every 2 hours</a></li>
            <li><a href="javascript:" onclick="upMedFreq(this); return false">every 3 hours</a></li>
            <li><a href="javascript:" onclick="upMedFreq(this); return false">every 4 hours</a></li>
            <li><a href="javascript:" onclick="upMedFreq(this); return false">every 6 hours</a></li>
            <li><a href="javascript:" onclick="upMedFreq(this); return false">every 8 hours</a></li>
            <li><a href="javascript:" onclick="upMedFreq(this); return false">every 12 hours</a></li>
          </ul>
          <ul style="clear:both">
            <li><a href="javascript:" onclick="upMedFreq(this); return false">daily<br>&nbsp;</a></li>
            <li><a href="javascript:" onclick="upMedFreq(this); return false">BID<br>&nbsp;</a></li>
            <li><a href="javascript:" onclick="upMedFreq(this); return false">TID<br>&nbsp;</a></li>
            <li><a href="javascript:" onclick="upMedFreq(this); return false">QID<br>&nbsp;</a></li>
            <li><a href="javascript:" onclick="upMedFreq(this); return false">five times daily</a></li>
            <li><a href="javascript:" onclick="upMedFreq(this); return false">QAM<br>&nbsp;</a></li>
            <li><a href="javascript:" onclick="upMedFreq(this); return false">QHS<br>&nbsp;</a></li>
          </ul>
          <ul style="clear:both">
            <li><a href="javascript:" onclick="upMedFreq(this); return false">every 2 days</a></li>
            <li><a href="javascript:" onclick="upMedFreq(this); return false">every 3 days</a></li>
            <li><a href="javascript:" onclick="upMedFreq(this); return false">MWF<br>&nbsp;</a></li>
            <li><a href="javascript:" onclick="upMedFreq(this); return false">Mon/Thur<br>&nbsp;</a></li>
            <li><a href="javascript:" onclick="upMedFreq(this); return false">once weekly</a></li>
            <li><a href="javascript:" onclick="upMedFreq(this); return false">twice weekly</a></li>
            <li><a href="javascript:" onclick="upMedFreq(this); return false">3 times weekly</a></li>
            <li><a href="javascript:" onclick="upMedFreq(this); return false">once monthly</a></li>
            <li><a href="javascript:" onclick="upMedFreq(this); return false">every 2 weeks</a></li>
            <li><a href="javascript:" onclick="upMedFreq(this); return false">every 10 days</a></li>
            <li><a href="javascript:" onclick="upMedFreq(this); return false">every 5 minutes</a></li>
          </ul>
          <ul style="clear:both">
            <li><a class="big" href="javascript:" onclick="upMedFreq(this); return false">as directed</a></li>
          </ul>
        </div>
      </div>
      <div id="m3" style="display:none">
        <div id="medOptTitle">Route Options</div>
        <div class="medOptions">
          <ul>
            <li><a class="big" href="javascript:" onclick="upMedRoute(this); return false">orally</a></li>
            <li><a class="big" href="javascript:" onclick="upMedRoute(this); return false">rectally</a></li>
            <li><a class="big" href="javascript:" onclick="upMedRoute(this); return false">intravaginally</a></li>
            <li><a class="big" href="javascript:" onclick="upMedRoute(this); return false">inhaled</a></li>
            <li><a class="big" href="javascript:" onclick="upMedRoute(this); return false">subcutaneously</a></li>
            <li><a class="big" href="javascript:" onclick="upMedRoute(this); return false">transdermally</a></li>
            <li><a class="big" href="javascript:" onclick="upMedRoute(this); return false">IV</a></li>
            <li><a class="big" href="javascript:" onclick="upMedRoute(this); return false">on the nails</a></li>
            <li><a class="big" href="javascript:" onclick="upMedRoute(this); return false">on the skin</a></li>
            <li><a class="big" href="javascript:" onclick="upMedRoute(this); return false">in the left eye</a></li>
            <li><a class="big" href="javascript:" onclick="upMedRoute(this); return false">in the right eye</a></li>
            <li><a class="big" href="javascript:" onclick="upMedRoute(this); return false">in both eyes</a></li>
            <li><a class="big" href="javascript:" onclick="upMedRoute(this); return false">in the left ear</a></li>
            <li><a class="big" href="javascript:" onclick="upMedRoute(this); return false">in the right ear</a></li>
            <li><a class="big" href="javascript:" onclick="upMedRoute(this); return false">in both ears</a></li>
            <li><a class="big" href="javascript:" onclick="upMedRoute(this); return false">in the left nostril</a></li>
            <li><a class="big" href="javascript:" onclick="upMedRoute(this); return false">in the right nostril</a></li>
            <li><a class="big" href="javascript:" onclick="upMedRoute(this); return false">in both nostrils</a></li>
            <li><a class="big" href="javascript:" onclick="upMedRoute(this); return false">in alternating nostrils</a></li>
            <li><a class="big" href="javascript:" onclick="upMedRoute(this); return false">IM</a></li>
          </ul>
        </div>
      </div>
      <div id="m4" style="display:none">
        <div id="medOptTitle">Length Options</div>
        <div class="medOptions">
          <ul>
            <li><a class="big" href="javascript:" onclick="upMedLength(this); return false">1 day</a></li>
            <li><a class="big" href="javascript:" onclick="upMedLength(this); return false">2 days</a></li>
            <li><a class="big" href="javascript:" onclick="upMedLength(this); return false">3 days</a></li>
            <li><a class="big" href="javascript:" onclick="upMedLength(this); return false">4 days</a></li>
            <li><a class="big" href="javascript:" onclick="upMedLength(this); return false">5 days</a></li>
            <li><a class="big" href="javascript:" onclick="upMedLength(this); return false">6 days</a></li>
            <li><a class="big" href="javascript:" onclick="upMedLength(this); return false">7 days</a></li>
            <li><a class="big" href="javascript:" onclick="upMedLength(this); return false">10 days</a></li>
            <li><a class="big" href="javascript:" onclick="upMedLength(this); return false">12 days</a></li>
            <li><a class="big" href="javascript:" onclick="upMedLength(this); return false">14 days</a></li>
            <li><a class="big" href="javascript:" onclick="upMedLength(this); return false">21 days</a></li>
            <li><a class="big" href="javascript:" onclick="upMedLength(this); return false">28 days</a></li>
            <li><a class="big" href="javascript:" onclick="upMedLength(this); return false">30 days</a></li>
            <li><a class="big" href="javascript:" onclick="upMedLength(this); return false">60 days</a></li>
            <li><a class="big" href="javascript:" onclick="upMedLength(this); return false">90 days</a></li>
            <li><a class="big" href="javascript:" onclick="upMedLength(this); return false">long-term</a></li>
          </ul>
        </div>
      </div>
      <div class="pop-cmd">
        <a id="medOK" class="cmd save" href="javascript:" onclick='medOk(); return false'>Save Changes</a>
        <span id="med-delete-span">
          <span>&nbsp;</span>
          <a id="medDelete" class="cmd delete-red" href="javascript:" onclick='medDelete(); return false'>Delete</a>
        </span>
        <span>&nbsp;</span>
        <a id="medCancel" class="cmd none" href="javascript:" onclick='medCancel(); return false'>Cancel</a>
      </div>
    </div>
  </div>
</div>
<?php
function renderLabelRadio2($id, $name, $caption, $checked = false, $style = null, $onclick = null, $lblId = null) {  // delim strings in $onclick by unescaped apostrophes only, e.g. alert('hi')
  $sty = ($style) ? "style='" . $style . "'" : "";
  $onc = ($onclick) ? "onclick=\"" . $onclick . "\"" : "";
  $ond = ";" . $onclick;
  $chk = ($checked) ? "checked" : "";
  $cls = ($checked) ? "lcheck-on" : "lcheck";
  $lid = ($lblId) ? "id='" . $lblId . "'" : "";
  echo "<input id='$id' name='$name' type='radio' $chk class='lcheck' onpropertychange='lcheckc(this)' $onc ondblclick=\"$ond\"><label unselectable='on' $lid class='$cls' onclick=\"lrcheck(this)$ond\" ondblclick=\"lrcheck(this)$ond\">$caption</label>";
}
?>