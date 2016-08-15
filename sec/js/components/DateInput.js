/**
 * Date/Time Input
 * Requires: Pop.js, Calendar.css
 */
/*
 * Date Input
 * @arg string text: '23-Nov-2011' default date value (optional)
 * @arg <e> container: to append this after construction (optional)
 * @callback onset(text)
 * 
 * Builds component as:
 *   <span dateInput=this>
 *     <input type='text' /><a class='dical' />
 *   </span> 
 */
function DateInput(text, container, onset) {
  this.span = Html.Span.create();
  this.span.dateInput = this;
  this.textbox = Html.InputText.create().setSize(8).into(this.span);
  if (onset) { 
    var self = this;
    this.onset = onset;
    this.textbox.onchange = function(){self.call_onset.call(self)};
  }
  this.a = Html.Anchor.create('dical', null, this.pop.bind(this)).into(this.span);
  this.setText(text);
  if (container) 
    this.appendTo(container);
}
//
DateInput.prototype = {
  span:null,
  textbox:null,
  a:null,
  /*
   * @arg string text 
   */
  setText:function(text) {
    var dv = new DateValue(text);
    this.textbox.value = dv.toString(null, true);
    if (this.onset)
      this.call_onset();
  },
  /*
   * @return string '23-Nov-2011'
   */
  getText:function() {
    var dv = new DateValue(this.textbox.value);
    var value = dv.toString(null, true);
    this.textbox.value = value;
    return value;
  },
  /*
   * Set focus to textbox
   */
  focus:function() {
    try {
      this.textbox.focus();
      this.textbox.select();
    } catch (e) {}
  },
  /*
   * Append this to container
   */
  appendTo:function(container) {
    container.appendChild(this.span);
  },
  //
  pop:function() {
    var self = this;
    Pop.Calendar.show(self.getText(),
      function(text) {
        if (text)
          self.setText(text);
        self.focus();
      });
  },
  call_onset:function() {
    var text = this.getText();
    this.onset(String.nullify(text));
  }
}
//
/*
 * DateTime Input
 * @arg string text: '23-Nov-2011 04:30PM' default datetime value (optional)
 * @arg <e> container: to append this after construction (optional)
 * @callback onchange(text)
 * 
 * Builds component as:
 *   <span dateTimeInput=this>
 *     <span dateInput>
 *       <input type='text' /><a class='dical' />
 *     </span>
 *     <input type='text' /><a class='diclock' />
 *   </span> 
 */
function DateTimeInput(text, container, onchange) {
  this.span = Html.Span.create();
  this.span.dateTimeInput = this;
  this.di = new DateInput(null, this.span, onchange); 
  this.textbox = Html.InputText.create().setSize(5).into(this.span);
  this.a = Html.Anchor.create('diclock', null, this.pop.bind(this)).into(this.span);
  this.setText(text);
  if (container) 
    this.appendTo(container);
  //
  function splitText(text) {  // return ['23-Nov-2011','04:30PM']
    if (text == null) {
      return [null, null];
    } else {
      var a = text.split(' ');
      if (a.length == 1)
        return [text, null];
      else 
        return [a[0], a[1]];
    }
  }
}
DateTimeInput.prototype = {
  span:null,
  di:null,
  textbox:null,
  a:null,
  /*
   * @arg string text e.g. '04-Nov-2011 04:30PM', '04-Nov-2011'
   */
  setText:function(text) {
    text = String.trim(String.denull(text));
    var dt = text.split(' ');
    var date = dt[0];
    var time = (dt.length > 1) ? dt[1] : ''; 
    this.di.setText(date);
    this.setValue(new TimeValue(time));
  },
  hideDateInput:function() {
    this.di.span.style.display = 'none';
    this.textbox.className = 'ml5';
  },
  /*
   * @arg string text e.g. '04:30PM'
   */
  setTimeText:function(text) {
    var tv = new TimeValue(text);
    this.textbox.value = tv.toString();
    if (this.textbox.value != '' && this.di.getText() == '') {
      dv = DateValue.now();
      this.di.setText(dv.toString(null, true));
    }
  },
  /*
   * @arg TimeValue timeValue
   */
  setValue:function(timeValue) {
    this.textbox.value = timeValue.toString();
    if (this.textbox.value != '' && this.di.getText() == '') {
      dv = DateValue.now();
      this.di.setText(dv.toString(null, true));
    }
  },
  /*
   * @return string, e.g. '04-Nov-2011 04:30PM' or '04-Nov-2011' 
   */
  getText:function() {
    return String.trim(this.di.getText() + ' ' + this.getTimeText());
  },
  /*
   * @return string 
   */
  getTimeText:function() {
    return new TimeValue(this.textbox.value).toString();
  },
  /*
   * Set focus to textbox
   */
  focus:function() {
    this.textbox.focus();
    this.textbox.select();
  },
  /*
   * Append this to container
   */
  appendTo:function(container) {
    container.appendChild(this.span);
  },
  //
  pop:function() {
    var self = this;
    Pop.Clock.show(self.getTimeText(), false, 
      function(text) {
        if (text)
          self.setTimeText(text);
        self.focus();
      });
  }
}
