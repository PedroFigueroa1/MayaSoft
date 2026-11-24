<template>
    <footer class="bg-primary text-white pt-16 pb-8">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">
                <!-- Columna 1 -->
                <div>
                    <div class="flex items-center mb-6">
                        <div class="bg-accent w-10 h-10 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-calculator text-primary text-xl"></i>
                        </div>
                        <span class="font-bold text-xl">Mayasof</span>
                    </div>
                    <p class="text-lighter mb-6">
                        Soluciones contables innovadoras para empresas modernas. Simplificamos tus finanzas para que puedas enfocarte en crecer.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-lighter hover:text-accent transition">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-lighter hover:text-accent transition">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-lighter hover:text-accent transition">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="#" class="text-lighter hover:text-accent transition">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>

                <!-- Columna 2 -->
                <div>
                    <h3 class="text-lg font-bold mb-6 text-light">Enlaces rápidos</h3>
                    <ul class="space-y-3">
                        <li><router-link to="/" class="text-lighter hover:text-accent transition">Inicio</router-link></li>
                        <li><router-link to="/about" class="text-lighter hover:text-accent transition">Nosotros</router-link></li>
                        <li><router-link to="/services" class="text-lighter hover:text-accent transition">Servicios</router-link></li>
                        <li><router-link to="/pricing" class="text-lighter hover:text-accent transition">Precios</router-link></li>
                        <li><router-link to="/blog" class="text-lighter hover:text-accent transition">Blog</router-link></li>
                    </ul>
                </div>

                <!-- Columna 3 -->
                <div>
                    <h3 class="text-lg font-bold mb-6 text-light">Servicios</h3>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-lighter hover:text-accent transition">Contabilidad Integral</a></li>
                        <li><a href="#" class="text-lighter hover:text-accent transition">Facturación Electrónica</a></li>
                        <li><a href="#" class="text-lighter hover:text-accent transition">Nómina Automatizada</a></li>
                        <li><a href="#" class="text-lighter hover:text-accent transition">Reportes Financieros</a></li>
                        <li><a href="#" class="text-lighter hover:text-accent transition">Auditoría</a></li>
                    </ul>
                </div>

                <!-- Columna 4 -->
                <div>
                    <h3 class="text-lg font-bold mb-6 text-light">Contacto</h3>
                    <ul class="space-y-3 text-lighter">
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt mt-1 mr-3 text-accent"></i>
                            <span>Av. Principal 123, Ciudad Empresarial</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-phone-alt mr-3 text-accent"></i>
                            <span>+1 (555) 123-4567</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-envelope mr-3 text-accent"></i>
                            <span>info@mayasof.com</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-clock mr-3 text-accent"></i>
                            <span>Lun-Vie: 9:00 AM - 6:00 PM</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-secondary pt-8 text-center text-lighter">
                <p>&copy; {{ new Date().getFullYear() }} Mayasof. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>
</template>

<script>
export default {
    name: 'FooterComponent'
}
</script>
