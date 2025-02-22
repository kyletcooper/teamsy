<?php

namespace WRD\Teamsy\Support;

use Exception;

class Ensure{
	static public function usesTrait( string $class, string $trait ){
		if( ! in_array( $trait, class_uses_recursive( $class ) ) ){
			throw new Exception( "$class must use the HasApiModel trait." );
		}
	}
}