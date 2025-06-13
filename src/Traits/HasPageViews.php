<?php

namespace Littleboy130491\Sumimasen\Traits;

trait HasPageViews
{
    /**
     * Get the current page views count for this model.
     */
    public function getPageViewsAttribute(): int
    {
        $customFields = $this->custom_fields ?? [];

        return (int) ($customFields['page_views'] ?? 0);
    }

    /**
     * Increment the page views count for this model.
     *
     * @param  int  $increment  The amount to increment by (default: 1)
     */
    public function incrementPageViews(int $increment = 1): bool
    {
        $customFields = $this->custom_fields ?? [];
        $currentViews = (int) ($customFields['page_views'] ?? 0);
        $customFields['page_views'] = $currentViews + $increment;

        return $this->update(['custom_fields' => $customFields]);
    }

    /**
     * Set the page views count for this model.
     *
     * @param  int  $views  The new page views count
     */
    public function setPageViews(int $views): bool
    {
        $customFields = $this->custom_fields ?? [];
        $customFields['page_views'] = max(0, $views); // Ensure non-negative

        return $this->update(['custom_fields' => $customFields]);
    }

    /**
     * Reset the page views count to zero.
     */
    public function resetPageViews(): bool
    {
        return $this->setPageViews(0);
    }

    /**
     * Scope to order by page views (most viewed first).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByPageViews($query, $direction = 'desc')
    {
        return $query->orderByRaw("JSON_EXTRACT(custom_fields, '$.page_views') {$direction}");
    }

    /**
     * Scope to get most viewed content.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $limit
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMostViewed($query, $limit = 10)
    {
        return $query->orderByPageViews('desc')->limit($limit);
    }

    /**
     * Scope to filter by minimum page views.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $minViews
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithMinViews($query, $minViews)
    {
        return $query->whereRaw("JSON_EXTRACT(custom_fields, '$.page_views') >= ?", [$minViews]);
    }
}
