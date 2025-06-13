<?php

namespace Littleboy130491\Sumimasen\Traits;

trait HasPageLikes
{
    /**
     * Get the current page likes count for this model.
     */
    public function getPageLikesAttribute(): int
    {
        $customFields = $this->custom_fields ?? [];

        return (int) ($customFields['page_likes'] ?? 0);
    }

    /**
     * Increment the page likes count for this model.
     *
     * @param  int  $increment  The amount to increment by (default: 1)
     */
    public function incrementPageLikes(int $increment = 1): bool
    {
        $customFields = $this->custom_fields ?? [];
        $currentLikes = (int) ($customFields['page_likes'] ?? 0);
        $customFields['page_likes'] = $currentLikes + $increment;

        return $this->update(['custom_fields' => $customFields]);
    }

    /**
     * Decrement the page likes count for this model.
     *
     * @param  int  $decrement  The amount to decrement by (default: 1)
     */
    public function decrementPageLikes(int $decrement = 1): bool
    {
        $customFields = $this->custom_fields ?? [];
        $currentLikes = (int) ($customFields['page_likes'] ?? 0);
        $customFields['page_likes'] = max(0, $currentLikes - $decrement); // Ensure non-negative

        return $this->update(['custom_fields' => $customFields]);
    }

    /**
     * Set the page likes count for this model.
     *
     * @param  int  $likes  The new page likes count
     */
    public function setPageLikes(int $likes): bool
    {
        $customFields = $this->custom_fields ?? [];
        $customFields['page_likes'] = max(0, $likes); // Ensure non-negative

        return $this->update(['custom_fields' => $customFields]);
    }

    /**
     * Reset the page likes count to zero.
     */
    public function resetPageLikes(): bool
    {
        return $this->setPageLikes(0);
    }

    /**
     * Scope to order by page likes (most liked first).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByPageLikes($query, $direction = 'desc')
    {
        return $query->orderByRaw("JSON_EXTRACT(custom_fields, '$.page_likes') {$direction}");
    }

    /**
     * Scope to get most liked content.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $limit
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMostLiked($query, $limit = 10)
    {
        return $query->orderByPageLikes('desc')->limit($limit);
    }

    /**
     * Scope to filter by minimum page likes.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $minLikes
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithMinLikes($query, $minLikes)
    {
        return $query->whereRaw("JSON_EXTRACT(custom_fields, '$.page_likes') >= ?", [$minLikes]);
    }
}
