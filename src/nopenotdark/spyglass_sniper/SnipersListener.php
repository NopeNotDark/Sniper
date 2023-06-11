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

use nopenotdark\spyglass_sniper\_trait\CooldownTrait;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwnedTrait;
use pocketmine\utils\TextFormat as C;

final class SnipersListener implements Listener {
    use CooldownTrait;

    public function onDataReceive(DataPacketReceiveEvent $event): void {
        $player = $event->getOrigin()->getPlayer();
        $packet = $event->getPacket();

        if ($packet instanceof AnimatePacket && $packet->action === AnimatePacket::ACTION_SWING_ARM) {
            $item = $player->getInventory()->getItemInHand();

            if ($item->getTypeId() == ItemTypeIds::SPYGLASS) {
                $cooldownKey = $player->getName() . '_spyglass_cooldown';

                $currentTime = time();
                $lastUsageTime = $this->getLastUsageTime($cooldownKey);
                $cooldownDuration = $this->getOwningPlugin()->getConfig()->get("ammo-cooldown", 4);

                if ($currentTime - $lastUsageTime < $cooldownDuration) {
                    $remainingCooldown = $cooldownDuration - ($currentTime - $lastUsageTime);
                    $player->sendMessage(C::RED . "The sniper is currently on cooldown for " . $remainingCooldown . " seconds.");
                    return;
                }

                $ammo = $player->getInventory()->all(VanillaItems::GOLD_NUGGET());

                if (empty($ammo)) {
                    $player->sendMessage(C::RED . "You don't have enough ammo (gold_nugget) to shoot this sniper.");
                    return;
                }

                foreach ($ammo as $slot => $item) {
                    $player->getInventory()->setItem($slot, $item->setCount($item->getCount() - 1));
                    break;
                }

                $this->getOwningPlugin()->handleSpyglassAnimation($player);

                $this->updateLastUsageTime($cooldownKey);
            }
        }
    }

    public function onPlayerDeath(PlayerDeathEvent $event): void {
        $player = $event->getPlayer();
        $cause = $player->getLastDamageCause();

        if (!$this->getOwningPlugin()->getConfig()->get("custom-death-messages", true)) {
            return;
        }
        $deathMessage = C::RED . $player->getName() . " ";
        switch ($cause->getCause()) {
            case EntityDamageEvent::CAUSE_ENTITY_ATTACK:
                $damager = $cause->getEntity();
                if ($damager instanceof Player) {
                    $deathMessage .= "was sniped by " . $damager->getName();
                }
                break;
            case EntityDamageEvent::CAUSE_PROJECTILE:
                $deathMessage .= "'s gun backfired, causing them to die!";
                break;
            case EntityDamageEvent::CAUSE_FALL:
                $deathMessage .= "fell to their death!";
                break;
            case EntityDamageEvent::CAUSE_VOID:
                $deathMessage .= "fell into the void!";
                break;
        }
        $event->setDeathMessage($deathMessage);
    }

    public function getOwningPlugin(): Snipers {
        return Snipers::getInstance();
    }

}