<?php

/**
 * Written by PocketAI (A revolutionary AI for PocketMine-MP plugin developing)
 *
 * @copyright 2023
 *
 * This file was refactored by PocketAI (A revolutionary AI for PocketMine-MP plugin developing)
 */

namespace nopenotdark\spyglass_sniper;

use NhanAZ\libRegRsp\libRegRsp;
use pocketmine\color\Color;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Snowball;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat as C;
use pocketmine\world\particle\DustParticle;

final class Snipers extends PluginBase {
    use SingletonTrait;

    public function onEnable() : void {
        self::setInstance($this);
        libRegRsp::regRsp($this);
        $this->saveDefaultConfig();
        $this->getServer()->getPluginManager()->registerEvents(new SnipersListener(), $this);
    }

    public function handleSpyglassAnimation(Player $player): void {
        $yaw = $player->getLocation()->getYaw();
        $pitch = $player->getLocation()->getPitch();
        $vector = Snipers::getInstance()->calculateVector($yaw, $pitch);

        $dustCount = $this->getConfig()->get("shot-range", 100);
        $dustSpacing = 0.2;
        $dustParticle = new DustParticle(new Color(255, 255, 255));

        Snipers::getInstance()->playSound($player);

        $snowBall = new Snowball(Location::fromObject($player->getEyePos(), $player->getWorld()), $player);
        $snowBall->setMotion($player->getDirectionVector()->multiply(10));
        $snowBall->spawnToAll();

        for ($i = 0; $i < $dustCount; $i++) {
            $dustPosition = $player->getEyePos()->add(
                $vector->getX() * $dustSpacing * $i,
                $vector->getY() * $dustSpacing * $i,
                $vector->getZ() * $dustSpacing * $i
            );
            $player->getWorld()->addParticle($dustPosition, $dustParticle);

            $this->handleEntityDamage($player, $dustPosition);
        }
    }

    public function handleEntityDamage(Player $player, Vector3 $dustPosition): void {
        $boundingBox = new AxisAlignedBB(
            $dustPosition->getX() - 0.2,
            $dustPosition->getY() - 0.2,
            $dustPosition->getZ() - 0.2,
            $dustPosition->getX() + 0.2,
            $dustPosition->getY() + 0.2,
            $dustPosition->getZ() + 0.2
        );

        $entities = $player->getWorld()->getNearbyEntities($boundingBox);

        $damageMultiplier = 1;
        $playerAttacked = false;

        if (!empty($entities)) {
            foreach ($entities as $entity) {
                if (!$entity instanceof Living) {
                    continue;
                }

                $entityHeight = $entity->getSize()->getHeight();
                $hitDistance = $dustPosition->distance($entity->getLocation());
                if ($hitDistance <= $entityHeight) {
                    $entityHeadY = $entity->getLocation()->getY() + $entityHeight - $entity->getEyeHeight();

                    if ($dustPosition->getY() >= $entityHeadY) {
                        // Loop sends message to player, loop ends, but still sends no clue why ???
                        // $player->sendMessage(C::GREEN . "+" . C::DARK_GREEN . "1" . C::GREEN . " Headshot");
                        $event = new EntityDamageEvent($player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, 21 * $damageMultiplier);
                    } else {
                        // Loop sends message to player, loop ends, but still sends no clue why ???
                        // $player->sendMessage(C::GREEN . "+" . C::DARK_GREEN . "1" . C::YELLOW . " Bodyshot");
                        $event = new EntityDamageEvent($player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, 20 / 5 * $damageMultiplier);
                    }
                } else {
                    // Missed Shot
                    continue;
                }

                if ($player === $entity) {
                    if ($this->getConfig()->get("backfire")) {
                        if ($this->getBackfireBool()) {
                            $player->sendMessage(C::RED . "Your gun malfunctioned and your hand was blown off.");
                            $entity->attack($event);
                            $playerAttacked = true;
                            break;
                        }
                    }
                    $playerAttacked = true;
                    break;
                }

                $entity->attack($event);
                $damageMultiplier -= 0.2;
                if ($damageMultiplier < 0.2) {
                    break;
                }
            }
        }

        if (!$playerAttacked && $this->getConfig()->get("backfire")) {
            $player->sendMessage(C::RED . "Your gun malfunctioned and your hand was blown off.");
        }
    }

    public function getBackfireBool(): bool {
        $first = mt_rand(0, 300);
        $second = mt_rand(0, 300);
        if ($first === $second) {
            return true;
        }
        return false;
    }

    public function playSound(Player $player): void {
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

    public function calculateVector(float $yaw, float $pitch): Vector3 {
        $x = -sin($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI);
        $y = -sin($pitch / 180 * M_PI);
        $z = cos($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI);
        return new Vector3($x, $y, $z);
    }


}
