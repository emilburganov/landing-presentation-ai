<script setup>
import { onMounted, ref } from 'vue';
import ContactForm from './components/ContactForm.vue';
import HeroSection from './components/HeroSection.vue';

const ready = ref(false);

onMounted(() => {
    requestAnimationFrame(() => {
        ready.value = true;
    });
});
</script>

<template>
    <div class="relative min-h-screen overflow-hidden">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_20%_10%,#1f6b4f_0%,transparent_45%),radial-gradient(ellipse_at_90%_0%,#143d30_0%,transparent_40%),linear-gradient(160deg,#071a14_0%,#0b241c_55%,#061510_100%)]" />
        <div class="bg-orb pointer-events-none absolute -left-24 top-16 h-72 w-72 rounded-full bg-[#b8f06e]/15 blur-3xl" />
        <div class="bg-orb-soft pointer-events-none absolute -right-16 bottom-10 h-80 w-80 rounded-full bg-[#1f6b4f]/35 blur-3xl" />

        <main class="relative mx-auto flex min-h-screen w-full max-w-6xl flex-col justify-center px-6 py-16 md:px-10 lg:px-12">
            <div class="grid items-center gap-12 lg:grid-cols-[1.05fr_0.95fr] lg:gap-16">
                <HeroSection :ready="ready" />

                <section :class="ready ? 'animate-rise-delay' : 'opacity-0'">
                    <ContactForm />
                </section>
            </div>
        </main>
    </div>
</template>
