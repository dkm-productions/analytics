/**
 * Lab
 */
Lab = Object.Rec.extend({
  /*
   labId
   uid
   name
   address
   contact
   */
  //
})
/**
 * Hl7Inbox
 */
Hl7Inbox = Object.Rec.extend({
  /*
   hl7InboxId
   userGroupId
   labId
   msgType 
   source
   filename
   dateReceived
   patientName
   cid
   reconciledBy
   data
   //
   Lab
   */
  onload:function() {
    this.setr('Lab', Lab);
  },
  uiSource:function() {
    var s = this.source;
    if (this.filename)
      s += ' - ' + this.filename;
    return s;
  },
  //
  ajax:function() {
    var self = this;
    return {
      remove:function(callback) {
        Ajax.Labs.removeInbox(self.hl7InboxId, Html.Window, callback);
      },
      setClient:function(cid, callback) {
        Ajax.Labs.setClient(cid, self.hl7InboxId, Html.Window, callback);
      }
    }
  }
})
Hl7Inboxes = Object.RecArray.of(Hl7Inbox, {
  //
  ajax:function(worker) {
    return {
      fetch:function(callback) { 
        Ajax.Labs.getInboxes(null, callback);
      }
    }
  }
})
/**
 * LabRecon
 */
LabRecon = Object.Rec.extend({
  /*
   HL7Inbox Inbox
   ORUMessage Msg
   */
  onload:function() {
    this.setr('Msg', OruMessage);
    this.setr('Inbox', Hl7Inbox);
  },
  getClient:function() {
    return this.Msg.getClient();
  },
  //
  ajax:function() {
    var self = this;
    var inbox = this.Inbox;
    return {
      save:function(msg, callback) {  // callback(LabRecon) if errors still exist
        Ajax.Labs.saveRecon(self.Inbox.hl7InboxId, msg, Html.Window, callback);
      },
      //
      fetch:function(inbox, callback) {
        Ajax.Labs.getRecon(inbox.hl7InboxId, Html.Window, callback);
      }
    }
  }
})
/**
 * OruMessage & HL7 Segments
 */
