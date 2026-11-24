<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { ref } from 'vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    terms: false,
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Crear Cuenta - Mayasof Cloud" />

    <div class="min-h-screen flex flex-col bg-gray-50">
        <!-- Header minimal -->
        <nav class="bg-primary text-white shadow-sm">
            <div class="container mx-auto px-4 py-3">
                <div class="flex justify-between items-center">
                    <div class="flex items-center">
                        <span class="font-medium text-lg tracking-tight">Mayasof Cloud</span>
                    </div>
                    <Link :href="route('login')" class="text-sm text-gray-300 hover:text-white transition">
                        Iniciar sesión
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
                            Crear cuenta
                        </h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Regístrate para comenzar
                        </p>
                    </div>

                    <!-- Formulario -->
                    <form @submit.prevent="submit" class="px-6 pb-6 space-y-5">
                        <!-- Nombre -->
                        <div>
                            <InputLabel for="name" value="Nombre completo" class="text-gray-700 text-sm font-medium" />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full border-gray-200 rounded-lg focus:border-gray-400 focus:ring-0"
                                required
                                autofocus
                                autocomplete="name"
                            />
                            <InputError class="mt-1" :message="form.errors.name" />
                        </div>

                        <!-- Email -->
                        <div>
                            <InputLabel for="email" value="Correo electrónico" class="text-gray-700 text-sm font-medium" />
                            <TextInput
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="mt-1 block w-full border-gray-200 rounded-lg focus:border-gray-400 focus:ring-0"
                                required
                                autocomplete="username"
                            />
                            <InputError class="mt-1" :message="form.errors.email" />
                        </div>

                        <!-- Contraseña -->
                        <div>
                            <InputLabel for="password" value="Contraseña" class="text-gray-700 text-sm font-medium" />
                            <div class="relative">
                                <TextInput
                                    id="password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    class="mt-1 block w-full border-gray-200 rounded-lg focus:border-gray-400 focus:ring-0 pr-10"
                                    required
                                    autocomplete="new-password"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                                    @click="showPassword = !showPassword"
                                >
                                    <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                </button>
                            </div>
                            <div class="mt-1 text-xs text-gray-400">
                                Mínimo 8 caracteres
                            </div>
                            <InputError class="mt-1" :message="form.errors.password" />
                        </div>

                        <!-- Confirmar Contraseña -->
                        <div>
                            <InputLabel for="password_confirmation" value="Confirmar contraseña" class="text-gray-700 text-sm font-medium" />
                            <div class="relative">
                                <TextInput
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    :type="showPasswordConfirmation ? 'text' : 'password'"
                                    class="mt-1 block w-full border-gray-200 rounded-lg focus:border-gray-400 focus:ring-0 pr-10"
                                    required
                                    autocomplete="new-password"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                                    @click="showPasswordConfirmation = !showPasswordConfirmation"
                                >
                                    <i :class="showPasswordConfirmation ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                </button>
                            </div>
                            <InputError class="mt-1" :message="form.errors.password_confirmation" />
                        </div>

                        <!-- Términos y condiciones -->
                        <div v-if="$page.props.jetstream.hasTermsAndPrivacyPolicyFeature" class="pt-2">
                            <div class="flex items-start">
                                <Checkbox
                                    id="terms"
                                    v-model:checked="form.terms"
                                    name="terms"
                                    required
                                    class="mt-0.5 border-gray-300 rounded text-accent focus:ring-accent"
                                />
                                <div class="ms-2 text-sm">
                                    <label for="terms" class="text-gray-600">
                                        Acepto los
                                        <a target="_blank" :href="route('terms.show')" class="text-accent hover:text-primary transition">
                                            Términos
                                        </a>
                                        y la
                                        <a target="_blank" :href="route('policy.show')" class="text-accent hover:text-primary transition">
                                            Política de Privacidad
                                        </a>
                                    </label>
                                </div>
                            </div>
                            <InputError class="mt-1" :message="form.errors.terms" />
                        </div>

                        <!-- Botón de registro -->
                        <PrimaryButton
                            class="w-full justify-center py-2.5 bg-accent hover:bg-accent/90 text-primary font-medium rounded-lg shadow-sm transition"
                            :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Creando cuenta...' : 'Registrarse' }}
                        </PrimaryButton>

                        <!-- Enlace a login minimal -->
                        <div class="text-center pt-2">
                            <p class="text-xs text-gray-500">
                                ¿Ya tienes cuenta?
                                <Link :href="route('login')" class="text-accent hover:text-primary font-medium">
                                    Iniciar sesión
                                </Link>
                            </p>
                        </div>
                    </form>
                </div>

                <!-- Texto legal simple -->
                <div class="text-center mt-6">
                    <p class="text-xs text-gray-400">
                        Al registrarte aceptas nuestros
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

.transition {
    transition: all 0.2s ease;
}
</style>
