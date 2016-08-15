<?php
require_once "php/forms/Form.php";

class ParForm extends Form {

	// Enterable fields
	public $id;	
	public $templateId;
	public $sectionId;
	public $uid;
	public $major;
	public $sortOrder;
	public $desc;
	public $noBreak;
	public $injectOnly;
	public $inTable;
	public $inType;
	public $inCond;
		
	// Lists
	public $questions;
	
	// Combo lists
	public $sortOrders = array();
  public $dataTables = array();
	public $inTypes = array();
  
	public function __construct() {
	  $this->dataTables = CommonCombos::inDataTables();
	  $this->inTypes = CommonCombos::inDataTypes();
	}
	
	public function validate() {
		$this->resetValidationException();
		$this->addRequired("uid", "UID", $this->uid);
		$this->throwValidationException();
	}
	
	public function buildPar() {
		return new Par($this->id, $this->sectionId, $this->uid, $this->major, $this->sortOrder, $this->desc, $this->noBreak, $this->injectOnly, null, $this->inTable, $this->inType, $this->inCond);
	}

	public function setFromDatabase($dto) {
		$this->id = $dto->id;
		$this->sectionId = $dto->sectionId;
		$this->uid = $dto->uid;
		$this->major = $dto->major;
		$this->sortOrder = $dto->sortOrder;
		$this->desc = $dto->desc;
		$this->noBreak = $dto->noBreak;
		$this->injectOnly = $dto->injectOnly;
		$this->questions = $dto->questions;
		$this->inTable = $dto->inTable;
		$this->inType = $dto->inType;
		$this->inCond = $dto->inCond;
	}

	public function setFromPost() {
		$this->id = $_POST["id"];
		$this->templateId = $_POST["templateId"];
		$this->sectionId = $_POST["sectionId"];
		$this->uid = $_POST["uid"];
		$this->major = $this->toBool(isset($_POST["major"]) ? $_POST["major"] : null);
		$this->sortOrder = $_POST["sortOrder"];
		$this->desc = $_POST["desc"];
		$this->noBreak = $this->toBool(isset($_POST["noBreak"]) ? $_POST["noBreak"] : null);
		$this->injectOnly = $this->toBool(isset($_POST["injectOnly"]) ? $_POST["injectOnly"] : null);
		$this->questions = null;
		$this->inTable = $_POST["inTable"];
		$this->inType = $_POST["inType"];
		$this->inCond = $_POST["inCond"];
	}
	
		public function formatText($question) {
		$t = $question->bt;
		if (! isNull($question->defix)) {
			if (isBlank($question->btms)) {
				$t .= " ___";
			} else {
				$t .= " " . $question->btms . " ___";
			}
			if (! isBlank($question->atms)) {
				$t .= " " . $question->atms;
			}
			if (! isBlank($question->btmu)) {
				$t .= " " . $question->btmu . " ___";
			}
			if (! isBlank($question->atmu)) {
				$t .= " " . $question->atmu;
			}
		}
		if (! isBlank($question->at)) {
			$t .= " " . $question->at;	
		}
		return $t;
	}
}
?>