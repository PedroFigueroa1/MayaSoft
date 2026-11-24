<script setup>
import AppLayoutInicio from '@/Layouts/AppLayoutInicio.vue';
</script>

<template>
  <AppLayoutInicio>
    <!-- Sección Hero -->
    <section class="hero-pattern bg-secondary text-white">
      <div class="container mx-auto px-4 py-20 md:py-32">
        <div class="flex flex-col md:flex-row items-center">
          <div class="md:w-1/2 mb-12 md:mb-0">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6">
              Transforma tu <span class="text-accent">contabilidad</span> con tecnología
            </h1>
            <p class="text-lighter text-xl mb-8">
              Mayasof es el sistema de contabilidad que simplifica tus procesos financieros, aumenta tu productividad y brinda información valiosa para la toma de decisiones.
            </p>
            <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
              <a href="#" class="btn-primary bg-accent text-primary font-semibold py-3 px-8 rounded-lg text-center">
                Comenzar ahora
              </a>
              <a href="#" class="btn-primary border-2 border-accent text-accent font-semibold py-3 px-8 rounded-lg text-center hover:bg-accent hover:text-primary">
                Ver demostración
              </a>
            </div>
          </div>
          <div class="md:w-1/2 flex justify-center">
            <div class="bg-secondary rounded-2xl p-6 shadow-2xl max-w-md w-full">
              <div class="bg-gray-800 h-8 rounded-t-lg flex items-center px-4">
                <div class="w-3 h-3 bg-red-500 rounded-full mr-2"></div>
                <div class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></div>
                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
              </div>
              <div class="bg-gray-900 p-4 rounded-b-lg">
                <div class="text-center mb-4">
                  <div class="inline-block bg-primary rounded-lg px-4 py-2">
                    <span class="text-accent">$</span>
                    <span class="text-light">Dashboard Financiero</span>
                  </div>
                </div>
                <div class="grid grid-cols-3 gap-4 mb-4">
                  <div class="bg-primary p-3 rounded-lg">
                    <div class="text-xs text-lighter mb-1">Ingresos</div>
                    <div class="text-accent font-bold">$42,560</div>
                  </div>
                  <div class="bg-primary p-3 rounded-lg">
                    <div class="text-xs text-lighter mb-1">Gastos</div>
                    <div class="text-accent font-bold">$18,230</div>
                  </div>
                  <div class="bg-primary p-3 rounded-lg">
                    <div class="text-xs text-lighter mb-1">Utilidad</div>
                    <div class="text-accent font-bold">$24,330</div>
                  </div>
                </div>
                <div class="h-40 bg-primary rounded-lg p-3">
                  <div class="flex items-end h-32 gap-2">
                    <div class="w-1/6 bg-accent h-20 rounded-t"></div>
                    <div class="w-1/6 bg-accent h-32 rounded-t"></div>
                    <div class="w-1/6 bg-accent h-24 rounded-t"></div>
                    <div class="w-1/6 bg-accent h-28 rounded-t"></div>
                    <div class="w-1/6 bg-accent h-36 rounded-t"></div>
                    <div class="w-1/6 bg-accent h-20 rounded-t"></div>
                  </div>
                  <div class="text-xs text-lighter text-center mt-2">Últimos 6 meses</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Sección Características -->
    <section class="py-20 bg-gray-50">
      <div class="container mx-auto px-4">
        <div class="text-center max-w-2xl mx-auto mb-16">
          <h2 class="text-3xl md:text-4xl font-bold mb-4 text-primary">
            Potencia tu gestión financiera
          </h2>
          <p class="text-gray-600">
            Con Mayasof, tienes todas las herramientas necesarias para una gestión contable eficiente y efectiva
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          <!-- Característica 1 -->
          <div class="feature-card bg-white rounded-xl shadow-lg p-8 transition duration-300">
            <div class="w-16 h-16 bg-accent/10 rounded-lg flex items-center justify-center mb-6">
              <i class="fas fa-file-invoice-dollar text-accent text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold mb-3 text-primary">Facturación Electrónica</h3>
            <p class="text-gray-600 mb-4">
              Emite y recibe facturas electrónicas en segundos, totalmente integradas con tu contabilidad.
            </p>
            <a href="#" class="text-accent font-semibold flex items-center">
              Saber más
              <i class="fas fa-arrow-right ml-2 text-sm"></i>
            </a>
          </div>

          <!-- Característica 2 -->
          <div class="feature-card bg-white rounded-xl shadow-lg p-8 transition duration-300">
            <div class="w-16 h-16 bg-accent/10 rounded-lg flex items-center justify-center mb-6">
              <i class="fas fa-chart-line text-accent text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold mb-3 text-primary">Reportes en Tiempo Real</h3>
            <p class="text-gray-600 mb-4">
              Accede a estados financieros, balances y reportes personalizados en cualquier momento.
            </p>
            <a href="#" class="text-accent font-semibold flex items-center">
              Saber más
              <i class="fas fa-arrow-right ml-2 text-sm"></i>
            </a>
          </div>

          <!-- Característica 3 -->
          <div class="feature-card bg-white rounded-xl shadow-lg p-8 transition duration-300">
            <div class="w-16 h-16 bg-accent/10 rounded-lg flex items-center justify-center mb-6">
              <i class="fas fa-lock text-accent text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold mb-3 text-primary">Seguridad Garantizada</h3>
            <p class="text-gray-600 mb-4">
              Tus datos están protegidos con cifrado de última generación y respaldos automáticos.
            </p>
            <a href="#" class="text-accent font-semibold flex items-center">
              Saber más
              <i class="fas fa-arrow-right ml-2 text-sm"></i>
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- Sección Testimonios -->
    <section class="py-20 bg-secondary">
      <div class="container mx-auto px-4">
        <div class="text-center max-w-2xl mx-auto mb-16">
          <h2 class="text-3xl md:text-4xl font-bold mb-4 text-light">
            Confiado por cientos de empresas
          </h2>
          <p class="text-lighter">
            Descubre lo que nuestros clientes dicen sobre nuestra solución contable
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          <!-- Testimonio 1 -->
          <div class="testimonial-card bg-primary rounded-xl shadow-lg p-8">
            <div class="flex items-center mb-6">
              <div class="w-12 h-12 rounded-full bg-accent flex items-center justify-center text-primary font-bold mr-4">
                MF
              </div>
              <div>
                <h4 class="font-bold text-light">María Fernández</h4>
                <p class="text-lighter text-sm">Contadora, FinanzasPro</p>
              </div>
            </div>
            <p class="text-lighter mb-4">
              "Mayasof ha transformado completamente nuestra gestión contable. Ahora podemos cerrar los libros mensuales en un 50% menos de tiempo."
            </p>
            <div class="flex text-accent">
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
            </div>
          </div>

          <!-- Testimonio 2 -->
          <div class="testimonial-card bg-primary rounded-xl shadow-lg p-8">
            <div class="flex items-center mb-6">
              <div class="w-12 h-12 rounded-full bg-accent flex items-center justify-center text-primary font-bold mr-4">
                CR
              </div>
              <div>
                <h4 class="font-bold text-light">Carlos Rodríguez</h4>
                <p class="text-lighter text-sm">CEO, Innovatec SA</p>
              </div>
            </div>
            <p class="text-lighter mb-4">
              "La capacidad de acceder a reportes financieros en tiempo real ha mejorado nuestra toma de decisiones estratégicas significativamente."
            </p>
            <div class="flex text-accent">
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
            </div>
          </div>

          <!-- Testimonio 3 -->
          <div class="testimonial-card bg-primary rounded-xl shadow-lg p-8">
            <div class="flex items-center mb-6">
              <div class="w-12 h-12 rounded-full bg-accent flex items-center justify-center text-primary font-bold mr-4">
                LP
              </div>
              <div>
                <h4 class="font-bold text-light">Laura Pérez</h4>
                <p class="text-lighter text-sm">Gerente, Consultores Asociados</p>
              </div>
            </div>
            <p class="text-lighter mb-4">
              "La implementación fue sencilla y el equipo de soporte siempre está disponible para resolver cualquier duda. ¡Altamente recomendado!"
            </p>
            <div class="flex text-accent">
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star-half-alt"></i>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Sección CTA -->
    <section class="py-20 bg-gray-50">
      <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto bg-gradient-to-r from-primary to-secondary rounded-2xl shadow-xl p-10 text-center">
          <h2 class="text-3xl md:text-4xl font-bold mb-6 text-white">
            ¿Listo para transformar tu contabilidad?
          </h2>
          <p class="text-lighter text-xl mb-8 max-w-2xl mx-auto">
            Únete a miles de empresas que ya usan Mayasof para simplificar sus procesos financieros
          </p>
          <div class="flex flex-col sm:flex-row justify-center space-y-4 sm:space-y-0 sm:space-x-4">
            <a href="#" class="btn-primary bg-accent text-primary font-semibold py-3 px-8 rounded-lg">
              Comenzar prueba gratuita
            </a>
            <a href="#" class="btn-primary border-2 border-white text-white font-semibold py-3 px-8 rounded-lg hover:bg-white hover:text-primary">
              Solicitar demostración
            </a>
          </div>
        </div>
      </div>
    </section>
  </AppLayoutInicio>
