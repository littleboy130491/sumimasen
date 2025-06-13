<?php

namespace Littleboy130491\Sumimasen\View\Components;

use Illuminate\Support\Facades\Cache;
use Illuminate\View\Component;
use Yizack\InstagramFeed as IGfeed;

/**
 * Class InstagramFeed
 */
class InstagramFeed extends Component
{
    public $feeds;

    public $type;

    public $columns;

    public $limit;

    public $showCaption;

    public $showLikes;

    public $showTimestamp;

    /**
     * @param  string  $type
     * @param  int  $columns
     * @param  int|null  $limit
     * @param  bool  $showCaption
     * @param  bool  $showLikes
     * @param  bool  $showTimestamp
     */
    public function __construct(
        $type = 'all',
        $columns = 3,
        $limit = null,
        $showCaption = false,
        $showLikes = false,
        $showTimestamp = false
    ) {
        $accessToken = config('cms.instagram.access_token');
        $ig = new IGfeed($accessToken);

        $fields = ['id', 'media_type', 'media_url', 'thumbnail_url', 'permalink', 'timestamp', 'caption', 'like_count'];
        $cacheKey = 'instagram_feeds_'.md5(json_encode($fields)).'_'.$accessToken;

        $feeds = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($ig, $fields) {
            return $ig->getFeed($fields);
        });

        if ($type !== 'all') {
            $mediaType = strtoupper($type);
            $feeds = array_filter($feeds, function ($item) use ($mediaType) {
                if ($mediaType === 'REEL') {
                    return $item['media_type'] === 'VIDEO' && (isset($item['caption']) && str_contains(strtolower($item['caption']), 'reel'));
                }

                return $item['media_type'] === $mediaType;
            });
        }

        // Apply limit if specified
        if ($limit && is_numeric($limit)) {
            $feeds = array_slice($feeds, 0, (int) $limit);
        }

        $this->feeds = $feeds;
        $this->type = $type;
        $this->columns = (int) $columns;
        $this->limit = $limit;
        $this->showCaption = (bool) $showCaption;
        $this->showLikes = (bool) $showLikes;
        $this->showTimestamp = (bool) $showTimestamp;
    }

    public function render()
    {
        return view('components.instagram-feed');
    }
}
