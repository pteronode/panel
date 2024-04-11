# Changelog

## 1.0.0 (2024-04-11)


### Features

* bump account key limit to 25 ([#4417](https://github.com/pteronode/panel/issues/4417)) ([e0e0689](https://github.com/pteronode/panel/commit/e0e0689846fa07dd90631493b992493b420c7933)), closes [#4394](https://github.com/pteronode/panel/issues/4394)
* **deps:** update to laravel10 ([e798502](https://github.com/pteronode/panel/commit/e7985023854ed94f45de2f85323a53a0c565753b))
* **docker:** automated certificate renewal with certbot ([#3916](https://github.com/pteronode/panel/issues/3916)) ([7866c19](https://github.com/pteronode/panel/commit/7866c190075914b529e69d4f419a3bde3592575c)), closes [#3907](https://github.com/pteronode/panel/issues/3907)
* **egg:** Add Steam out of disk space modal ([#3891](https://github.com/pteronode/panel/issues/3891)) ([0ff2f28](https://github.com/pteronode/panel/commit/0ff2f28cede94b2084c849dec82be6b826f9c187))
* **model/pid:** lowercase error array element ([#3892](https://github.com/pteronode/panel/issues/3892)) ([5f308fe](https://github.com/pteronode/panel/commit/5f308feb3f0da5da35cce1c19ed1558f31cddf39))
* **pid_limit:** expand list of errors for pid_limit feature ([#3942](https://github.com/pteronode/panel/issues/3942)) ([edfd97e](https://github.com/pteronode/panel/commit/edfd97e0b453b004b90d9477fdc7390559d8df48))
* **tf2:** add GSLT variable ([#3927](https://github.com/pteronode/panel/issues/3927)) ([5c229d6](https://github.com/pteronode/panel/commit/5c229d60e3d64b0c5b8d1441b078a739e0c731fc))
* use max percentage instead of xmx ([#4146](https://github.com/pteronode/panel/issues/4146)) ([3339a31](https://github.com/pteronode/panel/commit/3339a316cae253985ac100312f1082ae26af1723))


### Bug Fixes

* artisan translations ([#4069](https://github.com/pteronode/panel/issues/4069)) ([0e3e14a](https://github.com/pteronode/panel/commit/0e3e14aa936cac526ad9a420c00af8ca89606134))
* byte units ([#4419](https://github.com/pteronode/panel/issues/4419)) ([597821b](https://github.com/pteronode/panel/commit/597821b3bb3d3307096783daba4d7e3575897d08))
* correct route for console ([#4178](https://github.com/pteronode/panel/issues/4178)) ([2dda151](https://github.com/pteronode/panel/commit/2dda151a495787fa01d17a91556ee9fd0e179cf4)), closes [#4177](https://github.com/pteronode/panel/issues/4177)
* **deps:** update dependency axios to ^0.28.0 [security] ([#5](https://github.com/pteronode/panel/issues/5)) ([eacff68](https://github.com/pteronode/panel/commit/eacff68ab5b9423000b8594a492163ec124df55e))
* do not style 2fa QR code ([#4278](https://github.com/pteronode/panel/issues/4278)) ([6a11c32](https://github.com/pteronode/panel/commit/6a11c32bb240a88a7600257e1f8bf0c42b4ac327))
* **docker:** handle cases where DB_PORT is not defined ([#3808](https://github.com/pteronode/panel/issues/3808)) ([49dd719](https://github.com/pteronode/panel/commit/49dd719117a319543c24a91dc032854a53eb4e98))
* **eggs:** Forge latest version fetching ([#3770](https://github.com/pteronode/panel/issues/3770)) ([01e7a45](https://github.com/pteronode/panel/commit/01e7a45cc50b2975785c5c44b30e174ac4ce109c))
* enable editing paper and spongeforge version variable input ([#3509](https://github.com/pteronode/panel/issues/3509)) ([556deed](https://github.com/pteronode/panel/commit/556deedcc14c916afdfa490a18248d32a158a00c))
* eslint errors ([2e61a4d](https://github.com/pteronode/panel/commit/2e61a4db136823af8488ef07fb4aa7947f2ff044))
* exception localization ([#3850](https://github.com/pteronode/panel/issues/3850)) ([28f7a80](https://github.com/pteronode/panel/commit/28f7a809a5fb7382ea88bfa48af74a3554da56a1)), closes [#3849](https://github.com/pteronode/panel/issues/3849)
* exclude any permissions not defined internally when updating or creating subusers ([#4416](https://github.com/pteronode/panel/issues/4416)) ([c748fa9](https://github.com/pteronode/panel/commit/c748fa984272fb519384bd7fc522f10e05d3f04f))
* Forge version regex for 1.17+ JPMS ([#3783](https://github.com/pteronode/panel/issues/3783)) ([59d47e7](https://github.com/pteronode/panel/commit/59d47e746b8da91e94408c4fe07d18f16428fa69))
* **forge:** actually fix forge regex ([#3801](https://github.com/pteronode/panel/issues/3801)) ([4e6fe11](https://github.com/pteronode/panel/commit/4e6fe112b0f2ea4490ec6803fed111378a3ee3bc))
* **forge:** validate only input and not length ([#4528](https://github.com/pteronode/panel/issues/4528)) ([c068f57](https://github.com/pteronode/panel/commit/c068f57e4e3d8fb6e25d504edec87404fb525330))
* java version modal default value ([#4216](https://github.com/pteronode/panel/issues/4216)) ([003afb2](https://github.com/pteronode/panel/commit/003afb271b32d0d65c98dbbc9f9f90efde881f67))
* page title for SSH keys ([#4391](https://github.com/pteronode/panel/issues/4391)) ([a6b2509](https://github.com/pteronode/panel/commit/a6b250913b18718c61ca093683074612358815b1))
* paper server jar input rule ([#3494](https://github.com/pteronode/panel/issues/3494)) ([2bbe58e](https://github.com/pteronode/panel/commit/2bbe58e8ec98ea5236a4a94b7db988c29a485359)), closes [#3492](https://github.com/pteronode/panel/issues/3492)
* **paper:** fetching older versions ([#3998](https://github.com/pteronode/panel/issues/3998)) ([695c264](https://github.com/pteronode/panel/commit/695c2647c88d892033b08270e6540ab06e6c4e0d))
* round cpu usage in chart ([#4182](https://github.com/pteronode/panel/issues/4182)) ([63cf6ee](https://github.com/pteronode/panel/commit/63cf6ee96e2dc4e88b0782222d1c320d8a3943b3)), closes [#4168](https://github.com/pteronode/panel/issues/4168)
* **server/files:** duplicate entry when making a nested folder ([#3813](https://github.com/pteronode/panel/issues/3813)) ([1f3217c](https://github.com/pteronode/panel/commit/1f3217c3c54ff3a59244b1ea3c1b2e12a60f7fb1))
* **transformers:** force object type for properties ([#4544](https://github.com/pteronode/panel/issues/4544)) ([634c935](https://github.com/pteronode/panel/commit/634c9353e309c603064530824dfbb3c870a1f7b6))
* update Paper API ([#4080](https://github.com/pteronode/panel/issues/4080)) ([d0b6ae0](https://github.com/pteronode/panel/commit/d0b6ae00dccb04a53d224446393246dcd6109474))
* use correct network stat ([#4175](https://github.com/pteronode/panel/issues/4175)) ([ac997cd](https://github.com/pteronode/panel/commit/ac997cd7a663c1c7d19c1ca749e4052fac49ced3))
* use POST for admin logout route ([#3710](https://github.com/pteronode/panel/issues/3710)) ([d0663dc](https://github.com/pteronode/panel/commit/d0663dcbd4479ebe695a515d3b08820d63d1562a))


### Miscellaneous Chores

* **deps:** update mariadb docker tag to v10.11 ([#28](https://github.com/pteronode/panel/issues/28)) ([f2aaa1c](https://github.com/pteronode/panel/commit/f2aaa1c8bf8e18a7861772f06cab17088c32d0fa))

## Changelog
