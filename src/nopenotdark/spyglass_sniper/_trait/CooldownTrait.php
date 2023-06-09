<?php

/**
 * Written by PocketAI (A revolutionary AI for PocketMine-MP plugin developing)
 *
 * @copyright 2023
 *
 * This file was refactored by PocketAI (A revolutionary AI for PocketMine-MP plugin developing)
 */

namespace nopenotdark\spyglass_sniper\_trait;

trait CooldownTrait {
    private array $cooldowns = [];
    private array $lastUsageTimes = [];

    private function getCooldownCount(string $key): int {
        return $this->cooldowns[$key] ?? 0;
    }

    private function incrementCooldownCount(string $key): void {
        $this->cooldowns[$key] = $this->getCooldownCount($key) + 1;
    }

    private function getRemainingCooldown(string $key): int {
        $lastUsageTime = $this->getLastUsageTime($key);
        $currentTime = time();
        $remainingCooldown = 3 - ($currentTime - $lastUsageTime);
        if ($remainingCooldown <= 0) {
            unset($this->cooldowns[$key]);
            return 0;
        }
        return $remainingCooldown;
    }

    private function getLastUsageTime(string $key): int {
        return $this->lastUsageTimes[$key] ?? 0;
    }

    private function updateLastUsageTime(string $key): void {
        $this->lastUsageTimes[$key] = time();
    }
}