</template>

<style>
/* Estilos globales - puedes mover esto a tu archivo app.css principal */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

:root {
  --primary-dark: #081024ff;
  --primary-medium: #1d2d59;
  --primary-light: #2d3f7a;
  --accent-blue: #3b82f6;
  --accent-light: #60a5fa;
}

body {
  font-family: 'Poppins', sans-serif;
  color: #e2e8f0;
}

.sidebar-icon {
  @apply w-6 h-6 flex-shrink-0 transition duration-200;
}

.nav-link:hover .sidebar-icon {
  @apply text-accent-light transform scale-110;
}

.active-link {
  @apply bg-primary-light text-white;
}

.active-link .sidebar-icon {
  @apply text-white;
}

.notification-dot {
  @apply absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full;
}

.content-card {
  @apply bg-primary-dark rounded-xl shadow-xl p-6 border border-primary-medium;
}

.stat-card {
  @apply bg-primary-dark rounded-xl shadow-xl p-5 flex flex-col border border-primary-medium;
  transition: all 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
}

.top-gradient {
  background: linear-gradient(120deg, var(--primary-dark) 0%, var(--primary-medium) 100%);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
}

.sidebar-gradient {
  background: linear-gradient(180deg, var(--primary-dark) 0%, var(--primary-medium) 100%);
  box-shadow: 4px 0 12px rgba(0, 0, 0, 0.3);
}

