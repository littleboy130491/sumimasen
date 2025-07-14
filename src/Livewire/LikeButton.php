<?php

namespace App\Livewire;

use Littleboy130491\Sumimasen\Traits\HasPageLikes;
use Livewire\Component;
class LikeButton extends Component
{
    public $content;

    public string $lang;

    public string $contentType;

    public bool $showCount = true;

    public string $size = 'md';

    public string $variant = 'default';

    public bool $hasLiked = false;

    public int $likesCount = 0;

    public function mount($content = null, bool $showCount = true, string $size = 'md', string $variant = 'default')
    {
        $this->content = $content ?? view()->shared('globalItem');
        $this->showCount = $showCount;
        $this->size = $size;
        $this->variant = $variant;

        // Check if the model uses HasPageLikes trait
        if (!in_array(HasPageLikes::class, class_uses_recursive($this->content))) {
            throw new \Exception('Content model must use HasPageLikes trait');
        }

        $this->initializeLikeState();
    }

    public function initializeLikeState()
    {
        $cookieName = "liked_content_{$this->content->getTable()}_{$this->content->id}";
        $this->hasLiked = request()->cookie($cookieName) === 'true';
        $this->likesCount = $this->content->page_likes ?? 0;
    }

    public function toggleLike()
    {
        $cookieName = "liked_content_{$this->content->getTable()}_{$this->content->id}";

        if ($this->hasLiked) {
            // Unlike: decrement likes and remove cookie
            $this->content->decrementPageLikes();
            $this->hasLiked = false;
            // Set cookie to expire
            cookie()->queue(cookie($cookieName, 'false', -1));
        } else {
            // Like: increment likes and set cookie
            $this->content->incrementPageLikes();
            $this->hasLiked = true;
            // Set cookie for 1 year
            cookie()->queue(cookie($cookieName, 'true', 60 * 24 * 365));
        }

        // Refresh the likes count from database
        $this->likesCount = $this->content->fresh()->page_likes;

        // Dispatch browser event for any additional JavaScript handling
        $this->dispatch('like-toggled', [
            'contentId' => $this->content->id,
            'liked' => $this->hasLiked,
            'likesCount' => $this->likesCount,
        ]);
    }

    public function getSizeClasses()
    {
        return match ($this->size) {
            'sm' => 'text-sm px-2 py-1',
            'lg' => 'text-lg px-4 py-3',
            default => 'text-base px-3 py-2'
        };
    }

    public function getVariantClasses()
    {
        return match ($this->variant) {
            'minimal' => 'bg-transparent hover:bg-gray-100 text-gray-600',
            'outline' => 'border border-gray-300 bg-white hover:bg-gray-50 text-gray-700',
            default => 'bg-gray-100 hover:bg-gray-200 text-gray-700'
        };
    }

    public function render()
    {
        return view('livewire.like-button');
    }
}
