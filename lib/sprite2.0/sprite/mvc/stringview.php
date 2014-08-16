<?php
namespace sprite\mvc;

class StringView implements iView {

	private $_str;

	public function __construct($str) {
		$this->_str = $str;
	}

	public function render() {
		header('Content-type: text/html');
		echo $this->_str;
	}
}