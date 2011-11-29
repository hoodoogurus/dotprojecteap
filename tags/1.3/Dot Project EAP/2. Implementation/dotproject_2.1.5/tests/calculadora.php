<?php

class Calculadora {
	function soma($a,$b) {
		if (is_int($a) && is_int($b)){
			return $a + $b;
		} else {
			return false;
		}
	}
}

?>