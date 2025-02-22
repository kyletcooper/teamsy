<?php

namespace WRD\Teamsy\Providers;

use Composer\InstalledVersions;
use Illuminate\Support\ServiceProvider;

class InteropServiceProvider extends ServiceProvider {
	/**
	 * The required dependencies for this service provider to register.
	 * 
	 * @var string[] $required
	 */
	static public array $require = [];

	/**
	 * Checks if the interop service provider can be registered.
	 * 
	 * @return bool
	 */
	static public function shouldRegister(): bool{
		if( ! static::checkDependencies() ){
			return false;
		}

		return true;
	}

	/**
	 * Checks all dependencies are installed.
	 * 
	 * @return bool
	 */
	static protected function checkDependencies(): bool{
		foreach( static::$require as $package ){
			if( ! InstalledVersions::isInstalled( $package ) ){
				return false;
			}
		}

		return true;
	}
}