@props(['model', 'label' => null, 'min' => null, 'max' => null])

<div 
    x-data="{
        open: false,
        min: @js($min),
        max: @js($max),
        get minHour() {
            return this.min ? parseInt(this.min.split(':')[0]) : 0;
        },
        get minMinute() {
            return this.min ? parseInt(this.min.split(':')[1]) : 0;
        },
        get maxHour() {
            return this.max ? parseInt(this.max.split(':')[0]) : 23;
        },
        get maxMinute() {
            return this.max ? parseInt(this.max.split(':')[1]) : 59;
        },
        get hours() {
            let arr = [];
            for (let i = this.minHour; i <= this.maxHour; i++) {
                arr.push(String(i).padStart(2, '0'));
            }
            return arr;
        },
        get currentHour() {
            let val = $wire.get('{{ $model }}');
            return val ? parseInt(val.split(':')[0]) : null;
        },
        get currentMinute() {
            let val = $wire.get('{{ $model }}');
            return val ? parseInt(val.split(':')[1]) : null;
        },
        get minutes() {
            let h = this.currentHour;
            let start = 0;
            let end = 59;

            if (h === this.minHour) start = this.minMinute;
            if (h === this.maxHour) end = this.maxMinute;

            let arr = [];
            for (let i = start; i <= end; i++) {
                arr.push(String(i).padStart(2, '0'));
            }
            return arr;
        },
        get displayValue() {
            let val = $wire.get('{{ $model }}');
            return val ? val : '-- : --';
        },
        get hasRange() {
            return this.min && this.max;
        },
        selectHour(h) {
            let val = $wire.get('{{ $model }}');
            let currentMinute = val ? val.split(':')[1] : '00';

            let start = (parseInt(h) === this.minHour) ? this.minMinute : 0;
            let end = (parseInt(h) === this.maxHour) ? this.maxMinute : 59;
            let m = parseInt(currentMinute);
            if (isNaN(m) || m < start) m = start;
            if (m > end) m = end;

            $wire.set('{{ $model }}', h + ':' + String(m).padStart(2, '0'));
        },
        selectMinute(m) {
            let val = $wire.get('{{ $model }}');
            let h = val ? val.split(':')[0] : String(this.minHour).padStart(2, '0');
            $wire.set('{{ $model }}', h + ':' + m);
        }
    }"
    class="relative"
    wire:key="time-input-{{ $model }}-{{ $min }}-{{ $max }}"
    x-on:click.outside="open = false"
>
    @if($label)
        <label class="block text-sm font-semibold text-gray-700 mb-1">{{ $label }}</label>
    @endif

    <button
        type="button"
        x-on:click="open = !open"
        :disabled="!hasRange"
        class="w-full flex items-center justify-between px-4 py-2 border border-gray-300 rounded-xl bg-white text-left
               focus:outline-none focus:ring-2 focus:ring-amber-500
               disabled:bg-gray-100 disabled:cursor-not-allowed disabled:text-gray-400"
    >
        <span x-text="displayValue" class="text-gray-700"></span>
        <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
    </button>

    <template x-if="!hasRange">
        <p class="text-xs text-gray-400 italic mt-1">Pilih hari terlebih dahulu</p>
    </template>

    <div
        x-show="open"
        x-transition
        x-cloak
        class="absolute z-50 mt-2 w-full bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden"
    >
        <div class="flex">
            <div class="flex-1 max-h-48 overflow-y-auto overscroll-contain border-r">
                <template x-for="h in hours" :key="h">
                    <button
                        type="button"
                        x-on:click="selectHour(h)"
                        class="w-full px-3 py-2 text-sm text-center hover:bg-amber-50 hover:text-amber-600"
                        :class="currentHour === parseInt(h) ? 'bg-amber-100 text-amber-600 font-semibold' : ''"
                        x-text="h"
                    ></button>
                </template>
            </div>
            <div class="flex-1 max-h-48 overflow-y-auto overscroll-contain">
                <template x-for="m in minutes" :key="m">
                    <button
                        type="button"
                        x-on:click="selectMinute(m)"
                        class="w-full px-3 py-2 text-sm text-center hover:bg-amber-50 hover:text-amber-600"
                        :class="currentMinute === parseInt(m) ? 'bg-amber-100 text-amber-600 font-semibold' : ''"
                        x-text="m"
                    ></button>
                </template>
            </div>
        </div>

        <button
            type="button"
            x-on:click="open = false"
            class="w-full px-3 py-2 text-sm font-semibold text-white bg-amber-500 hover:bg-amber-600 transition"
        >
            Selesai
        </button>
    </div>
</div>