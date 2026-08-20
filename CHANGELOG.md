# Changelog

All notable changes to Tops are documented here.

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
