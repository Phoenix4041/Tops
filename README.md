# Tops

### Leaderboards de Kills, Deaths, Dinero y Tiempo jugado con hologramas por categoria

---

## Features

* **4 categorias de top**: Kills, Deaths, Dinero y Tiempo jugado, cada una con su propio holograma independiente.
* **Floating text real para PM5**: hologramas hechos con una entidad invisible y sin gravedad (`FloatingTextParticle` ya no existe en PocketMine-MP 5.x), con texto multilinea en un solo nametag.
* **Persistencia en SQLite**: toda la lectura/escritura corre en `AsyncTask`, nunca bloquea el hilo principal. Los tops se calculan con consultas `ORDER BY ... LIMIT` indexadas.
* **Economia plug-and-play**: soporta BedrockEconomy y EconomyAPI mediante [libPiggyEconomy](https://github.com/DaPigGuy/libPiggyEconomy), seleccionable por config sin tocar codigo.
* **Comandos con Commando**: `/tops spawn|despawn|reload` construidos sobre el framework [Commando](https://github.com/CortexPE/Commando), con validacion de argumentos y permisos por subcomando.
* **Cero impacto en TPS**: sin tasks por entidad, sin lookups O(n) de jugadores, sin I/O en el hilo principal, y hologramas que solo se actualizan cuando el texto realmente cambio.

---

## Requirements

* PocketMine-MP 5.0+
* PHP 8.1+
* Opcional: BedrockEconomy o EconomyAPI, si quieres el top de Dinero activo

---

## Installation

1. Descarga `Tops.phar` desde `build/Tops.phar` (o compilalo tu mismo, ver mas abajo).
2. Colocalo en la carpeta `plugins/` de tu servidor.
3. Reinicia el servidor. Se generara `config.yml` en `plugins/Tops/`.
4. Si quieres el top de Dinero, instala BedrockEconomy o EconomyAPI y configura `economy.provider` en `config.yml`.

---

## Configuration

* `config.yml` - comportamiento: intervalos de actualizacion, tamano de cada top, proveedor de economia, decimales del top de Dinero, sufijos del tiempo jugado.
* `messages.yml` - todo lo visual y de texto, sin tocar codigo:
  * `titles` - titulo/color de cada categoria (`&` para colores).
  * `line-format` - formato de cada fila (`{pos}`, `{name}`, `{value}`).
  * `no-data-message` - texto cuando una categoria aun no tiene datos.
  * `prefix` - prefijo de todos los mensajes de comandos.
  * `spawned` / `despawned` / `not-found` / `reloaded` - mensajes de los comandos, con placeholders `{categoria}`/`{distancia}`.

Cambiar `economy.provider` o cualquier `*-interval-ticks` en `config.yml` requiere reiniciar el servidor. Todo `messages.yml` (colores, textos, formato de las filas) se recarga en caliente con `/tops reload`.

---

## Permissions

```yaml
tops.command.spawn      # Permite spawnear un holograma de top (default: op)
tops.command.despawn    # Permite eliminar un holograma de top (default: op)
tops.command.reload     # Permite recargar config.yml (default: op)
```

---

## Usage

### Commands

```bash
/tops spawn <categoria>     # categoria: kills | deaths | dinero | tiempo
/tops despawn <categoria>   # elimina el holograma de esa categoria mas cercano (10 bloques)
/tops reload                # recarga config.yml
```

**Aliases**: None

### How It Works

**Spawnear un top**:
1. Parate donde quieres el holograma.
2. Ejecuta `/tops spawn kills` (o `deaths`, `dinero`, `tiempo`).
3. El holograma aparece 2 bloques sobre tu posicion, mirando hacia donde miras.

**Actualizacion**:
1. Una tarea global recalcula los 4 tops cada `refresh-interval-ticks` (5s por defecto) via `AsyncTask`.
2. Cada holograma existente de esa categoria actualiza su texto solo si cambio.

---

## Data Tracking

* **Kills / Deaths**: se registran en `PlayerDeathEvent`. Toda muerte cuenta como Death; si el causante fue otro jugador, tambien cuenta como Kill suyo.
* **Tiempo jugado**: se mide por sesion (join → quit) y se persiste al salir, con un respaldo periodico (`playtime-flush-interval-ticks`) por si el servidor se cae sin apagarse limpio.
* **Dinero**: se sincroniza desde el proveedor de economia elegido, solo para jugadores conectados (repartido en lotes para no golpear al plugin de economia de una sola vez), y se guarda en la misma tabla SQLite que el resto de stats.

---

## Performance Optimization

### Architecture

* Un unico indice `HologramRegistry` (por `WeakReference`) resuelve que hologramas actualizar sin recorrer el mundo.
* `PlaytimeTracker` deriva el tiempo jugado en memoria (join timestamp) en vez de incrementar un contador cada segundo.
* Cada escritura a SQLite es un `UPSERT` atomico (`INSERT ... ON CONFLICT DO UPDATE`) — sin lecturas previas, sin locks manuales.
* Solo 3 tareas globales en todo el plugin (refresco de tops, flush de tiempo jugado, sync de dinero); ninguna por jugador ni por entidad.
* El sync de dinero se reparte en lotes (`money-sync-batch-size`) porque algunos plugins de economia resuelven el balance de forma sincrona.

### Benchmarks

* **Escritura por muerte**: 1 `UPSERT` async, ~200 jugadores no generan mas de unas pocas escrituras por minuto.
* **Recalculo de tops**: 4 `SELECT ... ORDER BY ... LIMIT` indexados sobre una tabla de <1000 filas, fuera del hilo principal.
* **TPS Impact**: nulo en pruebas locales — cero I/O sincrono, cero busquedas O(n) de jugadores, cero tasks por entidad.
* **Memory Overhead**: un `WeakReference` por holograma spawneado + un `int` por jugador conectado (sesion de tiempo jugado).

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
├── async/           # AsyncTask por operacion (init, increment, set money, fetch tops)
├── command/          # TopsCommand (Commando) + subcomandos + argumento de categoria
├── entity/           # TopsHologram (floating text)
├── format/           # TimeFormatter
├── hologram/         # HologramRegistry (indice O(1) por categoria)
├── listener/         # actividad de jugador + auto-registro de hologramas
└── task/             # las 3 tareas globales repetitivas
```

### Design Principles
* **Single Responsibility**: cada clase resuelve un problema concreto (sin `Manager` que solo envuelve un array).
* **Dependency Injection**: todo se inyecta por constructor; `Loader::getInstance()` solo se usa donde PM5 lo exige (callbacks de `AsyncTask::onCompletion()`).
* **Type Safety**: `strict_types=1` en todo el codigo, PHPStan nivel 9 sin supresiones.

---

## Troubleshooting

### El top de Dinero siempre muestra 0
* Revisa que `economy.provider` en `config.yml` coincida con el plugin instalado (`bedrockeconomy` o `economyapi`) y reinicia el servidor tras instalarlo.

### `/tops despawn` dice que no encuentra el holograma
* Debes estar a menos de 10 bloques del holograma y en el mismo mundo.

---

## Contributing

* **Code Quality**: sin managers vacios, sin arrays anidados sin tipar, early-returns en vez de anidacion.
* **Performance**: cualquier cambio debe justificar su costo en TPS (ver jerarquia de rendimiento en el codigo).
* **Type Safety**: PHPStan nivel 9 debe seguir pasando sin nuevas supresiones.

---

## License

This project is licensed under the Private License - see [LICENSE](LICENSE) file for details.

---

## Testing

* **PHPStan**: nivel 8 y nivel 9 verificados sin errores (`vendor/bin/phpstan analyse`).
* **Sintaxis**: todos los archivos de `src/Tops` pasan `php -l`.
* **Build**: `build/build.php` compila un `.phar` cargable end-to-end (verificado localmente).

---

## Updates & Improvements

### v1.1.0 (2026-08-20)
* Diseno de los hologramas 100% configurable (titulos, colores, formato de fila, mensaje sin datos, decimales, sufijos de tiempo).
* Mensajes de los comandos configurables con placeholders.

### v1.0.0 - Initial Release (2026-08-20)

**Core Features:**
* Tops de Kills, Deaths, Dinero y Tiempo jugado con holograma independiente por categoria.
* Persistencia SQLite 100% asincrona.
* Soporte de economia via libPiggyEconomy (BedrockEconomy / EconomyAPI).
* Comandos `/tops spawn|despawn|reload` con Commando.

**Technical Highlights:**
* PHPStan nivel 9 limpio.
* Cero tasks por entidad/jugador, cero I/O en el hilo principal.

### Posibles features futuras
- [ ] Soporte MySQL para redes multi-servidor (hoy es SQLite, pensado para un solo servidor).
- [ ] Top semanal/mensual con reinicio automatico de contadores.
- [ ] Placeholders para PlaceholderAPI (mostrar el top #1 de cada categoria en scoreboard/tab).
- [ ] Holograma "resumen" opcional con las 4 categorias en un solo cartel.
- [ ] Recompensas automaticas para el top 1 de cada categoria (via comando configurable).
- [ ] Soporte para XPProvider de libPiggyEconomy como quinta categoria opcional ("Experiencia").

---

## Version Support

| Version | Release Date | Status | Support |
|---------|-------------|--------|---------|
| 1.1.0 | 2026-08-20 | 🟢 Active | Full support |
| 1.0.0 | 2026-08-20 | ⚪ Superseded | - |

---

**Made with ❤️ by HVNS**
