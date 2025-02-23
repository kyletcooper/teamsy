<?php

namespace WRD\Teamsy\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use WRD\Teamsy\Traits\HasTeam;

class TeamInspector{
	public function getAllTeamModels(): array{
		$files = File::allFiles( app_path() );
		$models = collect( $files )
			->map(function( $file ){
				$path = $file->getRelativePathname();
				$unresolved = Str::of( $path )->before(".")->replace( "/", "\\" );
				$class = '\\' . app()->getNamespace() . $unresolved;

				return $class;
			})
			->filter( fn( $class ) => $this->isValidTeam( $class ) )
			->values();

		return $models->all();
	}

	public function isValidTeam( string $model ): bool {
		if( ! class_exists( $model ) ){
			return false;
		}

		$reflection = new ReflectionClass( $model );

		if( $reflection->isAbstract() ){
			return false;
		}

		if( ! $reflection->isSubclassOf( Model::class ) ) {
			return false;
		}

		if( ! array_key_exists( HasTeam::class, $reflection->getTraits() ) ){
			return false;
		}

		return true;
	}

	public function getTeamCounts( string $class ): array{
		return $class::withCount([ 'members', 'invitations' ])->orderBy( 'members_count', 'ASC' )->get()->all();
	}

	/**
	 * Get all team relationship names for a model
	 * 
	 * @param Model|string The class name of the model or an instance of a model.
	 * 
	 * @return string[]
	 */
	public function getTeamRelationships( Model|string $model ): array{
		$reflection = new ReflectionClass( $model );

		if( $reflection->isAbstract() ){
			throw new Exception( 'getTeamRelationships: Argument 0 cannot be abstract.' );
		}

		if( ! $reflection->isSubclassOf( Model::class ) ) {
			throw new Exception( 'getTeamRelationships: Argument 0 must by of a subclass of Model.' );
		}

		$methods = $reflection->getMethods( ReflectionMethod::IS_PUBLIC );
		$relations = [];

		foreach( $methods as $method ){
			if( $this->isTeamRelationshipMethod( $method ) ){
				$relations[] = $method->getName();
			}
		}

		return $relations;
	}

	/**
	 * Checks if a reflection method is a valid team relationship.
	 * 
	 * @param ReflectionMethod $method
	 * 
	 * @return bool
	 */
	public function isTeamRelationshipMethod( ReflectionMethod $method ): bool{
		if( ! $method->isPublic()
			|| $method->isAbstract()
			|| $method->getDeclaringClass()->getName() === Model::class
			|| $method->getNumberOfParameters() > 0
		){
			return false;
		}

		$return = $method->getReturnType();

		if( ! $return instanceof ReflectionNamedType ){
			return false;
		}

		return is_subclass_of( $return->getName(), HasTeam::class);
	}
}