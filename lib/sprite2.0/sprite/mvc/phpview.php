<?php
namespace sprite\mvc;

class PhpView implements iView {
	
	private $_response;
	private $_template;
	private $_layout;
	
	public function __construct($response, $template, $layout=NULL) {
		$this->_response = $response;
		$this->_template = $template;
		$this->_layout = $layout;
	}
	
		
	public function render() {
		$template = $this->_template;
		 
		$template_path = '../template'; //项目基准路径
		
		$control = strtolower($this->_response->_controller); 
		$action = strtolower($this->_response->_action);
		
		if (empty($template)) {
			  $template =  "$template_path/$control/$action.php";
		} else {
			  $template =  "$template_path/$control/$template.php";
		}
		
		
		$layout = NULL;
		if ( $this->_layout ){
			 $layout = "$template_path/layout/{$this->_layout}.php";
		}
		
		
		foreach (get_object_vars($this->_response) as $key=>$value) {
			$$key = $value;
		}
		
		if ( $layout ){
			$layout_content_template = $template;
			include $layout;
		} else {
			include $template;
		}
		
	}
}