<template>
  <div class="min-h-screen flex flex-col bg-gray-50 text-gray-800">
    <!-- Barra de navegación minimal -->
    <nav class="bg-primary text-white sticky top-0 z-50 shadow-sm">
      <div class="container mx-auto px-4 py-3">
        <div class="flex justify-between items-center">
          <!-- Logo -->
          <div class="flex items-center">
            <div class="bg-accent w-8 h-8 rounded-lg flex items-center justify-center mr-2">
              <i class="fas fa-calculator text-primary text-sm"></i>
            </div>
            <span class="font-medium text-lg tracking-tight">Mayasof Cloud</span>
          </div>

          <!-- Menú para escritorio -->
          <div class="hidden md:flex space-x-6">
            <a href="#inicio" class="text-gray-300 hover:text-white transition">Inicio</a>
            <a href="#caracteristicas" class="text-gray-300 hover:text-white transition">Características</a>
            <a href="#soporte" class="text-gray-300 hover:text-white transition">Soporte</a>
          </div>

          <!-- Botones de acción -->
          <div class="hidden md:flex items-center space-x-4">
            <Link href="/login" class="text-gray-300 hover:text-white transition">
              Iniciar sesión
            </Link>
            <Link :href="route('register')" class="bg-accent text-primary px-4 py-1.5 rounded-md text-sm font-medium hover:bg-accent/90 transition">
              Registrarse
            </Link>
          </div>

          <!-- Menú móvil -->
          <button @click="toggleMobileMenu" class="md:hidden text-gray-300 focus:outline-none">
            <i class="fas fa-bars text-xl"></i>
          </button>
        </div>

        <!-- Menú móvil desplegable -->
        <div v-show="mobileMenuOpen" class="md:hidden py-4 border-t border-secondary mt-3">
          <a href="#inicio" class="block py-2 text-gray-300 hover:bg-secondary rounded transition">Inicio</a>
          <a href="#caracteristicas" class="block py-2 text-gray-300 hover:bg-secondary rounded transition">Características</a>
          <a href="#soporte" class="block py-2 text-gray-300 hover:bg-secondary rounded transition">Soporte</a>
          <div class="border-t border-secondary mt-3 pt-3 space-y-2">
            <Link href="/login" class="block py-2 text-center text-gray-300 hover:bg-secondary rounded transition">
              Iniciar sesión
            </Link>
            <Link href="/register" class="block py-2 text-center bg-accent text-primary rounded-md transition">
              Registrarse
            </Link>
          </div>
        </div>
      </div>
    </nav>

    <!-- Contenido principal -->
    <main class="flex-grow">
      <!-- Hero Section simplificada -->
      <section id="inicio" class="hero-pattern py-20">
        <div class="container mx-auto px-4 text-center">
          <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">
            Contabilidad en la nube
          </h1>
          <p class="text-lg text-gray-300 mb-8 max-w-2xl mx-auto">
            Gestiona tus finanzas desde cualquier lugar con nuestra plataforma especializada.
          </p>
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <Link :href="route('register')" class="bg-accent text-primary font-medium py-2 px-6 rounded-md hover:bg-accent/90 transition">
              Registrarse
            </Link>
            <a href="#caracteristicas" class="border border-accent text-accent font-medium py-2 px-6 rounded-md hover:bg-accent hover:text-primary transition">
              Conocer más
            </a>
          </div>
        </div>
      </section>

      <!-- Características esenciales -->
      <section id="caracteristicas" class="py-16 bg-white">
        <div class="container mx-auto px-4">
          <div class="text-center mb-12">
            <h2 class="text-2xl md:text-3xl font-semibold text-primary mb-2">
              Características
            </h2>
            <p class="text-gray-600 max-w-xl mx-auto">
              Funcionalidades diseñadas para simplificar tu gestión contable
            </p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Característica 1 -->
            <div class="bg-gray-50 p-6 rounded-xl border border-gray-100">
              <div class="bg-accent w-12 h-12 rounded-lg flex items-center justify-center mb-4">
                <i class="fas fa-bolt text-primary text-lg"></i>
              </div>
              <h3 class="text-lg font-semibold text-primary mb-2">Tiempo real</h3>
              <p class="text-gray-600 text-sm">Actualizaciones instantáneas de tus estados financieros.</p>
            </div>

            <!-- Característica 2 -->
            <div class="bg-gray-50 p-6 rounded-xl border border-gray-100">
              <div class="bg-accent w-12 h-12 rounded-lg flex items-center justify-center mb-4">
                <i class="fas fa-shield-alt text-primary text-lg"></i>
              </div>
              <h3 class="text-lg font-semibold text-primary mb-2">Seguridad</h3>
              <p class="text-gray-600 text-sm">Datos protegidos con encriptación y backups automáticos.</p>
            </div>

            <!-- Característica 3 -->
            <div class="bg-gray-50 p-6 rounded-xl border border-gray-100">
              <div class="bg-accent w-12 h-12 rounded-lg flex items-center justify-center mb-4">
                <i class="fas fa-sync text-primary text-lg"></i>
              </div>
              <h3 class="text-lg font-semibold text-primary mb-2">Sincronización</h3>
              <p class="text-gray-600 text-sm">Integración con bancos y sistemas tributarios.</p>
            </div>

            <!-- Característica 4 -->
            <div class="bg-gray-50 p-6 rounded-xl border border-gray-100">
              <div class="bg-accent w-12 h-12 rounded-lg flex items-center justify-center mb-4">
                <i class="fas fa-chart-line text-primary text-lg"></i>
              </div>
              <h3 class="text-lg font-semibold text-primary mb-2">Reportes</h3>
              <p class="text-gray-600 text-sm">Dashboards y reportes personalizados.</p>
            </div>

            <!-- Característica 5 -->
            <div class="bg-gray-50 p-6 rounded-xl border border-gray-100">
              <div class="bg-accent w-12 h-12 rounded-lg flex items-center justify-center mb-4">
                <i class="fas fa-mobile-alt text-primary text-lg"></i>
              </div>
              <h3 class="text-lg font-semibold text-primary mb-2">App móvil</h3>
              <p class="text-gray-600 text-sm">Gestiona tu contabilidad desde tu smartphone.</p>
            </div>

            <!-- Característica 6 -->
            <div class="bg-gray-50 p-6 rounded-xl border border-gray-100">
              <div class="bg-accent w-12 h-12 rounded-lg flex items-center justify-center mb-4">
                <i class="fas fa-headset text-primary text-lg"></i>
              </div>
              <h3 class="text-lg font-semibold text-primary mb-2">Soporte</h3>
              <p class="text-gray-600 text-sm">Asistencia técnica cuando la necesites.</p>
            </div>
          </div>
        </div>
      </section>

      <slot></slot>
    </main>

    <!-- Footer simplificado -->
    <footer class="bg-primary text-white pt-10 pb-6">
      <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
          <!-- Marca -->
          <div>
            <div class="flex items-center mb-4">
              <div class="bg-accent w-8 h-8 rounded-lg flex items-center justify-center mr-2">
                <i class="fas fa-calculator text-primary text-sm"></i>
              </div>
              <span class="font-medium text-lg">Mayasof Cloud</span>
            </div>
            <p class="text-gray-400 text-sm">
              Plataforma de contabilidad en la nube.
            </p>
          </div>

          <!-- Enlaces -->
          <div>
            <h3 class="text-md font-semibold mb-3 text-gray-200">Producto</h3>
            <ul class="space-y-1 text-sm">
              <li><a href="#caracteristicas" class="text-gray-400 hover:text-accent transition">Características</a></li>
              <li><a href="#" class="text-gray-400 hover:text-accent transition">Seguridad</a></li>
            </ul>
          </div>

          <div id="soporte">
            <h3 class="text-md font-semibold mb-3 text-gray-200">Soporte</h3>
            <ul class="space-y-1 text-sm text-gray-400">
              <li class="flex items-center">
                <i class="fas fa-envelope mr-2 text-accent text-xs"></i>
                <span>soporte@cloud.mayasof.com</span>
              </li>
              <li class="flex items-center">
                <i class="fas fa-headset mr-2 text-accent text-xs"></i>
                <span>Centro de ayuda</span>
              </li>
            </ul>
          </div>
        </div>

        <div class="border-t border-secondary pt-6 text-center text-gray-400 text-xs">
          <p>&copy; {{ new Date().getFullYear() }} Mayasof Cloud. Todos los derechos reservados.</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

export default {
  components: { Link },
  setup() {
    const mobileMenuOpen = ref(false);
    const toggleMobileMenu = () => { mobileMenuOpen.value = !mobileMenuOpen.value; };
    const closeMobileMenu = () => { mobileMenuOpen.value = false; };

    onMounted(() => {
      document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
          e.preventDefault();
          const target = document.querySelector(this.getAttribute('href'));
          if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            closeMobileMenu();
          }
        });
      });
    });

    return { mobileMenuOpen, toggleMobileMenu, closeMobileMenu };
  }
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
@import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');

body {
  font-family: 'Inter', sans-serif;
}

.hero-pattern {
  background: radial-gradient(circle at 10% 30%, #0a192f, #07101f);
}

.bg-primary { background-color: #0a192f; }
.bg-secondary { background-color: #112240; }
.bg-accent { background-color: #64ffda; }
.text-accent { color: #64ffda; }
.text-primary { color: #0a192f; }
.border-secondary { border-color: #112240; }

.transition {
  transition: all 0.2s ease;
}
</style>
