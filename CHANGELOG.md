# [2.0.0-alpha.3](https://github.com/Automattic/newspack-listings/compare/v2.0.0-alpha.2...v2.0.0-alpha.3) (2021-07-06)


* v2 release (#85) ([748810d](https://github.com/Automattic/newspack-listings/commit/748810d7c1d817e2a1c218b41b3ad10d74939260)), closes [#85](https://github.com/Automattic/newspack-listings/issues/85) [#40](https://github.com/Automattic/newspack-listings/issues/40) [#39](https://github.com/Automattic/newspack-listings/issues/39) [#32](https://github.com/Automattic/newspack-listings/issues/32) [#41](https://github.com/Automattic/newspack-listings/issues/41) [#49](https://github.com/Automattic/newspack-listings/issues/49) [#43](https://github.com/Automattic/newspack-listings/issues/43) [#56](https://github.com/Automattic/newspack-listings/issues/56) [#51](https://github.com/Automattic/newspack-listings/issues/51) [#57](https://github.com/Automattic/newspack-listings/issues/57) [#61](https://github.com/Automattic/newspack-listings/issues/61) [#67](https://github.com/Automattic/newspack-listings/issues/67) [#60](https://github.com/Automattic/newspack-listings/issues/60) [#70](https://github.com/Automattic/newspack-listings/issues/70) [#65](https://github.com/Automattic/newspack-listings/issues/65) [#71](https://github.com/Automattic/newspack-listings/issues/71) [#66](https://github.com/Automattic/newspack-listings/issues/66) [#58](https://github.com/Automattic/newspack-listings/issues/58) [#77](https://github.com/Automattic/newspack-listings/issues/77) [#81](https://github.com/Automattic/newspack-listings/issues/81)


### Bug Fixes

* errors and bugs related to WP 5.8 ([#83](https://github.com/Automattic/newspack-listings/issues/83)) ([90da6c5](https://github.com/Automattic/newspack-listings/commit/90da6c5449e7649bec90206537994c93d0e576a9))


### BREAKING CHANGES

* This feature will deprecate existing custom taxonomies, so any existing terms for those taxonomies will be lost.

To fix, we can convert terms from the deprecated taxonomies to standard post categories/tags via a migration script.

# [2.0.0-alpha.2](https://github.com/Automattic/newspack-listings/compare/v2.0.0-alpha.1...v2.0.0-alpha.2) (2021-06-30)


### Bug Fixes

* default listings to one-column-wide.php post template ([#77](https://github.com/Automattic/newspack-listings/issues/77)) ([e987e76](https://github.com/Automattic/newspack-listings/commit/e987e76db51b58516a585f6531427e5c27488d3b))
* memory leaks from legacy term utilities ([#81](https://github.com/Automattic/newspack-listings/issues/81)) ([4576805](https://github.com/Automattic/newspack-listings/commit/4576805a5deac6b76ad477bbce76a120a4867385))

# [2.0.0-alpha.2](https://github.com/Automattic/newspack-listings/compare/v2.0.0-alpha.1...v2.0.0-alpha.2) (2021-06-30)


### Bug Fixes

* default listings to one-column-wide.php post template ([#77](https://github.com/Automattic/newspack-listings/issues/77)) ([e987e76](https://github.com/Automattic/newspack-listings/commit/e987e76db51b58516a585f6531427e5c27488d3b))
* memory leaks from legacy term utilities ([#81](https://github.com/Automattic/newspack-listings/issues/81)) ([4576805](https://github.com/Automattic/newspack-listings/commit/4576805a5deac6b76ad477bbce76a120a4867385))

# [2.0.0-alpha.2](https://github.com/Automattic/newspack-listings/compare/v2.0.0-alpha.1...v2.0.0-alpha.2) (2021-06-30)


### Bug Fixes

* default listings to one-column-wide.php post template ([#77](https://github.com/Automattic/newspack-listings/issues/77)) ([e987e76](https://github.com/Automattic/newspack-listings/commit/e987e76db51b58516a585f6531427e5c27488d3b))
* memory leaks from legacy term utilities ([#81](https://github.com/Automattic/newspack-listings/issues/81)) ([4576805](https://github.com/Automattic/newspack-listings/commit/4576805a5deac6b76ad477bbce76a120a4867385))

# [2.0.0-alpha.2](https://github.com/Automattic/newspack-listings/compare/v2.0.0-alpha.1...v2.0.0-alpha.2) (2021-06-30)


### Bug Fixes

* default listings to one-column-wide.php post template ([#77](https://github.com/Automattic/newspack-listings/issues/77)) ([e987e76](https://github.com/Automattic/newspack-listings/commit/e987e76db51b58516a585f6531427e5c27488d3b))
* memory leaks from legacy term utilities ([#81](https://github.com/Automattic/newspack-listings/issues/81)) ([4576805](https://github.com/Automattic/newspack-listings/commit/4576805a5deac6b76ad477bbce76a120a4867385))

# [2.0.0-alpha.1](https://github.com/Automattic/newspack-listings/compare/v1.2.1...v2.0.0-alpha.1) (2021-06-21)


### Bug Fixes

* activation PHP warning ([#70](https://github.com/Automattic/newspack-listings/issues/70)) ([29b0a34](https://github.com/Automattic/newspack-listings/commit/29b0a34faa1c479cd9f873b4d17aa70eb5f698a5))
* failing npm ci command ([f509f36](https://github.com/Automattic/newspack-listings/commit/f509f3676c6b8c74dba4d898f5493c269649efba))
* guard against nonexistent meta object ([#66](https://github.com/Automattic/newspack-listings/issues/66)) ([c821a09](https://github.com/Automattic/newspack-listings/commit/c821a0919fff3caaa07babbfe1c49e38658bd738))
* missing condition for block appender in list container ([#74](https://github.com/Automattic/newspack-listings/issues/74)) ([2c49896](https://github.com/Automattic/newspack-listings/commit/2c498961b80324d8cbea61c7fbc356ca7ccb8de5))
* newspack_blocks support slug ([a2bda56](https://github.com/Automattic/newspack-listings/commit/a2bda564a3b5844df965bad824c1a8acb3984d00))
* remove material packages ([b489015](https://github.com/Automattic/newspack-listings/commit/b48901546198df8a982c41fb1da714862ccf3412))
* use synced attributes for ListContainer directly ([#73](https://github.com/Automattic/newspack-listings/issues/73)) ([f8641a7](https://github.com/Automattic/newspack-listings/commit/f8641a726ce7c2949c01b232829f8923f06b61ad))
* use value property of selection from AutocompleteWithSuggestions ([#61](https://github.com/Automattic/newspack-listings/issues/61)) ([c7c4cea](https://github.com/Automattic/newspack-listings/commit/c7c4ceaf3c9d9682d8441028f2f64516638d6aca))
* util for checking post type on new posts ([fb61530](https://github.com/Automattic/newspack-listings/commit/fb6153050fb08c29864fb8f304f583d82056f411))
* warning about default meta value ([16af17b](https://github.com/Automattic/newspack-listings/commit/16af17b7cf2338e001efc0a4064b69b28c30d39e))
* wp_insert_post filter name and theme_mod filter ([5befca7](https://github.com/Automattic/newspack-listings/commit/5befca7085cbcdb346d31ed24d2001e6eb0d042f))


### Features

* add a new global setting and post option to hide date ([#57](https://github.com/Automattic/newspack-listings/issues/57)) ([896f68f](https://github.com/Automattic/newspack-listings/commit/896f68f8371cfacae0acdf3977e0faa859c1149e))
* add revisions support for listings ([42d04d2](https://github.com/Automattic/newspack-listings/commit/42d04d2ccb0d87df75df00a01fa53ddda758cb95))
* add settings for individual listing type URL slugs ([d78a3f7](https://github.com/Automattic/newspack-listings/commit/d78a3f7ca9eb7387dd63b71318ed86f7d41c8ac5)), closes [#41](https://github.com/Automattic/newspack-listings/issues/41)
* better integration with Newspack Theme features ([823f66a](https://github.com/Automattic/newspack-listings/commit/823f66a22cbcf2d987a69f8b44e5b89081c94ee4))
* child and related listings UI ([#58](https://github.com/Automattic/newspack-listings/issues/58)) ([06aff81](https://github.com/Automattic/newspack-listings/commit/06aff8195b14d643e4c2c27db50c990e67a5589a))
* convert legacy custom terms to regular post terms ([#67](https://github.com/Automattic/newspack-listings/issues/67)) ([a2fcf84](https://github.com/Automattic/newspack-listings/commit/a2fcf84d160a7ed2f0c5ebd906e1931d0df8f49b))
* CSV importer script ([#51](https://github.com/Automattic/newspack-listings/issues/51)) ([ffbea00](https://github.com/Automattic/newspack-listings/commit/ffbea0057af3a2702587ef494c5f3fd7e7a29955))
* flush permalinks automatically if updating slug option ([988521e](https://github.com/Automattic/newspack-listings/commit/988521e2359d71ed9bfbd4acc3678c6aff6e4727))
* support Newspack Sponsors for listings ([#65](https://github.com/Automattic/newspack-listings/issues/65)) ([7d2ef64](https://github.com/Automattic/newspack-listings/commit/7d2ef649611b85794f8c950c706af737fc4b955f))
* update cpt icon and block icons ([7b59032](https://github.com/Automattic/newspack-listings/commit/7b59032250fda599de449dfdcbf15a7b00e1fe86)), closes [#49](https://github.com/Automattic/newspack-listings/issues/49)
* update price block to use placeholder and large font size ([#71](https://github.com/Automattic/newspack-listings/issues/71)) ([710f34c](https://github.com/Automattic/newspack-listings/commit/710f34ce415447ba52359e8dd2fee04c0e795542))
* use post categories and tags for all listing post types ([#39](https://github.com/Automattic/newspack-listings/issues/39)) ([f223053](https://github.com/Automattic/newspack-listings/commit/f2230534cc3d34f088d38ac3669a54c566858f8f)), closes [#32](https://github.com/Automattic/newspack-listings/issues/32)


### BREAKING CHANGES

* This feature will deprecate existing custom taxonomies, so any existing terms for those taxonomies will be lost.

To fix, we can convert terms from the deprecated taxonomies to standard post categories/tags via a migration script.

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
