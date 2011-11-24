<?php
	
	
	GLOBAL $AppUI, $project_id;
	
	require_once('simpletest/autorun.php');
	require_once('./calculadora.php');
	
	class TestOfCalculadora extends UnitTestCase {

		function testSomaDoisNumerosInteiros() {
			$calculadora = new Calculadora();
			$this->assertEqual($calculadora->soma(1,1), 2);
		}

	}

?>