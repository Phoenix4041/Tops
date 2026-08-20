<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$vendorCommando = $root . '/vendor/cortexpe/commando/src/CortexPE';
$vendorPiggy = $root . '/vendor/dapigguy/libpiggyeconomy/src/DaPigGuy';

if (!is_dir($vendorCommando) || !is_dir($vendorPiggy)) {
	fwrite(STDERR, "Faltan dependencias de composer. Corre primero:\n  composer install --ignore-platform-reqs\n");
	exit(1);
}

function tops_remove_directory(string $dir): void {
	if (!is_dir($dir)) {
		return;
	}
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	foreach ($iterator as $item) {
		$item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
	}
	rmdir($dir);
}

function tops_copy_directory(string $from, string $to): void {
	tops_remove_directory($to);
	mkdir($to, 0777, true);
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::SELF_FIRST
	);
	foreach ($iterator as $item) {
		$target = $to . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
		if ($item->isDir()) {
			if (!is_dir($target)) {
				mkdir($target, 0777, true);
			}
		} else {
			copy($item->getPathname(), $target);
		}
	}
}

// Merges the two virions' source into src/ so DevTools' single-src-tree phar builder finds them.
echo "Fusionando Commando y libPiggyEconomy en src/...\n";
tops_copy_directory($vendorCommando, $root . '/src/CortexPE');
tops_copy_directory($vendorPiggy, $root . '/src/DaPigGuy');

$outPhar = $root . '/build/Tops.phar';
$consoleScript = __DIR__ . '/tools/ConsoleScript.php';

$cmd = implode(' ', [
	escapeshellarg(PHP_BINARY),
	'-dphar.readonly=0',
	escapeshellarg($consoleScript),
	'--make', escapeshellarg('src,resources,stub.php,plugin.yml,config.yml'),
	'--relative', escapeshellarg($root),
	'--out', escapeshellarg($outPhar),
]);

echo "Compilando el phar...\n";
passthru($cmd, $exitCode);

if ($exitCode !== 0 || !is_file($outPhar)) {
	fwrite(STDERR, "Fallo la compilacion del phar.\n");
	exit(1);
}

echo 'Listo: ' . $outPhar . ' (' . round(filesize($outPhar) / 1024, 1) . " KB)\n";
