<?php
/**
 * Mapper for custom types to Newspack Listings Types.
 *
 * @package Newspack_Listings.
 */

namespace Newspack_Listings;

use Exception;
use Newspack_Listings\Contracts\Listings_Type_Mapper as Listings_Type_Mapper_Interface;

/**
 * Concrete implementation of @see Listings_Type_Mapper_Interface.
 */
class Listings_Type_Mapper implements Listings_Type_Mapper_Interface {

	/**
	 * Default Newspack Listings Types Map.
	 *
	 * @var array|array[] $listing_types
	 */
	protected array $listing_types = [
		Listing_Type::GENERIC     => [],
		Listing_Type::PLACE       => [],
		Listing_Type::MARKETPLACE => [],
		Listing_Type::EVENT       => [],
	];

	/**
	 * Custom listing types mapped to Newspack Listing Types.
	 *
	 * @var array $types
	 */
	protected array $types = [];

	/**
	 * Returns custom listing types for a particular Newspack Listing Type.
	 *
	 * @param string $listing_type Newspack Listing Type.
	 *
	 * @return string[]|array
	 *
	 * @throws Exception Throws exception if given an unsupported Newspack Listing Type.
	 */
	public function get_types( string $listing_type ): array {
		$this->check_listing_type( $listing_type );

		return $this->listing_types[ $listing_type ];
	}

	/**
	 * Get the Newspack Listing Type from a custom listing type.
	 *
	 * @param string $type Custom listing type.
	 *
	 * @return string
	 *
	 * @throws Exception Throws exception if custom listing type doesn't exist.
	 */
	public function get_listing_type( string $type ): string {
		if ( ! array_key_exists( $type, $this->types ) ) {
			throw new Exception( "Type '$type' isn't mapped." );
		}

		return $this->types[ $type ];
	}

	/**
	 * Map a set of custom listing types to a Newspack Listing Type.
	 *
	 * @param string         $listing_type Newspack Listing type.
	 * @param string[]|array $types Custom listing types.
	 *
	 * @throws Exception Throws exception if given an unsupported Newspack Listing Type.
	 */
	public function set_types( string $listing_type, array $types ): Listings_Type_Mapper_Interface {
		$this->check_listing_type( $listing_type );

		$this->listing_types[ $listing_type ] = $types;

		foreach ( $types as $type ) {
			$this->types[ $type ] = $listing_type;
		}

		return $this;
	}

	/**
	 * Map an individual custom listing type to a Newspack Listing Type.
	 *
	 * @param string $listing_type Newspack Listing Type.
	 * @param string $type Custom listing type.
	 *
	 * @throws Exception Throws exception if given an unsupported Newspack Listing Type.
	 */
	public function add_type( string $listing_type, string $type ): Listings_Type_Mapper_Interface {
		$this->check_listing_type( $listing_type );

		$this->listing_types[ $listing_type ][] = $type;
		$this->types[ $type ]                   = $listing_type;

		return $this;
	}

	/**
	 * Convenience function to determine if a custom mapping exists.
	 *
	 * @return bool
	 */
	public function has_mapped_types(): bool {
		return ! empty( $this->types );
	}

	/**
	 * Checks provided listing type against supported list of Newspack Listing Types.
	 *
	 * @param string $listing_type Listing Type.
	 *
	 * @throws Exception Throws exception if given an unsupported Newspack Listing Type.
	 */
	private function check_listing_type( string $listing_type ) {
		if ( ! array_key_exists( $listing_type, $this->listing_types ) ) {
			throw new Exception( "Listing type '$listing_type' doesn't exist" );
		}
	}
}
