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
	    case 5 :
	        $stepgo = 6;
	        break;
	   case 6 :
	        $stepgo = 7;
	         break;
	   case 7 :
	        $stepgo = 8;
	         break;
	 }
	return $stepgo;
}
