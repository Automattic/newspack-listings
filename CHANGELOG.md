# [2.0.0](https://github.com/Automattic/newspack-listings/compare/v1.2.2...v2.0.0) (2021-07-06)


* v2 release (#85) ([748810d](https://github.com/Automattic/newspack-listings/commit/748810d7c1d817e2a1c218b41b3ad10d74939260)), closes [#85](https://github.com/Automattic/newspack-listings/issues/85) [#40](https://github.com/Automattic/newspack-listings/issues/40) [#39](https://github.com/Automattic/newspack-listings/issues/39) [#32](https://github.com/Automattic/newspack-listings/issues/32) [#41](https://github.com/Automattic/newspack-listings/issues/41) [#49](https://github.com/Automattic/newspack-listings/issues/49) [#43](https://github.com/Automattic/newspack-listings/issues/43) [#56](https://github.com/Automattic/newspack-listings/issues/56) [#51](https://github.com/Automattic/newspack-listings/issues/51) [#57](https://github.com/Automattic/newspack-listings/issues/57) [#61](https://github.com/Automattic/newspack-listings/issues/61) [#67](https://github.com/Automattic/newspack-listings/issues/67) [#60](https://github.com/Automattic/newspack-listings/issues/60) [#70](https://github.com/Automattic/newspack-listings/issues/70) [#65](https://github.com/Automattic/newspack-listings/issues/65) [#71](https://github.com/Automattic/newspack-listings/issues/71) [#66](https://github.com/Automattic/newspack-listings/issues/66) [#58](https://github.com/Automattic/newspack-listings/issues/58) [#77](https://github.com/Automattic/newspack-listings/issues/77) [#81](https://github.com/Automattic/newspack-listings/issues/81)


### Bug Fixes

* errors and bugs related to WP 5.8 ([#83](https://github.com/Automattic/newspack-listings/issues/83)) ([90da6c5](https://github.com/Automattic/newspack-listings/commit/90da6c5449e7649bec90206537994c93d0e576a9))


### BREAKING CHANGES

* This feature will deprecate existing custom taxonomies, so any existing terms for those taxonomies will be lost.

To fix, we can convert terms from the deprecated taxonomies to standard post categories/tags via a migration script.

## [1.2.2](https://github.com/Automattic/newspack-listings/compare/v1.2.1...v1.2.2) (2021-06-22)


### Bug Fixes

* missing condition for block appender in list container ([#74](https://github.com/Automattic/newspack-listings/issues/74)) ([2c49896](https://github.com/Automattic/newspack-listings/commit/2c498961b80324d8cbea61c7fbc356ca7ccb8de5))
* use synced attributes for ListContainer directly ([#73](https://github.com/Automattic/newspack-listings/issues/73)) ([f8641a7](https://github.com/Automattic/newspack-listings/commit/f8641a726ce7c2949c01b232829f8923f06b61ad))

## [1.2.1](https://github.com/Automattic/newspack-listings/compare/v1.2.0...v1.2.1) (2021-06-08)


### Bug Fixes

* syncing attributes from curated list block to inner blocks ([#64](https://github.com/Automattic/newspack-listings/issues/64)) ([cdbc0bb](https://github.com/Automattic/newspack-listings/commit/cdbc0bb63bf4d8b18dc30fab87ce1a3ab68c7ddd))

# [1.2.0](https://github.com/Automattic/newspack-listings/compare/v1.1.0...v1.2.0) (2021-02-11)


### Features

* add block patterns ([#23](https://github.com/Automattic/newspack-listings/issues/23)) ([a273a40](https://github.com/Automattic/newspack-listings/commit/a273a40c0056cf09879d491083c2ca2321413896))

# [1.1.0](https://github.com/Automattic/newspack-listings/compare/v1.0.0...v1.1.0) (2020-12-18)


### Bug Fixes

* minor bug fixes ([#21](https://github.com/Automattic/newspack-listings/issues/21)) ([5f90bc7](https://github.com/Automattic/newspack-listings/commit/5f90bc7d027a2693a9dc4d804484ce0a78c4e4ff))


### Features

* remove borders and padding in editor to match front-end styles ([#14](https://github.com/Automattic/newspack-listings/issues/14)) ([6c47a17](https://github.com/Automattic/newspack-listings/commit/6c47a1760ea9429facb089f2be7bd71a91924cf0))

# 1.0.0 (2020-12-16)


### Features

* initial post type and block setup ([#1](https://github.com/Automattic/newspack-listings/issues/1)) ([47dc0c1](https://github.com/Automattic/newspack-listings/commit/47dc0c11cb8041117d5229e49ac14f49cee1b1ff))
* listing taxonomies and query mode ([#6](https://github.com/Automattic/newspack-listings/issues/6)) ([528e1e5](https://github.com/Automattic/newspack-listings/commit/528e1e5a25000c7746b62b88566803424879da14))
* new Curated List block, block pattern, and map functionality ([#3](https://github.com/Automattic/newspack-listings/issues/3)) ([9be6e7e](https://github.com/Automattic/newspack-listings/commit/9be6e7ebae9028407d67071e13857ab7827deff9))