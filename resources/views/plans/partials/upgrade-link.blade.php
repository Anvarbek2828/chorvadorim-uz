{{-- Tariflar sahifasi faqat plan.view huquqi borlarga ochiq --}}
@can('plan.view')
<a href="{{ route('plans.index') }}" class="block mt-2 font-semibold underline">{{ __("Tariflarni ko'rish") }} &rarr;</a>
@endcan
