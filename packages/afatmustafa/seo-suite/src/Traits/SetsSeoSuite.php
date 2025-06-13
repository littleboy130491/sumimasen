<?php

namespace Afatmustafa\SeoSuite\Traits;

use Afatmustafa\SeoSuite\Enums\OpenGraphTypes;
use Illuminate\Database\Eloquent\Model;

trait SetsSeoSuite
{
    use \Artesaos\SEOTools\Traits\SEOTools;

    public function setsSeo(Model $model)
    {
        $seoSuiteData = $model->seoSuite;
        if (! $seoSuiteData) {
            return;
        }

        // General
        if (config('seo-suite.features.general.enabled')) {
            $this->seo()->setTitle($model->getSimpleSeoField('title'))
                ->setDescription($model->getSimpleSeoField('description'));
        }

        // Advanced
        if (config('seo-suite.features.advanced.enabled')) {
            $this->seo()->setCanonical($model->getSimpleSeoField('canonical_url'));
            $noindex = $model->getSimpleSeoField('noindex');
            $nofollow = $model->getSimpleSeoField('nofollow');

            if ($noindex || $nofollow) {
                if ($noindex && $nofollow) {
                    $this->seo()->metatags()->setRobots('noindex, nofollow');
                } else {
                    if ($noindex) {
                        $this->seo()->metatags()->setRobots('noindex');
                    }
                    if ($nofollow) {
                        $this->seo()->metatags()->setRobots('nofollow');
                    }
                }
            }

            if (is_array($model->getAdditionalMetaTags())) {
                collect($model->getAdditionalMetaTags())->map(function ($value) {
                    return $this->seo()
                        ->metatags()
                        ->addMeta($value['meta'], $value['content'], $value['meta_type']);
                });
            }
        }

        // OpenGraph
        if (config('seo-suite.features.opengraph.enabled')) {
            // TODO: Add image support
            $openGraph = $this->seo()->opengraph()
                ->setTitle($model->getSimpleSeoField('og_title'))
                ->setDescription($model->getSimpleSeoField('og_description'))
                ->setType($model->getOpenGraphType()->value);
            $openGraph = match ($model->getOpenGraphType()) {
                OpenGraphTypes::ARTICLE => $openGraph->setArticle($model->getOpenGraphField('og_type_details')),
                OpenGraphTypes::BOOK => $openGraph->setBook($model->getOpenGraphField('og_type_details')),
                OpenGraphTypes::PROFILE => $openGraph->setProfile($model->getOpenGraphField('og_type_details')),
                //OpenGraphTypes::MUSIC_SONG => $openGraph->setMusicSong($model->getOpenGraphField('og_type_details')),
                //OpenGraphTypes::MUSIC_ALBUM => $openGraph->setMusicAlbum($model->getOpenGraphField('og_type_details')),
                //OpenGraphTypes::MUSIC_PLAYLIST => $openGraph->setMusicPlaylist($model->getOpenGraphField('og_type_details')),
                //OpenGraphTypes::MUSIC_RADIO_STATION => $openGraph->setMusicRadioStation($model->getOpenGraphField('og_type_details')),
                //OpenGraphTypes::VIDEO_MOVIE => $openGraph->setVideoMovie($model->getOpenGraphField('og_type_details')),
                //OpenGraphTypes::VIDEO_EPISODE => $openGraph->setVideoEpisode($model->getOpenGraphField('og_type_details')),
                //OpenGraphTypes::VIDEO_TV_SHOW => $openGraph->setVideoTVShow($model->getOpenGraphField('og_type_details')),
                //OpenGraphTypes::VIDEO_OTHER => $openGraph->setVideoOther($model->getOpenGraphField('og_type_details')),
                default => $openGraph,
            };
            $openGraph = match (is_array($model->getOpenGraphField('og_properties'))) {
                true => collect($model->getOpenGraphField('og_properties'))->map(function ($value) use ($openGraph) {
                    return $openGraph->addProperty($value['key'], $value['value']);
                }),
                default => $openGraph,
            };
        }

        // X
        if (config('seo-suite.features.x.enabled')) {
            $this->seo()->twitter()
                ->setTitle($model->getSimpleSeoField('x_title'))
                ->setSite($model->getSimpleSeoField('x_site'))
                ->setType($model->getXCardType()->value);
        }

    }
}
