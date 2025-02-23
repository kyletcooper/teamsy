<?php

namespace WRD\Teamsy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
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
}