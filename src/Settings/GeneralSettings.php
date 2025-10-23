<?php

namespace Littleboy130491\Sumimasen\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public ?string $site_name = null;

    public ?string $site_description = null;

    public ?string $site_logo = null;

    public ?string $site_favicon = null;

    public ?string $email = null;

    public ?string $phone_1 = null;

    public ?string $phone_2 = null;

    public ?string $whatsapp_1 = null;

    public ?string $whatsapp_2 = null;

    public ?string $address = null;

    public ?string $facebook = null;

    public ?string $twitter = null;

    public ?string $instagram = null;

    public ?string $linkedin = null;

    public ?string $youtube = null;

    public ?string $custom_code_head = null;

    public ?string $custom_code_body = null;

    public static function group(): string
    {
        return 'general';
    }
}
