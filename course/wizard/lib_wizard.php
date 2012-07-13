<?php
function get_stepgo($stepin, $POST){
	switch ($stepin){
	    case 1 :
	        $stepgo = 2;
	        break;
	    case 2 :
	        $stepgo = 3;
            if (array_key_exists('stepgo_1', $POST)) {
				$stepgo = 1;
			}
	        break;
	    case 3 :
	        $stepgo = 4;
            if (array_key_exists('stepgo_2', $POST)) {
				$stepgo = 2;
			}
	        break;
	 }
	return $stepgo;
}