Hl7Rec = Object.Rec.extend({
  //
  asList:function() {
    var ul = Html.Ul.create('seg ' + this.segId);
    var li = ul.li();
    Html.Div.create('seg').setText(this._getMyLabel().toUpperCase()).into(li);
    li.add(this._asFidsList());
    this._getSegments().each(function(seg) {
      seg && seg.asList().into(li);
    })
    return ul;
  },
  //
  _getMyLabel:function() {
    return 'Hl7 Rec';
  },
  _getSegments:function() {
    return [];
  },
  _getLabel:function(fid) {
    return fid.splitCamel();
  },
  _getValue:function(fid) {
    return this[fid];
  },
  _isFid:function(fid) {
    if (! this.hasOwnProperty(fid)) 
      return;
    if (fid.beginsWith('_') || fid.endsWith('_'))
      return;
    if (fid == 'segId')
      return;
    var c1 = fid.substr(0, 1);
    if (c1 != c1.toUpperCase())
      return true;
  },
  _asFidsList:function() {
    var ul;
    for (var fid in this) {
      if (this._isFid(fid)) {
        ul = ul || Html.Ul.create('fids');
        this._addFid(ul, this._getLabel(fid), this._getValue(fid));
      }
    }
    return ul;
  },
  _addFid:function(ul, label, value) {
    if (label && value) {
      var li = ul.li();
      if (Object.is(value)) {
        if (! value.asList) 
          value = Array.is(value) ? Hl7Values.revive(value) : Hl7Value.revive(value);
        value = value.asList();
      }
      if (value) {
        if (String.is(value)) { 
          Html.Label.create(null, label.toUpperCase() + ': ').into(li);
          Html.Span.create(null, value).into(li);
        } else {
          Html.Label.create('b', label.toUpperCase()).into(li);
          value.into(li);
        }
      }
    }
  }
})
Hl7RecArray = Object.RecArray.extend({
  //
  asList:function() {
    var uls = Html.Array();
    this.each(function(rec) {
      uls.push(rec.asList());
    })
    return uls;
  }
})
//
OruMessage = Hl7Rec.extend({
  //
  onload:function() {
    this.setr('Header', Msh);
    this.setr('PatientId', Pid);
    this._errorUi = null;
  },
  getClient:function() {
    return this.PatientId.Client_;
  },
  getCommonOrder:function() {
    return Array.is(this.PatientId.CommonOrder) ? this.PatientId.CommonOrder[0] : this.PatientId.CommonOrder;
  },
  getObsRequests:function() {
    return this.PatientId.ObsRequest;  
  },
  getObsRequest:function(seq) {
    return this.getObsRequests()[seq - 1];
  },
  //
  asList:function() {
    var div = Html.Div.create('hl7');
    this._getSegments().each(function(seg) {
      seg && seg.asList().into(div);
    })
    return div;
  },
  _getMyLabel:function() {
    return '';
  },
  _getSegments:function() {
    return [this.Header, this.PatientId];
  }
})
Msh = Hl7Rec.extend({
  onload:function() {
    this.setr('sendApp', HD);
    this.setr('sendFacility', HD);
    this.setr('rcvApp', HD);
    this.setr('rcvFacility', HD);
    this.setr('timestamp', TS);
  },
  //
  _getMyLabel:function() {
    return 'Header';
  },
  _getLabel:function(fid) {
    switch (fid) {
      case 'sendApp':
        return 'Sending App';
      case 'sendFacility':
        return 'Sending Facility';
      case 'rcvApp':
        return 'Receiving App';
      case 'rcvFacility':
        return 'Receiving Facility';
      case 'msgControlId':
        return 'Control ID';
      case 'timestamp':
        return 'Timestamp';
    }
  }
})
Pid = Hl7Rec.extend({
  onload:function() {
    this.setr('CommonOrder', Orc);
    this.setr('ObsRequest', Obrs);
    this.setr('patientId', CE);
    this.setr('address', XAD);
    this.setr('phoneHome', XTN);
    this.setr('phoneWork', XTN);
    this.setr('account', CX);
    this.setr('birthDate', TS);
  },
  uiPatientId:function() {
    var id = this.patientId && this.patientId.getId();
    if (id == null && this.patientIdList && this.patientIdList.length)
      id = this.patientIdList[0] && this.patientIdList[0].id;
    if (id == null)
      id = this.NA;
    return id;
  },
  uiRace:function() {
    return this.race && this.race.text;
  },
  uiGender:function() {
    if (this.gender) {
      switch (this.gender.toUpperCase()) {
        case 'M':
          return 'Male';
        case 'F':
          return 'Female';
        default:
          return this.gender;
      }
    }  
  },
  uiAddress:function() {
    var html = this.address && this.address.asHtml();
    return html || this.NA;
  },
  //
  _getMyLabel:function() {
    return 'Patient';
  },
  _getSegments:function() {
    return Array.arrayify(this.CommonOrder).append(this.ObsRequest);
  },
  _getLabel:function(fid) {
    switch (fid) {
      case 'seq':
        return null;
      default:
        return fid.splitCamel();
    }
  },
  NA:''
})
Orc = Hl7Rec.extend({
  //
  _getMyLabel:function() {
    return 'Order';
  }
}) 
Obr = Hl7Rec.extend({
  onload:function() {
    this.setr('Observation', Obxs);
    this.setr('serviceId', CE);
    this.setr('reqDateTime', TS);
    this.setr('obsDateTime', TS);
    this.setr('obsEndDateTime', TS);
    this.setr('danger', CE);
    this.setr('specimenReceived', TS);
    this.setr('qtyTiming', TS);
    this.setr('reason', CE);
    this.setr('scheduled', TS);
    this.setr('resultRpt', TS);
    this.setr('Comment', Ntes); 
    this.setr('TimingQty', Tq);
    this.setr('Specimen', Spm);
  },
  getObservations:function() {
    return this.Observation;
  },
  getObservation:function(seq) {
    return this.Observation[seq - 1];
  },
  uiServiceId:function() {
    return String.denull(this.serviceId.text || this.serviceId.altText);
  },
  uiComments:function() {
    return this.Comment && this.Comment.ui();
  },
  //
  _getMyLabel:function() {
    return 'Request';
  },
  _getSegments:function() {
    return Array.arrayify(this.Comment).append(this.TimingQty).append(this.Observation).append(this.Specimen);
  }
})
Obrs = Hl7RecArray.of(Obr, {
  //
})
Obx = Hl7Rec.extend({
  onload:function() {
    this.setr('Comment', Ntes); 
    this.setr('obsId', CE);
    this.setr('timestamp', TS);
    this.setr('producerId', CE);
    this.setr('method', CE);
  },
  uiValue:function() {
    var s = String.from(this.value).replace(/\\.br\\/g,'<br>')
    if (this.units && this.units.id)
      s += ' ' + this.units.id;
    if (s.contains('<br>'))
      s = '<pre>' + s + '</pre>'; /*Doesn't work because the proc result value column is too small*/
    return s;
  },
  uiAbnormal:function() {
    return (this.abnormal) ? 'Abnormal: ' + this.abnormal : '';
  },
  uiComments:function() {
    return this.Comment && this.Comment.ui();
  },
  //
  _getMyLabel:function() {
    return 'Observation';
  },
  _getSegments:function() {
    return Array.arrayify(this.Comment);
  },
  _getLabel:function(fid) {
    switch (fid) {
      case 'valueType':
        return null;
      default:
        return fid.splitCamel();
    }
  }
})
Obxs = Hl7RecArray.of(Obx, {
  //
})
Spm = Hl7Rec.extend({
  _getMyLabel:function() {
    return 'Specimen';
  }  
})
Tq = Hl7Rec.extend({
  //
  onload:function() {
    this.setr('start', TS);
    this.setr('end', TS);
  },
  _getMyLabel:function() {
    return 'Timing/Quantity';
  }
})
Nte = Hl7Rec.extend({
  //
  _getMyLabel:function() {
    return 'Comment';
  }
})
Ntes = Hl7RecArray.of(Nte, {
  ui:function() {
    var a = [];
    this.forEach(function(rec) {
      a.push(rec.comment);
    })
    return '<pre>' + a.join('<br>') + '</pre>'; 
  },
  asList:function() {
    var ul = Html.Ul.create('fids');
    var li = ul.li();
    Html.Label.create(null, 'NOTES: ').into(li);
    Html.Div.create('notes').html(this.ui()).into(li);
    return ul;
  }
})
/**
 * Hl7 Values
 */
