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
use pocketmine\color\Color;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\DustParticle;

final class SnipersListener implements Listener {
    use CooldownTrait;

    public function onDataReceive(DataPacketReceiveEvent $event): void {
        $player = $event->getOrigin()->getPlayer();
        $packet = $event->getPacket();

        if ($packet instanceof AnimatePacket && $packet->action === AnimatePacket::ACTION_SWING_ARM) {
            $itemInHand = $player->getInventory()->getItemInHand();
            if ($itemInHand->getTypeId() !== ItemTypeIds::SPYGLASS) {
                return;
            }

            $cooldownKey = "awp_cooldown_" . $player->getName();
            $cooldownCount = $this->getCooldownCount($cooldownKey);

            if ($cooldownCount >= 4) {
                $remainingCooldown = $this->getRemainingCooldown($cooldownKey);
                $player->sendMessage(TextFormat::RED . "AWP is on cooldown. Please wait " . $remainingCooldown . " seconds before shooting again.");
                return;
            }

            $goldNuggets = $player->getInventory()->all(VanillaItems::GOLD_NUGGET());
            if (count($goldNuggets) === 0) {
                $player->sendMessage(TextFormat::RED . "You don't have enough gold nuggets to shoot this sniper!");
                return;
            }

            $inventory = $player->getInventory();
            foreach ($goldNuggets as $index => $item) {
                $goldNugget = $item;
                if ($goldNugget instanceof Item && $goldNugget->getTypeId() === ItemTypeIds::GOLD_NUGGET) {
                    $goldNugget->setCount($goldNugget->getCount() - 1);
                    $inventory->setItem($index, $goldNugget);
                }
            }

            $direction = $player->getDirectionVector();
            $particle = new DustParticle(new Color(255, 220, 220));
            $gravity = 0.05;
            $duration = rand(8, 10);
            Snipers::getInstance()->playAWPSound($player);
            for ($i = 1; $i < $duration; $i++) {
                $pos = Snipers::getInstance()->calcDistance($player, $direction, $i);
                $player->getWorld()->addParticle($pos, $particle);
                Snipers::getInstance()->attackEntities($player, $pos);
            }
            for ($i = $duration; $i < 100; $i++) {
                $pos = Snipers::getInstance()->calcProjectile($player, $direction, $i, $duration, $gravity);
                $player->getWorld()->addParticle($pos, $particle);
                Snipers::getInstance()->attackEntities($player, $pos);
            }

            $this->incrementCooldownCount($cooldownKey);
            $this->updateLastUsageTime($cooldownKey);
        }
    }

    public function onPlayerDeath(PlayerDeathEvent $event): void {
        $player = $event->getPlayer();
        $cause = $player->getLastDamageCause();

        if (!Snipers::getInstance()->getConfig()->get("custom-death-messages", true)) {
            return;
        }
        $deathMessage = TextFormat::RED . $player->getName() . " ";
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