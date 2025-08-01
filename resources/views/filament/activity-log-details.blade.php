<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Timestamp</h3>
            <p class="text-sm text-gray-900 dark:text-gray-100">{{ $record->timestamp ?? 'N/A' }}</p>
        </div>
        
        <div>
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Activity</h3>
            <p class="text-sm text-gray-900 dark:text-gray-100">{{ $record->activity ?? 'N/A' }}</p>
        </div>
        
        <div>
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">User</h3>
            <p class="text-sm text-gray-900 dark:text-gray-100">{{ $record->user_name ?? 'N/A' }} ({{ $record->user_email ?? 'N/A' }})</p>
        </div>
        
        <div>
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">IP Address</h3>
            <p class="text-sm text-gray-900 dark:text-gray-100">{{ $record->ip_address ?? 'N/A' }}</p>
        </div>
        
        <div>
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Device & Platform</h3>
            <p class="text-sm text-gray-900 dark:text-gray-100">{{ $record->device_type ?? 'N/A' }} - {{ $record->platform ?? 'N/A' }}</p>
        </div>
        
        <div>
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Browser</h3>
            <p class="text-sm text-gray-900 dark:text-gray-100">{{ $record->browser ?? 'N/A' }}</p>
        </div>
        
        @if(isset($record->subject_type))
        <div>
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Subject</h3>
            <p class="text-sm text-gray-900 dark:text-gray-100">{{ class_basename($record->subject_type) }} #{{ $record->subject_id ?? 'N/A' }}</p>
        </div>
        @endif
        
        <div>
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Session ID</h3>
            <p class="text-sm text-gray-900 dark:text-gray-100">{{ $record->session_id ?? 'N/A' }}</p>
        </div>
    </div>
    
    @if(isset($record->url))
    <div>
        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">URL</h3>
        <p class="text-sm text-gray-900 dark:text-gray-100 break-all">{{ $record->url }}</p>
    </div>
    @endif
    
    @if(isset($record->user_agent))
    <div>
        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">User Agent</h3>
        <p class="text-sm text-gray-900 dark:text-gray-100 break-all">{{ $record->user_agent }}</p>
    </div>
    @endif
    
    @if(isset($record->additional_data) && !empty($record->additional_data))
    <div>
        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Additional Data</h3>
        <pre class="text-xs bg-gray-50 dark:bg-gray-800 p-3 rounded-md overflow-auto max-h-40">{{ json_encode($record->additional_data, JSON_PRETTY_PRINT) }}</pre>
    </div>
    @endif
    
    @if(isset($record->changes) && !empty($record->changes))
    <div>
        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Changes</h3>
        <pre class="text-xs bg-gray-50 dark:bg-gray-800 p-3 rounded-md overflow-auto max-h-40">{{ json_encode($record->changes, JSON_PRETTY_PRINT) }}</pre>
    </div>
    @endif
    
    @if(isset($record->raw_line))
    <div>
        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Raw Log Entry</h3>
        <pre class="text-xs bg-gray-50 dark:bg-gray-800 p-3 rounded-md overflow-auto">{{ $record->raw_line }}</pre>
    </div>
    @endif
</div>