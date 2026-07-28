<script setup>
import { computed, ref, watch } from 'vue';
import { AsYouType, parsePhoneNumberFromString } from 'libphonenumber-js';
import { buildPhoneCountries, DEFAULT_PHONE_COUNTRY } from '../../constants/phoneCountries';
import { clampNationalDigits, toPhoneE164 } from '../../utils/phone';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    id: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        required: true,
    },
    /** E.164 value, e.g. +79991234567 */
    modelValue: {
        type: String,
        default: '',
    },
    error: {
        type: String,
        default: '',
    },
    autocomplete: {
        type: String,
        default: 'tel-national',
    },
});

const emit = defineEmits(['update:modelValue']);

const countries = buildPhoneCountries();
const country = ref(DEFAULT_PHONE_COUNTRY);
const display = ref('');
const syncingFromParent = ref(false);

const placeholder = computed(() => {
    const sample = new AsYouType(country.value);
    const draft = sample.input('9123456789');
    return draft || 'Номер телефона';
});

function formatNational(digitsOrRaw, iso2) {
    return new AsYouType(iso2).input(digitsOrRaw);
}

function applyFromModel(value) {
    syncingFromParent.value = true;
    try {
        if (!value) {
            display.value = '';
            return;
        }

        const parsed = parsePhoneNumberFromString(value);
        if (parsed?.country) {
            country.value = parsed.country;
            display.value = parsed.formatNational();
            return;
        }

        display.value = value;
    } finally {
        syncingFromParent.value = false;
    }
}

watch(
    () => props.modelValue,
    (value) => {
        if (syncingFromParent.value) {
            return;
        }

        const currentE164 = toPhoneE164(display.value, country.value);
        if (value === currentE164) {
            return;
        }

        applyFromModel(value);
    },
    { immediate: true },
);

function commitDigits(digits) {
    const clamped = clampNationalDigits(digits, country.value);
    const formatted = clamped ? formatNational(clamped, country.value) : '';
    display.value = formatted;
    emit('update:modelValue', toPhoneE164(formatted, country.value));
}

function onCountryChange() {
    commitDigits(display.value);
}

function onInput(event) {
    // Formatting may include spaces/dashes; only digits are kept for the value
    commitDigits(event.target.value);
}

function onKeydown(event) {
    if (event.ctrlKey || event.metaKey || event.altKey) {
        return;
    }

    const allowedKeys = new Set([
        'Backspace',
        'Delete',
        'Tab',
        'Escape',
        'Enter',
        'ArrowLeft',
        'ArrowRight',
        'ArrowUp',
        'ArrowDown',
        'Home',
        'End',
    ]);

    if (allowedKeys.has(event.key)) {
        return;
    }

    // Block letters, spaces, punctuation — digits only
    if (event.key.length === 1 && !/\d/.test(event.key)) {
        event.preventDefault();
    }
}

function onBeforeInput(event) {
    if (!event.inputType?.startsWith('insert')) {
        return;
    }

    const data = event.data ?? '';
    if (data === '') {
        return;
    }

    const digitsOnly = data.replace(/\D/g, '');

    // Non-digit or mixed paste/type: accept digits only
    if (digitsOnly !== data) {
        event.preventDefault();
        if (!digitsOnly) {
            return;
        }

        const input = event.target;
        const start = input.selectionStart ?? input.value.length;
        const end = input.selectionEnd ?? input.value.length;
        commitDigits(input.value.slice(0, start) + digitsOnly + input.value.slice(end));
        return;
    }

    // Pure digits — block when already at max / complete length
    const input = event.target;
    const next =
        input.value.slice(0, input.selectionStart ?? input.value.length) +
        data +
        input.value.slice(input.selectionEnd ?? input.value.length);

    const clamped = clampNationalDigits(next, country.value);
    const currentClamped = clampNationalDigits(input.value, country.value);

    if (clamped === currentClamped) {
        event.preventDefault();
    }
}

const fieldClass =
    'field-control w-full border-0 border-b border-[#2f5647] bg-transparent px-0 py-3 text-[#e8f5ee] outline-none transition placeholder:font-normal placeholder:text-[#6f8b7d] focus:border-[#b8f06e]';
</script>

<template>
    <div class="block space-y-2.5">
        <label class="field-label text-[#cfe3d8]" :for="id">{{ label }}</label>

        <div class="flex items-end gap-3">
            <div class="relative shrink-0">
                <select
                    :id="`${id}-country`"
                    v-model="country"
                    class="field-control max-w-[7.5rem] cursor-pointer appearance-none border-0 border-b border-[#2f5647] bg-transparent py-3 pr-4 text-[#e8f5ee] outline-none transition focus:border-[#b8f06e]"
                    aria-label="Страна"
                    @change="onCountryChange"
                >
                    <option
                        v-for="item in countries"
                        :key="item.iso2"
                        :value="item.iso2"
                        class="bg-[#0f3d2e] text-[#e8f5ee]"
                    >
                        {{ item.flag }} {{ item.iso2 }} {{ item.dialCode }}
                    </option>
                </select>
            </div>

            <input
                :id="id"
                type="tel"
                inputmode="tel"
                :value="display"
                :autocomplete="autocomplete"
                :placeholder="placeholder"
                :class="fieldClass"
                v-bind="$attrs"
                @keydown="onKeydown"
                @beforeinput="onBeforeInput"
                @input="onInput"
            />
        </div>

        <p v-if="error" class="text-sm font-medium tracking-wide text-[#ffb4a2]">{{ error }}</p>
    </div>
</template>
