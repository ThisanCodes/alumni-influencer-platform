<?php
// app/Validation/UniversityEmailRules.php

namespace App\Validation;

class UniversityEmailRules
{
    public function university_email(string $value): bool 
    {
        $allowedDomains = ['iit.ac.lk'];
        
        $atPosition = strrchr($value, '@');
        if ($atPosition === false) {
            return false;
        }
        
        $domain = strtolower(substr($atPosition, 1));

        return in_array($domain, $allowedDomains, true);
    }
}