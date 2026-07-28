<script setup>
defineProps({
    ready: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <section :class="ready ? 'animate-rise' : 'opacity-0'">
        <h1 class="hero-title mt-7 max-w-xl text-3xl text-[#e8f5ee] sm:text-4xl lg:text-[2.75rem]">
            Напишите нам — мы поймём суть обращения
        </h1>
        <p class="hero-lead mt-5 max-w-md text-base text-[#9db7ab] sm:text-lg">
            Форма обратной связи с AI-анализом тональности и типа сообщения. Ответ уйдёт вам на почту.
        </p>
    </section>
</template>
