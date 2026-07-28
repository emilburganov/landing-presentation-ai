<script setup>
import { useContactForm } from '../composables/useContactForm';
import ContactResult from './ContactResult.vue';
import PhoneField from './ui/PhoneField.vue';
import TextArea from './ui/TextArea.vue';
import TextField from './ui/TextField.vue';

const {
    name,
    nameAttrs,
    phone,
    phoneAttrs,
    email,
    emailAttrs,
    comment,
    commentAttrs,
    errors,
    result,
    serverError,
    isSubmitting,
    canSubmit,
    onSubmit,
} = useContactForm();
</script>

<template>
    <form class="space-y-5" novalidate @submit="onSubmit">
        <div class="grid gap-4 sm:grid-cols-2">
            <TextField
                id="contact-name"
                v-model="name"
                v-bind="nameAttrs"
                label="Имя"
                autocomplete="name"
                placeholder="Иван"
                :error="errors.name"
            />

            <PhoneField
                id="contact-phone"
                v-model="phone"
                v-bind="phoneAttrs"
                label="Телефон"
                :error="errors.phone"
            />
        </div>

        <TextField
            id="contact-email"
            v-model="email"
            v-bind="emailAttrs"
            label="Email"
            type="email"
            autocomplete="email"
            placeholder="ivan@example.com"
            :error="errors.email"
        />

        <TextArea
            id="contact-comment"
            v-model="comment"
            v-bind="commentAttrs"
            label="Сообщение"
            placeholder="Расскажите, чем мы можем помочь..."
            :error="errors.comment"
        />

        <div class="flex flex-col gap-4 pt-2 sm:flex-row sm:items-center">
            <button
                type="submit"
                :disabled="!canSubmit"
                class="btn-label cursor-pointer inline-flex items-center justify-center bg-[#b8f06e] px-7 py-3.5 text-[#071a14] transition hover:bg-[#d4ff8f] disabled:cursor-not-allowed disabled:opacity-60"
            >
                {{ isSubmitting ? 'Отправляем…' : 'Отправить' }}
            </button>

            <p v-if="serverError" class="text-sm font-medium tracking-wide text-[#ffb4a2]">{{ serverError }}</p>
        </div>

        <ContactResult v-if="result" :result="result" />
    </form>
</template>
