<template>
    <nav class="bg-primary text-white sticky top-0 z-50 shadow-lg">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <!-- Logo -->
                <div class="flex items-center">
                    <div class="bg-accent w-10 h-10 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-calculator text-primary text-xl"></i>
                    </div>
                    <span class="font-bold text-xl">Mayasof</span>
                </div>

                <!-- Menú para escritorio -->
                <div class="hidden md:flex space-x-8">
                    <router-link to="/" class="text-light hover:text-accent transition">Inicio</router-link>
                    <router-link to="/about" class="text-light hover:text-accent transition">Nosotros</router-link>
                    <router-link to="/services" class="text-light hover:text-accent transition">Servicios</router-link>
                    <router-link to="/clients" class="text-light hover:text-accent transition">Clientes</router-link>
                    <router-link to="/contact" class="text-light hover:text-accent transition">Contacto</router-link>
                </div>

                <!-- Botón Acceder -->
                <router-link to="/login" class="btn-primary bg-accent text-primary font-semibold py-2 px-6 rounded-lg hover:bg-[#52e0c4]">
                    Acceder
                </router-link>

                <!-- Menú móvil -->
                <button @click="toggleMobileMenu" class="md:hidden text-light focus:outline-none">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>

            <!-- Menú móvil desplegable -->
            <div v-show="mobileMenuOpen" class="md:hidden py-4">
                <router-link to="/" class="block py-2 px-4 text-light hover:bg-secondary rounded" @click="mobileMenuOpen = false">Inicio</router-link>
                <router-link to="/about" class="block py-2 px-4 text-light hover:bg-secondary rounded" @click="mobileMenuOpen = false">Nosotros</router-link>
                <router-link to="/services" class="block py-2 px-4 text-light hover:bg-secondary rounded" @click="mobileMenuOpen = false">Servicios</router-link>
                <router-link to="/clients" class="block py-2 px-4 text-light hover:bg-secondary rounded" @click="mobileMenuOpen = false">Clientes</router-link>
                <router-link to="/contact" class="block py-2 px-4 text-light hover:bg-secondary rounded" @click="mobileMenuOpen = false">Contacto</router-link>
            </div>
        </div>
    </nav>
</template>

<script>
export default {
    name: 'Navbar',
    data() {
        return {
            mobileMenuOpen: false
        }
    },
    methods: {
        toggleMobileMenu() {
            this.mobileMenuOpen = !this.mobileMenuOpen;
        }
    }
}
</script>

<style scoped>
.btn-primary {
    transition: all 0.3s ease;
}
.btn-primary:hover {
    box-shadow: 0 0 15px rgba(100, 255, 218, 0.4);
}
</style>
