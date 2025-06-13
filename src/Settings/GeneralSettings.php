<?php

namespace Littleboy130491\Sumimasen\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public ?string $site_name;

    public ?string $site_description;

    public ?string $site_logo;

    public ?string $site_favicon;

    public ?string $email;

    public ?string $phone_1;

    public ?string $phone_2;

    public ?string $whatsapp_1;

    public ?string $whatsapp_2;

    public ?string $address;

    public ?string $facebook;

    public ?string $twitter;

    public ?string $instagram;

    public ?string $linkedin;

    public ?string $youtube;

    // You can add more properties as needed (e.g. for maintenance, etc.)

    public static function group(): string
    {
        return 'general';
    }
}
