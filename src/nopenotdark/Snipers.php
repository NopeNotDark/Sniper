<?php

/**
 * Written by PocketAI (A revolutionary AI for PocketMine-MP plugin developing)
 *
 * @copyright 2023
 *
 * This file was refactored by PocketAI (A revolutionary AI for PocketMine-MP plugin developing)
 */

namespace nopenotdark;

use NhanAZ\libRegRsp\libRegRsp;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;

final class Snipers extends PluginBase {
    use SingletonTrait;

    public function onEnable() : void {
        self::setInstance($this);
        libRegRsp::regRsp($this);
        $this->saveDefaultConfig();
        $this->getServer()->getPluginManager()->registerEvents(new SnipersListener(), $this);
    }

    public function playAWPSound(Player $player): void {
        $pk = PlaySoundPacket::create(
            "awp.sound",
            $player->getEyePos()->getX(),
            $player->getEyePos()->getY(),
            $player->getEyePos()->getZ(),
            0.5,
            1
        );
        $player->getNetworkSession()->sendDataPacket($pk);
    }

    public function calcDistance(Player $player, Vector3 $direction, int $i): Vector3 {
        return new Vector3(
            $player->getEyePos()->getX() + $direction->x * $i,
            $player->getEyePos()->getY(),
            $player->getEyePos()->getZ() + $direction->z * $i
        );
    }

    public function calcProjectile(Player $player, Vector3 $direction, int $i, int $duration, float $gravity): Vector3 {
        return new Vector3(
            $player->getEyePos()->getX() + $direction->x * $i,
            $player->getEyePos()->getY() - (($i - $duration) * ($i - $duration) * $gravity) / 2,
            $player->getEyePos()->getZ() + $direction->z * $i
        );
    }

    public function attackEntities(Player $player, Vector3 $pos): void {
        $backfireEnabled = $this->getConfig()->get("backfire", false);

        foreach ($player->getWorld()->getEntities() as $entity) {
            if ($entity === $player) {
                if ($backfireEnabled && $this->shouldBackfire()) {
                    $entity->attack(new EntityDamageEvent($player, EntityDamageEvent::CAUSE_PROJECTILE, 100));
                    $player->sendMessage(TextFormat::RED . "Watch out! The sniper backfired and killed you!");
                }
                return;
            }

            $entityPos = $entity->getPosition();
            if ($entityPos->getX() <= $pos->getX() && $entityPos->getY() <= $pos->getY() && $entityPos->getZ() <= $pos->getZ()) {
                $entity->attack(new EntityDamageEvent($player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, 100));
            }
        }
    }

    private function shouldBackfire(): bool {
        $randomChance = mt_rand(1, 350);
        $randomChance2 = mt_rand(1, 350);
        return $randomChance === $randomChance2;
    }

}