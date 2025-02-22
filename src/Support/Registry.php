<?php

namespace WRD\Teamsy\Support;

/**
 * @template T
 */
class Registry{
	protected $items;

	/**
	 * Create a new registry.
	 */
	public function __construct() {
		$this->items = collect([]);
	}

	/**
	 * Adds an item to the registry.
	 * 
	 * @param string $name
	 * 
	 * @param T $item
	 * 
	 * @return void
	 */
	protected function register( string $name, mixed $item ): void{
		$this->items->put( $name, $item );
	}

	/**
	 * Removes an item from the registry.
	 * 
	 * @param string $name
	 * 
	 * @return void
	 */
	protected function deregister( string $name ): void{
		$this->items->forget( $name );
	}

	/**
	 * Find an item by it's key.
	 * 
	 * @return T
	 */
	protected function find( string $name ): mixed {
		return $this->items->get( $name );
	}

	/**
	 * Get all registered items.
	 * 
	 * @return T[]
	 */
	protected function all(): array {
		return $this->items->all();
	}
}