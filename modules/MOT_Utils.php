<?php

class MOT_Material {
    function __construct($id, $name, $value, $symbol, $task, $description) {
        $this->id = $id;
        $this->name = $name;
        $this->value = $value;
        $this->symbol = $symbol;
        $this->task = $task;
        $this->description = $description;
    }

    public function __toString() {
      return "Material({$this->name})";
    }

    public function toJson() {
      return [
        'id' => $this->id,
        'name' => $this->name,
        'value' => $this->value,
        'symbol' => $this->symbol,
        'task' => $this->task,
        'description' => $this->description,
      ];
    }
}

class MOT_Card {
    function __construct($id, $name, $material) {
        $this->id = $id;
        $this->name = $name;
        $this->material = $material;
    }

    public function __toString() {
      return "Card({$this->name})";
    }

    public function toJson() {
      return [
        'id' => $this->id,
        'name' => $this->name,
        'material' => $this->material->id,
      ];
    }
}
