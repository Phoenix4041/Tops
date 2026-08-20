# Changelog

Todas las versiones notables de Tops se documentan aqui.

## [1.0.0] - 2026-08-20

### Added
- Top de Kills, Deaths, Dinero y Tiempo jugado, cada uno con su propio holograma.
- Persistencia SQLite 100% asincrona (`AsyncTask` para init de schema, incrementos, set de dinero y fetch de tops).
- Soporte de economia via libPiggyEconomy, con BedrockEconomy y EconomyAPI como proveedores seleccionables por config.
- Comandos `/tops spawn <categoria>`, `/tops despawn <categoria>` y `/tops reload`, construidos con Commando.
- Sync de dinero por lotes para jugadores conectados, evitando picos de lag con plugins de economia sincronos.
- Flush periodico de tiempo jugado como respaldo ante caidas del servidor.

### Technical
- PHPStan nivel 8 y nivel 9 sin errores.
- `strict_types=1` en todo el codigo.
- Script de build (`build/build.php`) que fusiona los virions (Commando, libPiggyEconomy) y compila el `.phar` con DevTools.
