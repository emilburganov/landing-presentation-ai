<script setup>
import { labelFor, SENTIMENT_LABELS, TYPE_LABELS } from '../constants/labels';

defineProps({
    result: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="animate-rise border-t border-[#2f5647] pt-5">
        <p class="text-base font-semibold tracking-tight text-[#b8f06e]">{{ result.message }}</p>
        <p class="mt-2 text-sm font-medium leading-relaxed tracking-wide text-[#9db7ab]">
            Тональность: {{ labelFor(SENTIMENT_LABELS, result.sentiment) }}.
            Тип: {{ labelFor(TYPE_LABELS, result.type) }}.
        </p>
    </div>
</template>
