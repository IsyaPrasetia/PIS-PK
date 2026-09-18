<div class="import-tabs">
    <a href="{{ route('families.create') }}" class="import-tab {{ $active === 'form' ? 'active' : '' }}">&#9998; Manual</a>
    <a href="{{ route('ai.index') }}" class="import-tab {{ $active === 'ai' ? 'active' : '' }}">&#10022; Dengan AI (catatan)</a>
    <a href="{{ route('impor.index') }}" class="import-tab {{ $active === 'excel' ? 'active' : '' }}">&#128196; Impor Excel</a>
</div>
