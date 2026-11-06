<?php

namespace App\Support;

use Composer\Script\Event;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\PackageManifest;

class ComposerScripts
{
    public static function discoverPackages(Event $event): void
    {
        $vendorDirectory = $event->getComposer()->getConfig()->get('vendor-dir');

        require_once $vendorDirectory.'/autoload.php';

        $projectRoot = dirname(__DIR__, 2);
        $packageManifest = new PackageManifest(
            new Filesystem,
            $projectRoot,
            $projectRoot.'/bootstrap/cache/packages.php'
        );

        $packageManifest->build();
    }
}
