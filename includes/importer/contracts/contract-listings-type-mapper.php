<?php
/**
 * Mapper for custom types to Newspack Listings Types.
 *
 * @package Newspack_Listings.
 */

namespace Newspack_Listings\Contracts;

interface Listings_Type_Mapper {
	/**
	 * Get custom listing types mapped to a Newspack Listing Type.
	 *
	 * @param string $listing_type Post_Type type.
	 *
	 * @return string[]
	 */
	public function get_types( string $listing_type ): array;

	/**
	 * Get the Newspack Listing Type from a custom listing type.
	 *
	 * @param string $type Custom listing type.
	 *
	 * @return string
	 */
	public function get_listing_type( string $type ): string;

	/**
	 * Map a set of custom listing types to a Newspack Listing Type.
	 *
	 * @param string   $listing_type Post_Type type.
	 * @param string[] $types Custom listing types.
	 *
	 * @return Listings_Type_Mapper
	 */
	public function set_types( string $listing_type, array $types ): Listings_Type_Mapper;

	/**
	 * Map an individual custom listing type to a Newspack Listing Type.
	 *
	 * @param string $listing_type Post_Type type.
	 * @param string $type Custom listing type.
	 *
	 * @return Listings_Type_Mapper
	 */
	public function add_type( string $listing_type, string $type ): Listings_Type_Mapper;
}
