<?php

namespace Littleboy130491\Sumimasen\View\Components\Ui;

use Illuminate\View\Component;

/**
 * Social Share Blade Component
 *
 * Usage example:
 *
 * <x-social-share
 *     :url="request()->fullUrl()"
 *     :title="$post->title"
 *     :description="$post->excerpt ?? null"
 * />
 *
 * @property string $url         The URL to share.
 * @property string $title       The title of the page/content.
 * @property string|null $description Optional description (for some platforms).
 */

class SocialShare extends Component
{
    public string $url;
    public string $title;
    public ?string $description;

    public function __construct(string $url, string $title, ?string $description = null)
    {
        $this->url = $url;
        $this->title = $title;
        $this->description = $description;
    }

    public function render()
    {
        return view('components.ui.social-share');
    }

    public function socialLinks(): array
    {
        $site = config('cms.site_social_media');
        $url = urlencode($this->url);
        $title = urlencode($this->title);

        return [
            'facebook' => [
                'enabled' => !empty($site['facebook']),
                'share_url' => "https://www.facebook.com/sharer/sharer.php?u={$url}",
                'profile_url' => $site['facebook'] ?? '#'
            ],
            'twitter' => [
                'enabled' => !empty($site['twitter']),
                'share_url' => "https://twitter.com/intent/tweet?url={$url}&text={$title}",
                'profile_url' => $site['twitter'] ?? '#'
            ],
            'linkedin' => [
                'enabled' => !empty($site['linkedin']),
                'share_url' => "https://www.linkedin.com/shareArticle?url={$url}&title={$title}",
                'profile_url' => $site['linkedin'] ?? '#'
            ],
            'whatsapp' => [
                'enabled' => !empty($site['whatsapp']),
                'share_url' => "https://wa.me/?text={$title}%20{$url}",
                'profile_url' => null
            ],

        ];
    }
}
