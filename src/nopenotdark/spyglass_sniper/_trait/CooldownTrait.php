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
