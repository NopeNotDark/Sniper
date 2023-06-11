<?php

/**
 * `7MM"""Mq.                 `7MM              mm        db     `7MMF'
 *   MM   `MM.                  MM              MM       ;MM:      MM
 *   MM   ,M9 ,pW"Wq.   ,p6"bo  MM  ,MP.gP"Ya mmMMmm    ,V^MM.     MM
 *   MMmmdM9 6W'   `Wb 6M'  OO  MM ;Y ,M'   Yb  MM     ,M  `MM     MM
 *   MM      8M     M8 8M       MM;Mm 8M""""""  MM     AbmmmqMA    MM
 *   MM      YA.   ,A9 YM.    , MM `MbYM.    ,  MM    A'     VML   MM
 * .JMML.     `Ybmd9'   YMbmd'.JMML. YA`Mbmmd'  `Mbm.AMA.   .AMMA.JMML.
 *
 * This file was generated using PocketAI, Branch Stable, V6.20.1
 *
 * PocketAI is private software: You can redistribute the files under
 * the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or (at your option)
 * any later version.
 *
 * This plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this file.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @ai-profile: NopeNotDark
 * @copyright 2023
 * @authors NopeNotDark, SantanasWrld
 * @link https://thedarkproject.net/pocketai
 *
 */

namespace nopenotdark\spyglass_sniper;

use NhanAZ\libRegRsp\libRegRsp;
use pocketmine\color\Color;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Snowball;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\StringToItemParser;
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

    private array $shootableBlocks;

    public function onEnable(): void {
        self::setInstance($this);
        libRegRsp::regRsp($this);
        $this->saveDefaultConfig();
        $this->getServer()->getPluginManager()->registerEvents(new SnipersListener(), $this);

        foreach ($this->getConfig()->get("shootable-blocks", []) as $blockName) {
            $this->shootableBlocks[] = StringToItemParser::getInstance()->parse($blockName)->getTypeId();
        }
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
        $blockAt = $player->getWorld()->getBlockAt($dustPosition->getX(), $dustPosition->getY(), $dustPosition->getZ());

        if (!in_array($blockAt->getTypeId(), $this->shootableBlocks)) {
            return;
        }

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

        foreach ($entities as $entity) {
            if (!$entity instanceof Living) {
                continue;
            }

            $entityHeight = $entity->getSize()->getHeight();
            $hitDistance = $dustPosition->distance($entity->getLocation());

            if ($hitDistance <= $entityHeight) {
                $entityHeadY = $entity->getLocation()->getY() + $entityHeight - $entity->getEyeHeight();

                if ($dustPosition->getY() >= $entityHeadY) {
                    // Headshot
                    $event = new EntityDamageEvent($player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, 21 * $damageMultiplier);
                } else {
                    // Bodyshot
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

        if (!$playerAttacked && $this->getConfig()->get("backfire")) {
            $player->sendMessage(C::RED . "Your gun malfunctioned and your hand was blown off.");
        }
    }

    public function getBackfireBool(): bool {
        $first = mt_rand(0, 300);
        $second = mt_rand(0, 300);

        if ($first == $second) {
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