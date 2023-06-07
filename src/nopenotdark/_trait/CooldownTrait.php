<?php

/**
 * Written by PocketAI (A revolutionary AI for PocketMine-MP plugin developing)
 *
 * @copyright 2023
 *
 * This file was refactored by PocketAI (A revolutionary AI for PocketMine-MP plugin developing)
 */

namespace nopenotdark\_trait;

trait CooldownTrait {
    private array $cooldowns = [];
    private array $lastUsageTimes = [];

    private function getCooldownCount(string $key): int {
        $cooldowns = $this->cooldowns;
        return $cooldowns[$key] ?? 0;
    }

    private function incrementCooldownCount(string $key): void {
        $cooldowns = $this->cooldowns;
        $cooldowns[$key] = $this->getCooldownCount($key) + 1;
        $this->cooldowns = $cooldowns;
    }

    private function getRemainingCooldown(string $key): int {
        $lastUsageTime = $this->getLastUsageTime($key);
        $currentTime = time();
        $remainingCooldown = 3 - ($currentTime - $lastUsageTime);
        $remCooldown = $remainingCooldown > 0 ? $remainingCooldown : 0;
        if ($remCooldown <= 0) unset($this->cooldowns[$key]);
        return $remainingCooldown > 0 ? $remainingCooldown : 0;
    }

    private function getLastUsageTime(string $key): int {
        $lastUsageTimes = $this->lastUsageTimes;
        return $lastUsageTimes[$key] ?? 0;
    }

    private function updateLastUsageTime(string $key): void {
        $lastUsageTimes = $this->lastUsageTimes;
        $lastUsageTimes[$key] = time();
        $this->lastUsageTimes = $lastUsageTimes;
    }
}