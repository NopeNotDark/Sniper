<?php

/**
 * Generated from Github under PocketAI Token
 *
 * @copyright 2023
 *
 * This file was refactored by PocketAI (A revolutionary AI for PocketMine-MP plugin developing)
 */

declare(strict_types=1);

namespace NhanAZ\libRegRsp;

use pocketmine\plugin\PluginBase;
use pocketmine\resourcepacks\ResourcePack;
use pocketmine\resourcepacks\ZippedResourcePack;
use pocketmine\utils\Filesystem;
use Symfony\Component\Filesystem\Path;

final class libRegRsp {
    private static ?ResourcePack $pack = null;

    public static function regRsp(PluginBase $plugin): void {
        $plugin->getLogger()->debug('Compiling resource pack');
        $packPath = Path::join($plugin->getDataFolder(), $plugin->getName() . '.mcpack');
        $packFolder = $plugin->getName() . ' Pack';
        $zip = new \ZipArchive();
        $zip->open($packPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($plugin->getResources() as $resource) {
            $path = $resource->getPathname();
            if ($resource->isFile() && str_contains($path, $packFolder)) {
                $relativePath = Path::normalize(preg_replace("/.*[\/\\\\]{$packFolder}[\/\\\\].*/U", '', $path));
                $plugin->saveResource(Path::join($packFolder, $relativePath), false);
                $zip->addFile(Path::join($plugin->getDataFolder(), $packFolder, $relativePath), $relativePath);
            }
        }

        $zip->close();
        Filesystem::recursiveUnlink(Path::join($plugin->getDataFolder(), $packFolder));
        $plugin->getLogger()->debug('Resource pack compiled');

        $plugin->getLogger()->debug('Registering resource pack');
        self::$pack = new ZippedResourcePack($packPath);
        $manager = $plugin->getServer()->getResourcePackManager();
        $reflection = new \ReflectionClass($manager);

        $property = $reflection->getProperty("resourcePacks");
        $property->setAccessible(true);
        $currentResourcePacks = $property->getValue($manager);
        $currentResourcePacks[] = self::$pack;
        $property->setValue($manager, $currentResourcePacks);

        $property = $reflection->getProperty("uuidList");
        $property->setAccessible(true);
        $currentUUIDPacks = $property->getValue($manager);
        $currentUUIDPacks[mb_strtolower(self::$pack->getPackId())] = self::$pack;
        $property->setValue($manager, $currentUUIDPacks);

        $property = $reflection->getProperty("serverForceResources");
        $property->setAccessible(true);
        $property->setValue($manager, true);

        $plugin->getLogger()->debug('Resource pack registered');
    }

    public static function unRegRsp(PluginBase $plugin): void {
        $manager = $plugin->getServer()->getResourcePackManager();
        $pack = self::$pack;
        $reflection = new \ReflectionClass($manager);

        $property = $reflection->getProperty("resourcePacks");
        $property->setAccessible(true);
        $currentResourcePacks = $property->getValue($manager);
        $key = array_search($pack, $currentResourcePacks, true);

        if ($key !== false) {
            unset($currentResourcePacks[$key]);
            $property->setValue($manager, $currentResourcePacks);
        }

        $property = $reflection->getProperty("uuidList");
        $property->setAccessible(true);
        $currentUUIDPacks = $property->getValue($manager);

        if (isset($currentUUIDPacks[mb_strtolower($pack->getPackId())])) {
            unset($currentUUIDPacks[mb_strtolower($pack->getPackId())]);
            $property->setValue($manager, $currentUUIDPacks);
        }

        $plugin->getLogger()->debug('Resource pack unregistered');
        unlink(Path::join($plugin->getDataFolder(), $plugin->getName() . '.mcpack'));
        $plugin->getLogger()->debug('Resource pack file deleted');
    }
}