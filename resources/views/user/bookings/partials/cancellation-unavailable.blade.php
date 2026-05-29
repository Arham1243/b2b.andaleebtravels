<div class="text-center py-4">
    <p class="text-danger mb-2">
        <i class="bx bx-x-circle"></i>
        {{ $cancellation['reason'] ?? 'Cancellation is not available for this booking.' }}
    </p>
    @if (!empty($cancellation['policy_summary']))
        <p class="text-muted small mb-0">{{ $cancellation['policy_summary'] }}</p>
    @endif
</div>
