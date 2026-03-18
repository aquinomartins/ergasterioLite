@props(['name', 'label', 'value' => null])
<label class="block space-y-2">
    <span class="text-sm font-medium text-slate-200">{{ $label }}</span>
    <textarea name="{{ $name }}" rows="5" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-slate-100">{{ old($name, $value) }}</textarea>
</label>