.btn-primary {
  @apply bg-accent-blue hover:bg-accent-light text-white font-medium py-2 px-4 rounded-lg transition-all duration-300 shadow-md;
}

.btn-secondary {
  @apply bg-primary-medium hover:bg-primary-light text-blue-200 font-medium py-2 px-4 rounded-lg transition-all duration-300;
}

.input {
  @apply w-full px-4 py-2 border border-primary-medium rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent bg-primary-dark text-white;
}

.badge {
  @apply inline-block px-2 py-1 text-xs font-semibold rounded-full;
}

.badge-primary {
  @apply bg-blue-900 text-blue-200;
}

.badge-success {
  @apply bg-green-900 text-green-200;
}

.badge-warning {
  @apply bg-amber-900 text-amber-200;
}

.badge-neutral {
  @apply bg-gray-800 text-gray-200;
}

.slide-in {
  animation: slideIn 0.3s ease-out forwards;
}

.slide-out {
  animation: slideOut 0.3s ease-out forwards;
}

@keyframes slideIn {
  from { transform: translateX(-100%); }
  to { transform: translateX(0); }
}

@keyframes slideOut {
  from { transform: translateX(0); }
  to { transform: translateX(-100%); }
}

.menu-collapsed .link-text {
  display: none;
}

.menu-collapsed .badge {
  display: none;
}

.menu-collapsed {
  width: 80px !important;
}

.menu-collapsed ~ main {
  margin-left: 80px !important;
}

.text-gradient {
  background: linear-gradient(90deg, #3b82f6 0%, #60a5fa 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}
</style>
