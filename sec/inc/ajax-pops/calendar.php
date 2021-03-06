<div id="pop-cal" class="pop" onmousedown="event.cancelBubble = true">
  <div id="pop-cal-cap" class="pop-cap">
    <div id="pop-cal-cap-text">
      Set Date
    </div>
    <a href="javascript:closeCalendar()" class="pop-close"></a>
  </div>
  <div class="pop-content">
    <table class="calBody" cellpadding="0" cellspacing="0">
      <tr>
        <td style="text-align:center">
          <table border="0" width="200px" cellspacing="1" class="calBody" id="tblCalendar" onmouseover="onMouseOver()" onmouseout="onMouseOut()" onclick="onClick()">
            <tr>
              <td align="middle" colspan="7">
                <table class="calBody" border="0" cellpadding="0" cellspacing="0" width="100%">
                  <tr>
                    <td width="3"></td>
                    <td>
                      <img alt="Previous Month" class="calLink" src="img/nav-prev.jpg" onclick="prevMonth()">
                    </td>
                    <td align="middle" id="tdTitle" class="calTitle" width="100%">
                    </td>
                    <td align="right">
                      <img alt="Next Month" class="calLink" src="img/nav-next.jpg" onclick="nextMonth()">
                    </td>
                    <td width="3"></td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr>
              <td class="calDayTitle">S</td>
              <td class="calDayTitle">M</td>
              <td class="calDayTitle">T</td>
              <td class="calDayTitle">W</td>
              <td class="calDayTitle">T</td>
              <td class="calDayTitle">F</td>
              <td class="calDayTitle">S</td>
            </tr>
            <tr>
              <td class="calOffDay">&nbsp;</td>
              <td class="calOffDay">&nbsp;</td>
              <td class="calDay">1</td>
              <td class="calDay">2</td>
              <td class="calDay">3</td>
              <td class="calDay">4</td>
              <td class="calDay">5</td>
            </tr>
            <tr>
              <td class="calDay">6</td>
              <td class="calDay">7</td>
              <td class="calDay">8</td>
              <td class="calDay">9</td>
              <td class="calDay">10</td>
              <td class="calDay">11</td>
              <td class="calDay">12</td>
            </tr>
            <tr>
              <td class="calDay">13</td>
              <td class="calDay">14</td>
              <td class="calDay">15</td>
              <td class="calDay">16</td>
              <td class="calDay">17</td>
              <td class="calDay">18</td>
              <td class="calDay">19</td>
            </tr>
            <tr>
              <td class="calDay">20</td>
              <td class="calDay">21</td>
              <td class="calDay">22</td>
              <td class="calDay">23</td>
              <td class="calDay">24</td>
              <td class="calDay">25</td>
              <td class="calDay">26</td>
            </tr>
            <tr>
              <td class="calDay">27</td>
              <td class="calDay">28</td>
              <td class="calDay">29</td>
              <td class="calDay">30</td>
              <td class="calDay">31</td>
              <td class="calOffDay">&nbsp;</td>
              <td class="calOffDay">&nbsp;</td>
            </tr>
            <tr id="trExtraRow">
              <td class="calOffDay">&nbsp;</td>
              <td class="calOffDay"></td>
              <td class="calOffDay"></td>
              <td class="calOffDay"></td>
              <td class="calOffDay"></td>
              <td class="calOffDay"></td>
              <td class="calOffDay"></td>
            </tr>
          </table>
        </td>
      </tr>
      <tr>
        <td align="middle">
          <table border="0" cellspacing="0">
            <tr>
              <td align="right">
                <select size="1" id="cboMonth" onchange="setDateByCombos()">
               </select>
              </td>
              <td width="5"></td>
              <td>
                <select size="1" id="cboYear" onchange="setDateByCombos()">
                </select>
              </td>
            </tr>
          </table>
        </td>
      </tr>
      <tr>
        <td>
          <table width="100%" border="0" cellspacing="0">
            <tr>
              <td style="text-align:center;padding:2px">
                <a id="calToday" href="javascript:setToday()" title="Current Month">Today</a>
              </td>
            </tr>
          </table>
          <div class="pop-cmd">
            <a href="javascript:closeOverlayPop()" class="cmd none">Cancel</a>
          </div>
        </td>
      </tr>
    </table>
  </div>
</div>

<div id="pop-clock" class="pop" onmousedown="event.cancelBubble = true">
  <div id="pop-clock-cap" class="pop-cap">
    <div id="pop-clock-cap-text">
      Set Time
    </div>
    <a href="javascript:closeOverlayPop()" class="pop-close"></a>
  </div>
  <div class="pop-content" style="text-align:center">
    <ul class="entry">
      <li> 
        <select id="clkHour" size="12">
          <option style='background-color:#ffffe0' value="06AM">6a</option>
          <option style='background-color:#ffffe0' value="07AM">7</option>
          <option style='background-color:#ffffc0' value="08AM">8</option>
          <option style='background-color:#ffffc0' value="09AM">9</option>
          <option style='background-color:#ffff80' value="10AM">10</option>
          <option style='background-color:#ffff80' value="11AM">11</option>
          <option style='background-color:#ffff00' value="12PM">12p</option>
          <option style='background-color:#ffff80' value="01PM">1</option>
          <option style='background-color:#ffff80' value="02PM">2</option>
          <option style='background-color:#ffffc0' value="03PM">3</option>
          <option style='background-color:#ffffc0' value="04PM">4</option>
          <option style='background-color:#ffffe0' value="05PM">5</option>
          <option style='background-color:#ffffe0' value="06PM">6p</option>
          <option style='background-color:#f0f0f0' value="07PM">7</option>
          <option style='background-color:#e7e7e7' value="08PM">8</option>
          <option style='background-color:#e0e0e0' value="09PM">9</option>
          <option style='background-color:#d7d7d7' value="10PM">10</option>
          <option style='background-color:#c7c7c7' value="11PM">11</option>
          <option style='background-color:#c0c0c0' value="12AM">12a</option>
          <option style='background-color:#c7c7c7' value="01AM">1</option>
          <option style='background-color:#d7d7d7' value="02AM">2</option>
          <option style='background-color:#e0e0e0' value="03AM">3</option>
          <option style='background-color:#e7e7e7' value="04AM">4</option>
          <option style='background-color:#f0f0f0' value="05AM">5</option>
        </select>
        <select id="clkMin" size="12">
          <option value="00">00</option>
          <option style='color:#707070' value="05">05</option>
          <option style='color:#707070' value="10">10</option>
          <option value="15">15</option>
          <option style='color:#707070' value="20">20</option>
          <option style='color:#707070' value="25">25</option>
          <option value="30">30</option>
          <option style='color:#707070' value="35">35</option>
          <option style='color:#707070' value="40">40</option>
          <option value="45">45</option>
          <option style='color:#707070' value="50">50</option>
          <option style='color:#707070' value="55">55</option>
        </select>
      </li>
    </ul>
    <div class="pop-cmd">
      <a href="javascript:saveClock()" class="cmd save">&nbsp;&nbsp;OK&nbsp;&nbsp;</a>
      <a href="javascript:closeOverlayPop()" class="cmd none">Cancel</a>
    </div>
  </div>
</div>