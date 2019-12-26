<?php

	//	Original author: Gourav Mehta
	//	modified by Tec for Sphioder-plus application
	class Doc2Txt{
		public $text;

		function __construct($f){
			if(!isset($f)||!file_exists($f))
				return $this->text="File path does not exist.";

			switch(pathinfo($f,PATHINFO_EXTENSION)){
				case "doc":return $this->text = $this->read_doc($f);
				default: return $this->text = "Invalid File Type";
			}
		}

		function __toString(){
			return $this->text;
		}

		function read_doc($f){
			return preg_replace("/[^a-zA-Z0-9\s\,\.\-\n\r\t@\/\_\(\)]/","",
								implode(" ",array_filter(explode(chr(0x0D), file_get_contents($f)), function($x){
										return strpos($x,chr(0x00))===FALSE&&strlen($x)!=0;})));
		}
	}

?>