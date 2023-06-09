<?php

/**
 * Written by PocketAI (A revolutionary AI for PocketMine-MP plugin developing)
 *
 * @copyright 2023
 *
 * This file was refactored by PocketAI (A revolutionary AI for PocketMine-MP plugin developing)
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
                $cooldownDuration = Snipers::getInstance()->getConfig()->get("ammo-cooldown", 4);

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

                Snipers::getInstance()->handleSpyglassAnimation($player);

                $this->updateLastUsageTime($cooldownKey);
            }
        }
    }

    public function onPlayerDeath(PlayerDeathEvent $event): void {
        $player = $event->getPlayer();
        $cause = $player->getLastDamageCause();

        if (!Snipers::getInstance()->getConfig()->get("custom-death-messages", true)) {
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

}