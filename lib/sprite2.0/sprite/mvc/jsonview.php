<?php
namespace sprite\mvc;

class JsonView implements iView {

	private $_obj;

	public function __construct($array_or_obj) {
		$this->_obj = $array_or_obj;
	}

	public function render() {
		header('Content-type: text/json');
		echo json_encode($this->_obj);
	}
}