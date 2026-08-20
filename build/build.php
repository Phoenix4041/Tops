<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$virions = [
	$root . '/vendor/cortexpe/commando/src/CortexPE' => $root . '/src/CortexPE',
	$root . '/vendor/dapigguy/libpiggyeconomy/src/DaPigGuy' => $root . '/src/DaPigGuy',
	$root . '/vendor/muqsit/simple-packet-handler/src/muqsit' => $root . '/src/muqsit',
];

foreach ($virions as $vendorPath => $_) {
	if (!is_dir($vendorPath)) {
		fwrite(STDERR, "Faltan dependencias de composer. Corre primero:\n  composer install --ignore-platform-reqs\n");
		exit(1);
	}
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

// Merges every virion's source into src/ so DevTools' single-src-tree phar builder finds them.
echo "Fusionando virions en src/...\n";
foreach ($virions as $vendorPath => $targetPath) {
	tops_copy_directory($vendorPath, $targetPath);
}

$outPhar = $root . '/build/Tops.phar';
$consoleScript = __DIR__ . '/tools/ConsoleScript.php';

$cmd = implode(' ', [
	escapeshellarg(PHP_BINARY),
	'-dphar.readonly=0',
	escapeshellarg($consoleScript),
	'--make', escapeshellarg('src,resources,stub.php,plugin.yml'),
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
