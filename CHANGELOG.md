# Changelog

All notable changes to Tops are documented here.

## [1.1.1] - 2026-08-20

### Fixed
- `config.yml` was bundled at the plugin root instead of `resources/`, so `saveDefaultConfig()` (which reads from `resources/`) never actually created it in the data folder. Moved next to `messages.yml`.
- All SQLite `AsyncTask`s are now pinned to a single fixed `AsyncPool` worker instead of round-robining across the pool. SQLite3 handles are not guaranteed safe to use concurrently from multiple OS threads, and that was crashing an async worker mid-session, silently breaking every top refresh and any pending write after it.
- `TopsCommand` never had a permission set on the underlying `Command` object; PM5's `SimpleCommandMap::register()` now hard-requires one, which crashed plugin enable. Added a `tops.command` base permission (default `true`; the real gating stays on each subcommand).

### Added
- Commando's `PacketHooker` is now registered, so `/tops <Tab>` shows native subcommand/argument autocompletion in the Bedrock client, via `muqsit/simple-packet-handler` (added as a VCS composer dependency since it isn't on Packagist).

## [1.1.0] - 2026-08-20

### Added
- Fully configurable hologram design: per-category title/color, per-line format, "no data" message, Money top decimals, and playtime suffixes.
- Configurable command messages (`spawned`, `despawned`, `not-found`, `reloaded`) with placeholders.
- All text and colors moved out of `config.yml` into a dedicated `messages.yml`, hot-reloadable with `/tops reload`.
- Colors use `&` instead of `§` in config, resolved once per refresh cycle (no extra TPS cost).

## [1.0.0] - 2026-08-20

### Added
- Kills, Deaths, Money and Playtime tops, each with its own hologram.
- 100% asynchronous SQLite persistence (`AsyncTask` for schema init, increments, money sync and top fetches).
- Economy support via libPiggyEconomy, with BedrockEconomy and EconomyAPI selectable through config.
- `/tops spawn <category>`, `/tops despawn <category>` and `/tops reload` commands, built with Commando.
- Batched money sync for online players, avoiding lag spikes with synchronous economy plugins.
- Periodic playtime flush as a safety net against unclean server shutdowns.

### Technical
- PHPStan level 8 and level 9 clean.
- `strict_types=1` across the codebase.
- Build script (`build/build.php`) that merges the virions (Commando, libPiggyEconomy) and compiles the `.phar` with DevTools.
