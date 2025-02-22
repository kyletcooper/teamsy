<?php

namespace WRD\Teamsy\Capabilities;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use WRD\Teamsy\Traits\HasTeam;

class Role{
	/**
	 * The id of the role.
	 */
	protected string $id;

	/**
	 * The title of the role.
	 */
	protected string $title = "";

	/**
	 * The description of the role.
	 */
	protected string $description = "";

	/**
	 * Denotes what team types the role applies to.
	 * 
	 * Null means it applies to all team types.
	 */
	protected ?string $scope = null;

	/**
	 * The flags of the role.
	 * 
	 * @var RoleFlag[] $flags
	 */
	protected array $flags = [];

	/**
	 * @var \Illuminate\Support\Collection<string> $capabilities
	 */
	protected Collection $capabilities;

	public function __construct(string $id, array $capabilities = [])
	{
		$this->id = $id;
		$this->capabilities = collect($capabilities);
	}

	/**
	 * Get the id of the role.
	 * 
	 * @return string
	 */
	public function getId(): string{
		return $this->id;
	}

	/**
	 * Set the title of the role.
	 * 
	 * @return static
	 */
	public function title(string $title): static{
		$this->title = $title;

		return $this;
	}

	/**
	 * Get the title of the role.
	 * 
	 * @return string
	 */
	public function getTitle(): string{
		return $this->title;
	}

	/**
	 * Set the description of the role.
	 * 
	 * @return static
	 */
	public function describe(string $description): static{
		$this->description = $description;

		return $this;
	}

	/**
	 * Get the description of the role.
	 * 
	 * @return string
	 */
	public function getDescription(): string{
		return $this->description;
	}

	/**
	 * Denote that a role only applies to certain team types.
	 * 
	 * @return static
	 */
	public function scope( string $teamType ): static{
		if( ! in_array( HasTeam::class, class_uses_recursive( $teamType ) ) ){
			throw new Exception( "Scope must be a model which uses the HasTeam trait." );
		}

		$this->scope = $teamType;

		return $this;
	}

	/**
	 * Checks if this role applies to a certain team type.
	 * 
	 * @return bool
	 */
	public function scopedFor( ?string $teamType ): bool{
		if( is_null( $this->scope ) ){
			return true;
		}

		return $this->scope === $teamType;
	}

	/**
	 * Get the team type this role applies to.
	 * 
	 * @return bool
	 */
	public function getScope(): string{
		if( is_null( $this->scope ) ){
			return "*";
		}

		return $this->scope;
	}

	/**
	 * Set the flags for the role.
	 * 
	 * @return static
	 */
	public function flags(array $flags): static{
		$this->flags = $flags;

		return $this;
	}

	/**
	 * Get the flags.
	 * 
	 * @return RoleFlag[]
	 */
	public function getFlags(): array{
		return $this->flags;
	}

	/**
	 * Checks if the role has a flag.
	 * 
	 * @return bool
	 */
	public function hasFlag( RoleFlag $flag ): bool{
		return in_array( $flag, $this->flags );
	}

	/**
	 * Get the capabilties list.
	 * 
	 * @return string[]
	 */
	public function getCapabilities(): array{
		return $this->capabilities->all();
	}

	/**
	 * Add a new capability.
	 * 
	 * @param string[]|string $cap
	 * 
	 * @return static
	 */
	public function allow(string|array $cap): static{
		if( ! is_array( $cap ) ){
			$cap = [ $cap ];
		}

		$this->capabilities = $this->capabilities->push(...$cap)->unique();

		return $this;
	}

	/**
	 * Add a new capability.
	 * 
	 * @param string[]|string $cap
	 * 
	 * @return static
	 */
	public function forbid(string|array $cap): static{
		if( ! is_array( $cap ) ){
			$cap = [ $cap ];
		}

		$this->capabilities = $this->capabilities->filter(fn($value) => 
			in_array( $value, $cap )
		);

		return $this;
	}

	/**
	 * Checks if the role has a capability.
	 * 
	 * Wildcard operators are also checked. For example 'posts.read' would pass if the role has the 'posts.*' capability.
	 * 
	 * @return bool
	 */
	public function hasCapability(string $cap): bool{
		foreach( $this->capabilities as $capability ){
			if( $cap === $capability ){
				return true;
			}

			if( $capability === "*" ){
				return true;
			}

			if( Str::endsWith( $capability, ".*" ) ){
				$ancestorCapability = Str::chopEnd( $capability, ".*" ) . '.';

				/**
				 * If the capability is a wildcard, match any descendent capabilities.
				 * 
				 * For example, 'posts.*' will match 'posts.create', AND 'posts.create.own'.
				 */
				if( Str::startsWith( $cap, $ancestorCapability ) ){
					return true;
				}
			}
		}

		return false;
	}
}