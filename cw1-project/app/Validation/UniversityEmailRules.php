<?php
// app/Validation/UniversityEmailRules.php

namespace App\Validation;

class UniversityEmailRules
{
    public function university_email(string $value): bool 
    {
        $allowedDomains = ['iit.ac.lk'];
 
        $domain = strtolower(substr(strrchr($value, '@'), 1));

        if (! in_array($domain, $allowedDomains)) {
            return false;
        }

        return true;
    }
}