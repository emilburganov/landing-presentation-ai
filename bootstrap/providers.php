<?php

use App\Providers\AppServiceProvider;
use App\Services\AI\AIServiceProvider;
use App\Services\Contact\ContactServiceProvider;

return [
    AppServiceProvider::class,
    AIServiceProvider::class,
    ContactServiceProvider::class,
];
