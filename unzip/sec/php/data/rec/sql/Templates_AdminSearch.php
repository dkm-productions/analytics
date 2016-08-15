<?php
require_once 'php/data/rec/sql/_TemplateRecs.php';
//
/**
 * Template Admin Search
 * @author Warren Hornsby
 */
class Templates_AdminSearch {
  //
  public function search($text) {
    $qs = Question_Search::search($text);
    $os = Option_Search::search($text);
    $recs = array();
    foreach ($qs as $q) 
      $recs[$q->questionId] = $q;
    foreach ($os as $o) 
      $recs[$o->Question->questionId] = $o->Question;
    return Rec::sort($recs, new RecSort('Par.Section.Template.uid', 'Par.Section.uid', 'Par.uid', 'uid'));
  }
} 
//
class Option_Search extends OptionRec implements ReadOnly {
  //
  public $optionId;
  public $questionId;
  public $uid;
  public $desc;
  public $text;
  public /*Question_Search*/ $Question;
  //
  static function search($text) {
    $c = new static();
    $c->text = CriteriaValue::sql(static::asCriterias($text));
    $c->Question = Question_Search::asJoin();
    return static::fetchAllBy($c);
  }
  private static function asCriterias($text) {
    return implode(' OR ', array(
      static::asCriteria('T0.uid', $text),
      static::asCriteria('text', $text)));
  }
  private static function asCriteria($fid, $text) {
    return "$fid LIKE '%$text%'";
  }
}
class Question_Search extends QuestionRec implements ReadOnly {
  //
  public $questionId;
  public $parId;
  public $uid;
  public $desc;
  public $bt;
  public $at;
  public $btms;
  public $atms;
  public $btmu;
  public $atmu;
  public /*Par_Search*/ $Par;
  //
  public function asLink() {
    $href = $this->getUrl();
    $text = $this->getFullUid();
    return "<a target='_blank' href='$href'>$text</a>";
  }
  protected function getUrl() {
    $qid = $this->questionId;
    $sid = $this->Par->sectionId;
    $tid = $this->Par->Section->templateId;
    return "adminQuestion.php?id=$qid&sid=$sid&tid=$tid&" . rand(0, 999999);
  }
  protected function getFullUid() {
    $quid = $this->uid;
    $puid = $this->Par->uid;
    $suid = $this->Par->Section->uid;
    $tuid = $this->Par->Section->Template->uid;
    return "$tuid.$suid.$puid.$quid";
  }
  //
  static function search($text) {
    $c = new static();
    $c->bt = CriteriaValue::sql(static::asCriterias($text));
    $c->Par = Par_Search::asJoin();
    return static::fetchAllBy($c);
  }
  static function asJoin() {
    $c = new static();
    $c->Par = Par_Search::asJoin();
    return $c;
  }
  private static function asCriterias($text) {
    return implode(' OR ', array(
      static::asCriteria('bt', $text),
      static::asCriteria('at', $text),
      static::asCriteria('btms', $text),
      static::asCriteria('atms', $text),
      static::asCriteria('btmu', $text),
      static::asCriteria('atmu', $text)));
  }
  private static function asCriteria($fid, $text) {
    return "$fid LIKE '%$text%'";
  }
}
class Par_Search extends ParRec implements ReadOnly {
  //
  public $parId;
	public $sectionId;
	public $uid;
	public $desc;
	public /*Section_Search*/ $Section;
	//
	static function asJoin() {
	  $c = new static();
	  $c->Section = Section_Search::asJoin();
	  return $c;
	}
}
class Section_Search extends SectionRec implements ReadOnly {
  //
	public $sectionId;
	public $templateId;
	public $uid;
	public $name;
	public /*Template_Search*/ $Template;
	//
	static function asJoin() {
	  $c = new static();
	  $c->Template = new Template_Search();
	  return $c;
	}
}
class Template_Search extends TemplateRec implements ReadOnly {
  //
	public $templateId;
	public $uid;
	public $name;
}
