<?php

namespace WRD\Teamsy\Support;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Ensure{
	static public function usesTrait( string $class, string $trait ){
		if( ! in_array( $trait, class_uses_recursive( $class ) ) ){
			throw new Exception( "$class must use the HasApiModel trait." );
		}
	}

	static public function callerIn( array|string $allowed ){
		$allowed = Arr::wrap( $allowed );
		$list = Arr::join( $allowed, ', ', ' or ' );
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);

		$previous = $trace[1] ?? null;
		$caller = $trace[2] ?? null;
		
		if( ! $previous ){
			throw new Exception( "You should not call this function directly, instead use $list." );
		}

		$name = $previous['function'];

		if( ! $caller ){
			throw new Exception( "$name should not be called directly, you must use $list." );
		}
		
		if( ! in_array( $caller['function'], $allowed ) ){
			throw new Exception( "$name should not be called directly, you must use $list." );
		}
	}
}