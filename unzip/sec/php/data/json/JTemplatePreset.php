<?php
require_once "php/data/json/_util.php";
require_once "php/data/db/TemplatePreset.php";

class JTemplatePreset extends TemplatePreset {

  public $templateName;
  public $createdBy;
  public $updatedBy;
  
  // Optional child
  public $template;
  public $map;
  
  public function __construct($id, $userGroupId, $name, $templateId, $dateCreated, $dateUpdated, $templateName, $actions, $createdBy, $updatedBy) {
    $this->id = $id;
    $this->userGroupId = $userGroupId;
    $this->name = $name;
    $this->templateId = $templateId;
    $this->dateCreated = $dateCreated;
    $this->dateUpdated = $dateUpdated;
    $this->templateName = $templateName; 
    $this->actions = $actions;
    $this->createdBy = $createdBy;
    $this->updatedBy = $updatedBy;
  }
  
  public function out() {
    return cb(qq("id", $this->id) 
        . C . qq("userGroupId", $this->userGroupId) 
        . C . qq("name", $this->name) 
        . C . qq("templateId", $this->templateId) 
        . C . qq("dateCreated", formatTimestamp($this->dateCreated)) 
        . C . qq("dateUpdated", formatTimestamp($this->dateUpdated))
        . C . qq("noteDate", $this->dateCreated) 
        . C . qq("templateName", $this->templateName) 
        . C . qqo("actions", $this->actions)
        . C . qq("createdBy", $this->createdBy) 
        . C . qq("updatedBy", $this->updatedBy) 
        . C . qq("noteDate", $this->dateCreated)
        . C . qqj("template", $this->template)
        . C . qqo("map", jsonencode($this->map))
        );
  }
}
?>