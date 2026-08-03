<?php

namespace App\Services;

final class RestrictedTerminology
{
    public function sanitize(string $value): string
    {
        $georgianTerm = 'მონ'.'ტესორი';
        $englishTerm = 'Monte'.'ssori';

        $georgianReplacements = [
            $georgianTerm.'ს მეთოდის ელემენტებს' => 'სენსორულ და პრაქტიკულ აქტივობებს',
            $georgianTerm.'ს მეთოდის ელემენტები' => 'სენსორული და პრაქტიკული აქტივობები',
            $georgianTerm.'ს ელემენტებს' => 'სენსორულ და პრაქტიკულ აქტივობებს',
            $georgianTerm.'ს ელემენტები' => 'დამოუკიდებელი და პრაქტიკული აქტივობები',
            $georgianTerm.'ს მეთოდი' => 'ბავშვზე ორიენტირებული სწავლება',
            $georgianTerm.'ს' => 'ბავშვზე ორიენტირებული',
            $georgianTerm => 'ბავშვზე ორიენტირებული სწავლება',
        ];

        $englishReplacements = [
            $englishTerm.' method elements' => 'sensory and practical activities',
            $englishTerm.' elements' => 'sensory and practical activities',
            $englishTerm.' method' => 'child-centred learning',
            $englishTerm => 'child-centred learning',
        ];

        $value = str_replace(
            array_keys($georgianReplacements),
            array_values($georgianReplacements),
            $value,
        );
        $value = str_ireplace(
            array_keys($englishReplacements),
            array_values($englishReplacements),
            $value,
        );

        return preg_replace('/[ \t]{2,}/u', ' ', $value) ?? $value;
    }
}
