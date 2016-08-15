<?php
require_once 'php/data/rec/sql/_TemplateRecs.php';
require_once 'php/data/rec/sql/LookupTemplates.php';
//
/**
 * Template Map
 * @author Warren Hornsby
 */
class Templates_Map {
  //
  static function get($tid, $effective = null) {
    $lu = LookupTemplates::get($tid);
    $map = Template_Map::fetch($tid, $effective);
    $map->applyLookups($lu);
    return $map;
  }
  static function getCinfo($id) {
    $rec = Cinfo::fetch($id);
    return $rec;
  }
} 
//
class Template_Map extends TemplateRec implements ReadOnly {
  //
  public $templateId;
	public $uid;
	public $name;
	public $public;
	public $desc;
	public $title;
	public $userGroupId;
  public /*Section_Map[]*/ $Sections;
  public $_startSection;
  public $_effective;
  //
  public function applyLookups($lu) {
    $this->_startSection = $lu->startSection;
    $this->applyLookupSort($lu->sort);
    $this->applyLookupToPars($lu->main, 'major');
    $this->applyLookupToPars($lu->auto, 'auto');
  }
  private function applyLookupToPars($suids, $fid) {
    if ($suids) {
      foreach ($this->Sections as &$section) {
        $puids = geta($suids, $section->uid);
        if ($puids) 
          foreach ($section->Pars as &$par) 
            $par->$fid = in_array($par->uid, $puids);
      }
    }
  }
  private function applyLookupSort($suids) {
    if ($suids) {
      foreach ($this->Sections as &$section) {
        $sortOrder = get($suids, $section->uid);
        if ($sortOrder)
          $section->sortOrder = $sortOrder;
      }
      $this->Sections = Rec::sort($this->Sections, new RecSort('sortOrder'));
    }
  }
  //
  static function fetch($tid, $effective = null) {
    global $login;
    $rec = parent::fetch($tid);
    $rec->Sections = Section_Map::fetchAll($tid, $effective);
    $rec->_effective = $effective;
    return $rec;
  }
  //
  protected function authenticateUserGroupId($ugid, $forReadOnly = false) {
    // ugid not important here
  }
}
//
class Section_Map extends SectionRec implements ReadOnly {
  //
	public $sectionId;
	public $templateId;
	public $uid;
	public $name;
	public $sortOrder;
	public $title;
	public /*Par_Map[]*/ $Pars;
	//
  public function getJsonFilters() {
    return array(
    	'templateId' => JsonFilter::omit());
  }
	public function loadPars($effective = null) {
	  $this->Pars = Par_Map::fetchAll($this->sectionId, $effective);
	}
  //
	static function fetchAll($tid, $effective = null) {
	  $c = self::asCriteria($tid);
	  $recs = SqlRec::fetchAllBy($c, new RecSort('sortOrder'), 2000);
	  $a = array();
	  foreach ($recs as &$rec) {
	    $rec->loadPars($effective);
	    if (! empty($rec->Pars))
	      $a[] = $rec;
	  }
	  return $a;
	}
	static function asCriteria($tid) {
	  $c = new self();
	  $c->templateId = $tid;
	  $c->uid = CriteriaValues::_and(CriteriaValue::notStartsWith(self::UID1_HIDE), CriteriaValue::notStartsWith(self::UID1_HIDE_EMPTY));
	  global $login;
	  if (($login->userGroupId == 3011) && $tid == 1) /*customizations*/ {
      $c->sectionId = CriteriaValue::notEqualsNumeric(19/*wcc*/);
    }
    return $c;
	}
}
//
class Par_Map extends ParRec implements ReadOnly {
  //
  public $parId;
	public $sectionId;
	public $uid;
	public $major;
	public $desc;
	public $injectOnly;
	public $dateEffective;
	public $current;
	//
  public function getJsonFilters() {
    return array(
    	'major' => JsonFilter::boolean(),
    	'sectionId' => JsonFilter::omit(),
    	'injectOnly' => JsonFilter::omit(),
      'dateEffective' => JsonFilter::omit(),
      'current' => JsonFilter::omit());
  }
  public function toJsonObject(&$o) {
    if (substr($this->uid, 0, 1) == '+')
      $o->cloneable = true;
  }
  //
	static function fetchAll($sid, $effective = null) {
	  if ($effective == null) 
	    return self::fetchAllCurrent($sid);
	  $c = new self();
	  $c->sectionId = $sid;
	  $c->injectOnly = CriteriaValue::notEquals('1');
	  $c->uid = CriteriaValue::notStartsWith('=');
	  $c->dateEffective = CriteriaValue::lessThanOrEquals($effective);
	  $self = new self();
	  $self->sectionId = CriteriaValue::equalsNumeric('T0.section_id');
	  $self->uid = CriteriaValue::equalsNumeric('T0.uid');
	  $self->dateEffective = CriteriaValues::_and(CriteriaValue::greaterThanNumeric('T0.date_effective'), CriteriaValue::lessThanOrEquals($effective));
	  $c->JoinSelf = CriteriaJoin::notExists($self)->onCriteriaOnly();  
	  $c->Cinfos = Cinfo_Map::asOptionalJoin();
	  $c = static::applyCustoms($c, $sid);
	  return SqlRec::fetchAllBy($c, new RecSort('desc'), 2000);
	}
	static function fetchAllCurrent($sid) {
	  $c = new self();
	  $c->sectionId = $sid;
	  $c->injectOnly = CriteriaValue::notEquals('1'); 
	  $c->current = true;
	  $c->Cinfos = Cinfo_Map::asOptionalJoin();
	  return SqlRec::fetchAllBy($c, new RecSort('desc'), 2000);
	}
	protected static function applyCustoms($c, $sid) {
	  global $login;
	  switch ($login->userGroupId) {
      case 3011/*QuestCare Matrix*/:
        if ($sid == 36/*PMHX*/)  
          $c->uid = CriteriaValues::_and(
            CriteriaValue::notStartsWith('='), 
            CriteriaValue::notEquals('pastObHx'));
        break;
    }
	  return $c;
	}
}
//
class Cinfo_Map extends CinfoRec implements ReadOnly {
  //
  public $cinfoId;
  public $parId;
  public $type;
  public $name;
  //
  public function getJsonFilters() {
    return array(
    	'parId' => JsonFilter::omit());
  }
  //
  static function asOptionalJoin() {
    $c = new self();
    return CriteriaJoin::optionalAsArray($c);
  }
}
class Cinfo extends CinfoRec implements ReadOnly {
  //
  public $cinfoId;
  public $parId;
  public $type;
  public $name;
  public $dateCreated;
  public $text;
}
