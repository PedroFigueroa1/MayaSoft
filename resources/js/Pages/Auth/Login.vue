<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.transform(data => ({
        ...data,
        remember: form.remember ? 'on' : '',
    })).post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Iniciar Sesión - Mayasof Cloud" />

    <div class="min-h-screen flex flex-col bg-gray-50">
        <!-- Header minimal -->
        <nav class="bg-primary text-white shadow-sm">
            <div class="container mx-auto px-4 py-3">
                <div class="flex justify-between items-center">
                    <div class="flex items-center">
                        <span class="font-medium text-lg tracking-tight">Mayasof Cloud</span>
                    </div>
                    <Link :href="route('register')" class="text-sm text-gray-300 hover:text-white transition">
                        Registrarse
                    </Link>
                </div>
            </div>
        </nav>

        <!-- Contenido principal -->
        <div class="flex-grow flex items-center justify-center py-12 px-4">
            <div class="max-w-md w-full">
                <!-- Tarjeta minimalista -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <!-- Encabezado simple -->
                    <div class="px-6 pt-8 pb-4 text-center">
                        <h2 class="text-2xl font-semibold text-gray-900">
                            Iniciar sesión
                        </h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Accede a tu cuenta
                        </p>
                    </div>

                    <!-- Alertas -->
                    <div v-if="status" class="mx-6 mb-4 bg-green-50 border border-green-200 rounded-md p-3 text-sm text-green-700">
                        {{ status }}
                    </div>

                    <!-- Formulario -->
                    <form @submit.prevent="submit" class="px-6 pb-6 space-y-5">
                        <!-- Email -->
                        <div>
                            <InputLabel for="email" value="Correo electrónico" class="text-gray-700 text-sm font-medium" />
                            <TextInput
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="mt-1 block w-full border-gray-200 rounded-lg focus:border-gray-400 focus:ring-0"
                                required
                                autofocus
                                autocomplete="username"
                            />
                            <InputError class="mt-1" :message="form.errors.email" />
                        </div>

                        <!-- Contraseña -->
                        <div>
                            <div class="flex items-center justify-between">
                                <InputLabel for="password" value="Contraseña" class="text-gray-700 text-sm font-medium" />
                                <Link v-if="canResetPassword" :href="route('password.request')" class="text-xs text-gray-500 hover:text-gray-700 transition">
                                    ¿Olvidaste tu contraseña?
                                </Link>
                            </div>
                            <TextInput
                                id="password"
                                v-model="form.password"
                                type="password"
                                class="mt-1 block w-full border-gray-200 rounded-lg focus:border-gray-400 focus:ring-0"
                                required
                                autocomplete="current-password"
                            />
                            <InputError class="mt-1" :message="form.errors.password" />
                        </div>

                        <!-- Recordar sesión -->
                        <div class="flex items-center">
                            <Checkbox
                                v-model:checked="form.remember"
                                name="remember"
                                class="border-gray-300 rounded text-accent focus:ring-accent"
                            />
                            <span class="ms-2 text-sm text-gray-600">Recordar sesión</span>
                        </div>

                        <!-- Botón de login -->
                        <PrimaryButton
                            class="w-full justify-center py-2.5 bg-accent hover:bg-accent/90 text-primary font-medium rounded-lg shadow-sm transition"
                            :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Iniciando...' : 'Iniciar sesión' }}
                        </PrimaryButton>

                        <!-- Enlace de registro minimal -->
                        <div class="text-center pt-2">
                            <p class="text-xs text-gray-500">
                                ¿No tienes cuenta?
                                <Link :href="route('register')" class="text-accent hover:text-primary font-medium">
                                    Crear cuenta
                                </Link>
                            </p>
                        </div>
                    </form>
                </div>

                <!-- Texto legal simple -->
                <div class="text-center mt-6">
                    <p class="text-xs text-gray-400">
                        Al iniciar sesión aceptas nuestros
                        <a href="#" class="text-gray-500 hover:text-gray-700">Términos</a>
                        y
                        <a href="#" class="text-gray-500 hover:text-gray-700">Privacidad</a>
                    </p>
                </div>
            </div>
        </div>

        <!-- Footer simple -->
        <footer class="py-4 text-center text-xs text-gray-400 border-t border-gray-100">
            &copy; {{ new Date().getFullYear() }} Mayasof Cloud
        </footer>
    </div>
</template>

<style scoped>
@import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');

.bg-primary {
    background-color: #0a192f;
}

.bg-accent {
    background-color: #64ffda;
}

.text-accent {
    color: #64ffda;
}

.text-primary {
    color: #0a192f;
}

/* Estilos minimalistas para inputs */
input:focus {
    outline: none;
    border-color: #64ffda;
    box-shadow: 0 0 0 2px rgba(100, 255, 218, 0.1);
}

/* Transiciones suaves */
.transition {
    transition: all 0.2s ease;
}
</style>
