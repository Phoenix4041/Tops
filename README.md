# Tops

### Kills, Deaths, Money and Playtime leaderboards with per-category holograms

---

## Features

* **4 top categories**: Kills, Deaths, Money and Playtime, each with its own independent hologram.
* **Real PM5 floating text**: `FloatingTextParticle` no longer exists in PocketMine-MP 5.x — the hologram is a `falling_block` actor forced to render as air (no invisibility hack needed), with multi-line text in a single nameTag.
* **SQLite persistence**: every read/write runs in an `AsyncTask`, never blocks the main thread. Tops are computed with indexed `ORDER BY ... LIMIT` queries. All queries are pinned to a single `AsyncPool` worker (SQLite3 isn't safe across concurrent OS threads).
* **Plug-and-play economy**: supports BedrockEconomy and EconomyAPI through [libPiggyEconomy](https://github.com/DaPigGuy/libPiggyEconomy), selectable via config with no code changes.
* **Commands with Commando**: `/tops spawn|despawn|reload` built on the [Commando](https://github.com/CortexPE/Commando) framework, with argument validation, per-subcommand permissions and native Tab-autocomplete on Bedrock clients.
* **Zero TPS impact**: no per-entity tasks, no O(n) player lookups, no I/O on the main thread, and holograms that only update when their text actually changed.

---

## Requirements

* PocketMine-MP 5.0+
* PHP 8.1+
* Optional: BedrockEconomy or EconomyAPI if you want the Money top active

---

## Installation

1. Download `Tops.phar` from `build/Tops.phar` (or build it yourself, see below).
2. Drop it into your server's `plugins/` folder.
3. Restart the server. `config.yml` and `messages.yml` will be generated in `plugins/Tops/`.
4. If you want the Money top, install BedrockEconomy or EconomyAPI and set `economy.provider` in `config.yml`.

---

## Configuration

* `config.yml` - behavior: refresh intervals, top size, economy provider, Money decimals, playtime suffixes.
* `messages.yml` - everything visual and text-related, no code changes needed:
  * `categories.<name>.title` - title/color for that category (`&` for colors).
  * `categories.<name>.top1` / `top2` / `top3` - format for each of the top 3 spots, independently customizable per rank (e.g. gold/silver/bronze).
  * `categories.<name>.line-format` - format shared by every rank from 4 onward. All of the above use `{pos}`, `{name}`, `{value}` placeholders.
  * `no-data-message` - text shown while a category has no data yet.
  * `prefix` - prefix for every command message.
  * `spawned` / `despawned` / `not-found` / `reloaded` - command messages, with `{categoria}`/`{distancia}` placeholders.

Changing `economy.provider` or any `*-interval-ticks` in `config.yml` requires a server restart. All of `messages.yml` (colors, text, row format) hot-reloads with `/tops reload`.

---

## Permissions

```yaml
tops.command            # Base permission to see the command exists (default: true)
tops.command.spawn      # Spawn a top hologram (default: op)
tops.command.despawn    # Remove a top hologram (default: op)
tops.command.reload     # Reload config.yml / messages.yml (default: op)
```

---

## Usage

### Commands

```bash
/tops spawn <categoria>     # categoria: kills | deaths | dinero | tiempo
/tops despawn <categoria>   # removes the nearest hologram of that category (10 blocks)
/tops reload                # reloads config.yml and messages.yml
```

**Aliases**: None

### How It Works

**Spawning a top**:
1. Stand where you want the hologram.
2. Run `/tops spawn kills` (or `deaths`, `dinero`, `tiempo`).
3. The hologram appears 2 blocks above your position, facing the direction you're looking.

**Updating**:
1. A single global task recomputes all 4 tops every `refresh-interval-ticks` (5s by default) via `AsyncTask`.
2. Every existing hologram of that category updates its text only if it changed.

---

## Data Tracking

* **Kills / Deaths**: recorded on `PlayerDeathEvent`. Every death counts as a Death; if the killer was another player, it also counts as their Kill.
* **Playtime**: measured per session (join → quit) and persisted on quit, with a periodic backup flush (`playtime-flush-interval-ticks`) in case the server goes down uncleanly.
* **Money**: synced from the selected economy provider, only for online players (batched so it doesn't hit the economy plugin all at once), and stored in the same SQLite table as the other stats.

---

## Performance Optimization

### Architecture

* A single `HologramRegistry` index (via `WeakReference`) resolves which holograms to update without walking the world.
* `PlaytimeTracker` derives playtime in memory (join timestamp) instead of ticking a counter every second.
* Every SQLite write is an atomic `UPSERT` (`INSERT ... ON CONFLICT DO UPDATE`) — no prior reads, no manual locks.
* Only 3 global tasks in the whole plugin (top refresh, playtime flush, money sync); none per player or per entity.
* Money sync is batched (`money-sync-batch-size`) because some economy plugins resolve balances synchronously.

### Benchmarks

* **Write per death**: 1 async `UPSERT`, ~200 players generate at most a handful of writes per minute.
* **Top recompute**: 4 indexed `SELECT ... ORDER BY ... LIMIT` queries over a <1000-row table, off the main thread.
* **TPS Impact**: none observed locally — zero synchronous I/O, zero O(n) player lookups, zero per-entity tasks.
* **Memory Overhead**: one `WeakReference` per spawned hologram + one `int` per online player (playtime session).

---

## Technical Details

### Architecture

```
src/Tops/
├── Loader.php
├── TopCategory.php
├── TopsConfig.php
├── StatsRepository.php
├── PlaytimeTracker.php
├── Permissions.php
├── async/           # one AsyncTask per operation (init, increment, set money, fetch tops)
├── command/          # TopsCommand (Commando) + subcommands + category argument
├── entity/           # TopsHologram (floating text)
├── format/           # TimeFormatter
├── hologram/         # HologramRegistry (O(1) index per category)
├── listener/         # player activity + hologram auto-registration
└── task/             # the 3 global repeating tasks
```

### Design Principles
* **Single Responsibility**: every class solves one concrete problem (no `Manager` that just wraps an array).
* **Dependency Injection**: everything is constructor-injected; `Loader::getInstance()` is used only where PM5 requires it (`AsyncTask::onCompletion()` callbacks).
* **Type Safety**: `strict_types=1` across the codebase, PHPStan level 9 with no suppressions.

---

## Troubleshooting

### The Money top always shows 0
* Check that `economy.provider` in `config.yml` matches the installed plugin (`bedrockeconomy` or `economyapi`) and restart the server after installing it.

### `/tops despawn` says it can't find the hologram
* You must be within 10 blocks of the hologram, in the same world.

---

## Contributing

* **Code Quality**: no empty managers, no untyped nested arrays, early-returns over nesting.
* **Performance**: any change must justify its TPS cost (see the performance hierarchy in the code).
* **Type Safety**: PHPStan level 9 must keep passing with no new suppressions.

---

## Roadmap

- [ ] MySQL support for multi-server networks (currently SQLite, built for a single server).
- [ ] Weekly/monthly top with automatic counter reset.
- [ ] PlaceholderAPI placeholders (show each category's #1 in scoreboard/tab).
- [ ] Optional "summary" hologram with all 4 categories on one board.
- [ ] Automatic rewards for each category's #1 (via a configurable command).
- [ ] Support for libPiggyEconomy's XPProvider as an optional 5th category ("Experience").

---

## License

This project is licensed under the Private License - see [LICENSE](LICENSE) file for details.

---

## Testing

* **PHPStan**: level 8 and level 9 verified with no errors (`vendor/bin/phpstan analyse`).
* **Syntax**: every file in `src/Tops` passes `php -l`.
* **Build**: `build/build.php` compiles an end-to-end loadable `.phar` (verified locally).

---

## Version Support

| Version | Release Date | Status | Support |
|---------|-------------|--------|---------|
| 1.2.0 | 2026-08-20 | 🟢 Active | Full support |

See [CHANGELOG.md](CHANGELOG.md) for the full version history.

---

**Made with ❤️ by HVNS**
