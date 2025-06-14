<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.site_name', config('cms.site_name', 'Clean CMS'));
        $this->migrator->add('general.site_description', config('cms.site_description', 'A modern content management system'));
        $this->migrator->add('general.site_logo', config('cms.site_logo'));
        $this->migrator->add('general.site_favicon', config('cms.site_favicon'));
        $this->migrator->add('general.email', config('cms.site_email', 'admin@example.com'));
        $this->migrator->add('general.phone_1', config('cms.site_phone_1'));
        $this->migrator->add('general.phone_2', config('cms.site_phone_2'));
        $this->migrator->add('general.whatsapp_1', config('cms.site_whatsapp_1'));
        $this->migrator->add('general.whatsapp_2', config('cms.site_whatsapp_2'));
        $this->migrator->add('general.address', config('cms.site_address'));
        $this->migrator->add('general.facebook', config('cms.site_social_media.facebook'));
        $this->migrator->add('general.twitter', config('cms.site_social_media.twitter'));
        $this->migrator->add('general.instagram', config('cms.site_social_media.instagram'));
        $this->migrator->add('general.linkedin', config('cms.site_social_media.linkedin'));
        $this->migrator->add('general.youtube', config('cms.site_social_media.youtube'));
    }

    public function down(): void
    {
        $this->migrator->delete('general.site_name');
        $this->migrator->delete('general.site_description');
        $this->migrator->delete('general.site_logo');
        $this->migrator->delete('general.site_favicon');
        $this->migrator->delete('general.email');
        $this->migrator->delete('general.phone_1');
        $this->migrator->delete('general.phone_2');
        $this->migrator->delete('general.whatsapp_1');
        $this->migrator->delete('general.whatsapp_2');
        $this->migrator->delete('general.address');
        $this->migrator->delete('general.facebook');
        $this->migrator->delete('general.twitter');
        $this->migrator->delete('general.instagram');
        $this->migrator->delete('general.linkedin');
        $this->migrator->delete('general.youtube');
    }
};
