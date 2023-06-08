<?php

declare(strict_types=1);

namespace NhanAZ\libRegRsp;

use pocketmine\plugin\PluginBase;
use pocketmine\resourcepacks\ResourcePack;
use pocketmine\resourcepacks\ZippedResourcePack;
use pocketmine\utils\Filesystem;
use Symfony\Component\Filesystem\Path;

class libRegRsp {

	private static ?ResourcePack $pack = null;

	public static function regRsp(PluginBase $plugin): void {
		$plugin->getLogger()->debug('Compiling resource pack');
		$zip = new \ZipArchive();
		$zip->open(Path::join($plugin->getDataFolder(), $plugin->getName() . '.mcpack'), \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

		foreach ($plugin->getResources() as $resource) {
			if ($resource->isFile() and str_contains($resource->getPathname(), $plugin->getName() . ' Pack')) {
				$relativePath = Path::normalize(preg_replace("/.*[\/\\\\]{$plugin->getName()}\hPack[\/\\\\].*/U", '', $resource->getPathname()));
				$plugin->saveResource(Path::join($plugin->getName() . ' Pack', $relativePath), false);
				$zip->addFile(Path::join($plugin->getDataFolder(), $plugin->getName() . ' Pack', $relativePath), $relativePath);
			}
		}

		$zip->close();
		Filesystem::recursiveUnlink(Path::join($plugin->getDataFolder() . $plugin->getName() . ' Pack'));
		$plugin->getLogger()->debug('Resource pack compiled');

		$plugin->getLogger()->debug('Registering resource pack');
		$plugin->getLogger()->debug('Resource pack compiled');
		self::$pack = $pack = new ZippedResourcePack(Path::join($plugin->getDataFolder(), $plugin->getName() . '.mcpack'));
		$manager = $plugin->getServer()->getResourcePackManager();

		$reflection = new \ReflectionClass($manager);

		$property = $reflection->getProperty("resourcePacks");
		$property->setAccessible(true);
		$currentResourcePacks = $property->getValue($manager);
		$currentResourcePacks[] = $pack;
		$property->setValue($manager, $currentResourcePacks);

		$property = $reflection->getProperty("uuidList");
		$property->setAccessible(true);
		$currentUUIDPacks = $property->getValue($manager);
		$currentUUIDPacks[mb_strtolower($pack->getPackId())] = $pack;
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

		if (isset($currentResourcePacks[mb_strtolower($pack->getPackId())])) {
			unset($currentUUIDPacks[mb_strtolower($pack->getPackId())]);
			$property->setValue($manager, $currentUUIDPacks);
		}
		$plugin->getLogger()->debug('Resource pack unregistered');

		unlink(Path::join($plugin->getDataFolder(), $plugin->getName() . '.mcpack'));
		$plugin->getLogger()->debug('Resource pack file deleted');
	}
}