Hl7Value = Hl7Rec.extend({
  asList:function() {
    return this._asFidsList();
  }
})
Hl7Values = Hl7RecArray.of(Hl7Value, {
  //
})
//
CE = Hl7Value.extend({
  getId:function() {
    return this.id || this.altId;
  },
  //
  asList:function() {
    var h = [this.getId()];
    if (this.text)
      h.push('(' + this.text + ')');
    return h.join(' ');
  }
})
XAD = Hl7Value.extend({
  asHtml:function() {
    if (this.addr1) {
      var h = [this.addr1];
      if (this.addr2)
        h.push(this.addr2);
      if (this.city) {
        var csz = this.city;
        if (this.state)
          csz += ', ' + this.state;
        if (this.zip)
          csz += ' ' + this.zip;
        h.push(csz);
      }
      return h.join('<br/>');      
    }
  }
})
XTN = Hl7Value.extend({
  //
})
CX = Hl7Value.extend({
  //
})
TS = Hl7Value.extend({
  asList:function() {
    return this._time || this._date || this._asFidsList();
  }
})
HD = Hl7Value.extend({
  asList:function() {
    return this.namespaceId;
  }
})
/**
 * ClientRecon
 */
ClientRecon = Object.Rec.extend({
  onload:function() {
    this.setr('Address_Home', XAD);
  },
  uiAddress:function() {
    var html = this.Address_Home.asHtml();
    return html || 'None on File';
  }
})
