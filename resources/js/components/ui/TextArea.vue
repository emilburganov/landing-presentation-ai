<script setup>
defineOptions({ inheritAttrs: false });

defineProps({
    id: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        required: true,
    },
    modelValue: {
        type: String,
        default: '',
    },
    error: {
        type: String,
        default: '',
    },
    rows: {
        type: Number,
        default: 4,
    },
    placeholder: {
        type: String,
        default: '',
    },
});

defineEmits(['update:modelValue']);

const textareaClass =
    'field-control w-full resize-y border-0 border-b border-[#2f5647] bg-transparent px-0 py-3 text-[#e8f5ee] outline-none transition placeholder:font-normal placeholder:text-[#6f8b7d] focus:border-[#b8f06e]';
</script>

<template>
    <div class="block space-y-2.5" :id="id">
        <span class="field-label text-[#cfe3d8]">{{ label }}</span>
        <textarea
            :id="id"
            :value="modelValue"
            :rows="rows"
            :placeholder="placeholder"
            :class="textareaClass"
            v-bind="$attrs"
            @input="$emit('update:modelValue', $event.target.value)"
        />
        <p v-if="error" class="text-sm font-medium tracking-wide text-[#ffb4a2]">{{ error }}</p>
    </div>
</template>
