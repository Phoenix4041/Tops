# Changelog

All notable changes to Tops are documented here.

## [1.2.1] - 2026-08-21

### Fixed
- `TopsCommand` relied on `pocketmine\command\Command::getPermissions()` being inherited to satisfy Commando's `IRunnable` interface. That method isn't guaranteed on every PMMP 5.x-API-compatible fork/build, and an outdated bundled copy of the Commando virion in an older build caused `Class TopsCommand contains 1 abstract method` on enable. `TopsCommand` now declares its own `getPermissions()` instead of depending on inherited behavior.
- Leaderboard names were stored and displayed in lowercase (`strtolower($player->getName())` was the only name ever persisted). Added a `display_name` column to `player_stats`, kept in sync on every kill/death/playtime/money write, so holograms show the player's actual capitalization.
- The Playtime top only advanced on `PlayerQuitEvent` or the periodic flush (`playtime-flush-interval-ticks`, 5 minutes by default), so an online player's entry stayed stuck for minutes at a time instead of counting up. Top refreshes now merge each online player's live in-memory session time on top of their persisted total, so Playtime updates every `refresh-interval-ticks` like the other categories.

## [1.2.0] - 2026-08-20

### Added
- Per-category hologram design in `messages.yml`: each category now has its own `title`, its own format for ranks `top1`/`top2`/`top3` (independently customizable, e.g. gold/silver/bronze), and a shared `line-format` for rank 4 onward.

### Changed
- Replaced the single global `titles` map and `line-format` with a `CategoryDisplay` value object per category, resolved once at config load/reload.

## [1.1.3] - 2026-08-20

### Fixed
- The `PIG`-based hologram still didn't render on the actual client. Switched to the technique used by working PM5 hologram plugins (e.g. [armorshard1/pmholograms](https://github.com/armorshard1/pmholograms)): a `minecraft:falling_block` actor with its `VARIANT` metadata forced to air's block state, so there's nothing to render but the nameTag — no invisibility flag or scale hack needed.

## [1.1.2] - 2026-08-20

### Fixed
- Hologram entity used `minecraft:armor_stand` as its network type on a bare `Entity`, which doesn't carry the actor components Bedrock expects for that type and simply never rendered client-side. Switched to an invisible mob (`minecraft:pig`), the same approach every working PM5 hologram plugin uses.
- `StatsRepository` now catches a dead pinned worker instead of letting the exception propagate. An unrelated plugin's AsyncTask (e.g. EconomyAPI's `/topmoney`, which has its own bug) can kill the shared `AsyncPool` worker Tops pins its queries to; when that happens Tops now logs a warning instead of throwing a second crash on top of it (observed as "Crashed while crashing, killing process" during shutdown).

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
