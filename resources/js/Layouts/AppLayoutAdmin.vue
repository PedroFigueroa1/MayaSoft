<template>
  <div class="flex flex-col h-screen bg-gray-100">
    <!-- Barra superior -->
    <header class="bg-top-gradient shadow-lg py-3 px-6 flex items-center justify-between sticky top-0 z-20">
      <div class="flex items-center">
        <button @click="toggleSidebar" class="mr-4 text-blue-300 hover:text-white transition-colors">
          <i class="fas fa-bars text-xl"></i>
        </button>
        <div class="flex items-center">
          <div class="w-10 h-10 rounded-full bg-primary-light flex items-center justify-center text-white font-bold mr-3 shadow">
            <span class="text-gradient">M</span>
          </div>
          <h1 class="text-xl font-bold text-white">{{ currentPageTitle }}</h1>
        </div>
      </div>

      <div class="flex items-center space-x-4">
        <!-- ===== EMPRESA ACTIVA ===== -->
        <div class="relative" ref="empresaRef">
          <!-- Con empresa seleccionada -->
          <button
            v-if="empresaActual"
            type="button"
            @click="showEmpresaDropdown = !showEmpresaDropdown"
            class="flex items-center gap-2 pl-2 pr-3 py-1.5 text-sm text-white bg-white/10 hover:bg-white/20 border border-white/20 rounded-lg transition"
            title="Empresa en la que estás trabajando"
          >
            <img
              v-if="empresaActual.logo"
              :src="`/storage/${empresaActual.logo}`"
              :alt="empresaActual.nombre"
              class="w-7 h-7 rounded-full object-cover border border-white/30 bg-white"
            />
            <span v-else class="w-7 h-7 rounded-full bg-accent-blue flex items-center justify-center text-xs font-bold">
              {{ iniciales(empresaActual.nombre) }}
            </span>
            <span class="hidden sm:flex flex-col items-start leading-tight text-left">
              <span class="text-[10px] uppercase tracking-wide text-blue-300">Empresa</span>
              <span class="max-w-[180px] truncate font-semibold">{{ empresaActual.nombre }}</span>
            </span>
            <i class="fas fa-chevron-down text-xs text-blue-300"></i>
          </button>

          <!-- Sin empresa seleccionada -->
          <Link
            v-else
            href="/empresa"
            class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-amber-900 bg-amber-300 hover:bg-amber-200 rounded-lg shadow transition"
          >
            <i class="fas fa-exclamation-triangle"></i>
            <span class="hidden sm:inline">Sin empresa — Seleccionar</span>
            <span class="sm:hidden">Elegir</span>
          </Link>

          <!-- Desplegable para cambiar de empresa -->
          <div
            v-if="empresaActual && showEmpresaDropdown"
            class="absolute right-0 mt-2 w-72 bg-primary-dark rounded-lg shadow-xl py-2 z-30 border border-primary-medium"
          >
            <div class="px-4 py-2 text-[11px] uppercase tracking-wider text-blue-300">Cambiar de empresa</div>
            <div class="max-h-64 overflow-y-auto">
              <button
                v-for="e in empresasUsuario"
                :key="e.id"
                type="button"
                @click="cambiarEmpresa(e)"
                class="w-full flex items-center justify-between px-4 py-2 text-sm text-left transition-colors"
                :class="e.id === empresaActual.id
                  ? 'bg-primary-light text-white font-semibold'
                  : 'text-blue-200 hover:bg-primary-light hover:text-white'"
              >
                <span class="truncate">{{ e.nombre }}</span>
                <i v-if="e.id === empresaActual.id" class="fas fa-check-circle text-green-400 ml-2"></i>
              </button>
            </div>
            <div class="border-t border-primary-medium mt-1 pt-1">
              <Link
                href="/empresa"
                class="block px-4 py-2 text-sm text-blue-200 hover:bg-primary-light hover:text-white transition-colors"
                @click="showEmpresaDropdown = false"
              >
                <i class="fas fa-list mr-2"></i> Ver todas las empresas
              </Link>
            </div>
          </div>
        </div>

        <!-- Notificaciones -->
        <div class="relative">
          <button class="text-blue-300 hover:text-white transition-colors relative">
            <i class="far fa-bell text-xl"></i>
            <span v-if="notificationsCount > 0" class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full"></span>
          </button>
        </div>

        <!-- Usuario -->
        <div class="relative group">
          <button @click="toggleUserDropdown" class="flex items-center space-x-2 focus:outline-none">
            <div class="user-avatar w-10 h-10 rounded-full bg-primary-light flex items-center justify-center text-white font-bold shadow">
              {{ userInitial }}
            </div>
            <span class="text-blue-200 hidden md:inline-block">{{ userName }}</span>
            <i class="fas fa-chevron-down text-xs text-blue-300 transition-transform duration-200" :class="{ 'transform rotate-180': showUserDropdown }"></i>
          </button>

          <div v-show="showUserDropdown" class="absolute right-0 mt-2 w-56 bg-primary-dark rounded-lg shadow-xl py-2 z-30 border border-primary-medium">
            <div class="px-4 py-3 border-b border-primary-medium">
              <p class="text-sm font-bold text-white">{{ userName }}</p>
              <p class="text-xs text-blue-300">{{ userEmail }}</p>
            </div>
            <div class="py-1">
              <a href="#" class="block px-4 py-2 text-sm text-blue-200 hover:bg-primary-light hover:text-white transition-colors">
                <i class="fas fa-user-circle mr-2"></i> Perfil
              </a>
              <a href="#" class="block px-4 py-2 text-sm text-blue-200 hover:bg-primary-light hover:text-white transition-colors">
                <i class="fas fa-cog mr-2"></i> Configuración
              </a>
            </div>
            <div class="py-1 border-t border-primary-medium">
              <a href="#" @click.prevent="logout" class="block px-4 py-2 text-sm text-red-400 hover:bg-red-900/30 transition-colors">
                <i class="fas fa-sign-out-alt mr-2"></i> Cerrar sesión
              </a>
            </div>
          </div>
        </div>
      </div>
    </header>

    <div class="flex flex-1 overflow-hidden">
      <!-- Menú lateral -->
      <aside
        id="sidebar"
        class="bg-sidebar-gradient h-full flex flex-col justify-between fixed z-10 transition-all duration-300 ease-in-out"
        :class="{
          '-translate-x-full md:translate-x-0': !sidebarCollapsed && isMobile,
          'w-64': !sidebarCollapsed,
          'w-20': sidebarCollapsed
        }"
      >
        <div class="p-4 overflow-y-auto">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-white flex items-center whitespace-nowrap overflow-hidden">
              <i class="fas fa-cube mr-3 text-accent-blue"></i>
              <span v-show="!sidebarCollapsed" class="text-gradient">AdminPro</span>
            </h2>
            <button
              @click="toggleSidebarCollapse"
              class="text-blue-300 hover:text-white transition-colors p-1"
              :title="sidebarCollapsed ? 'Expandir menú' : 'Colapsar menú'"
            >
              <i class="fas" :class="sidebarCollapsed ? 'fa-chevron-right' : 'fa-chevron-left'"></i>
            </button>
          </div>

          <!-- Empresa en el sidebar -->
          <Link
            href="/empresa"
            class="block mb-4 rounded-lg border transition"
            :class="[
              empresaActual
                ? 'bg-primary-light/40 border-primary-light hover:bg-primary-light/60'
                : 'bg-amber-400/15 border-amber-400/50 hover:bg-amber-400/25',
              sidebarCollapsed ? 'p-2 flex justify-center' : 'p-3'
            ]"
            :title="empresaActual ? empresaActual.nombre : 'Selecciona una empresa'"
          >
            <template v-if="sidebarCollapsed">
              <span
                class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white"
                :class="empresaActual ? 'bg-accent-blue' : 'bg-amber-500'"
              >
                {{ empresaActual ? iniciales(empresaActual.nombre) : '?' }}
              </span>
            </template>
            <template v-else>
              <div class="text-[10px] uppercase tracking-wider" :class="empresaActual ? 'text-blue-300' : 'text-amber-300'">
                {{ empresaActual ? 'Trabajando en' : 'Sin empresa' }}
              </div>
              <div v-if="empresaActual">
                <div class="text-sm font-semibold text-white truncate">{{ empresaActual.nombre }}</div>
                <div class="text-[11px] text-blue-300">NIT {{ empresaActual.nit }}</div>
              </div>
              <div v-else class="text-sm font-medium text-amber-200">
                Selecciona una empresa <i class="fas fa-arrow-right ml-1 text-xs"></i>
              </div>
            </template>
          </Link>

          <nav class="space-y-1">
            <Link
              v-for="item in filteredMenu"
              :key="item.link"
              :href="item.link"
              @click="cerrarEnMovil"
              class="nav-link flex items-center py-3 px-4 rounded-lg transition-all duration-200"
              :class="{
                'active-link': esActivo(item),
                'hover:bg-primary-light': !esActivo(item),
                'justify-center': sidebarCollapsed
              }"
            >
              <i :class="`${item.icon} sidebar-icon ${item.iconColor}`"></i>
              <span v-show="!sidebarCollapsed" class="link-text ml-3 font-medium text-white whitespace-nowrap">
                {{ item.text }}
              </span>
            </Link>
          </nav>
        </div>

        <div class="p-4 border-t border-primary-medium">
          <p class="text-xs text-blue-400 whitespace-nowrap overflow-hidden" :title="`v${version} © ${new Date().getFullYear()} Mayasof`">
            <span v-show="!sidebarCollapsed">v{{ version }} &copy; {{ new Date().getFullYear() }} Mayasof</span>
            <span v-show="sidebarCollapsed" class="flex justify-center">v{{ version }}</span>
          </p>
        </div>
      </aside>

      <!-- Contenido principal -->
      <main
        id="main-content"
        class="flex-1 p-6 overflow-y-auto transition-all duration-300 ease-in-out bg-gray-50 text-gray-900"
        :class="{ 'ml-64': !sidebarCollapsed, 'ml-20': sidebarCollapsed }"
      >
        <!-- Aviso global si no hay empresa -->
        <div
          v-if="!empresaActual && !enPaginaEmpresas"
          class="max-w-7xl mx-auto mb-4 flex items-center justify-between gap-4 p-3 rounded-lg bg-amber-50 border border-amber-300 text-amber-900 text-sm"
        >
          <span><i class="fas fa-exclamation-triangle mr-2"></i> No has seleccionado una empresa para trabajar.</span>
          <Link href="/empresa" class="font-semibold underline">Seleccionar</Link>
        </div>

        <header v-if="$slots.header" class="max-w-7xl mx-auto mb-4">
          <slot name="header"></slot>
        </header>

        <div class="max-w-7xl mx-auto text-gray-900">
          <slot></slot>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import useAuth from '@/Composables/useAuth';

const page = usePage();
const { user, roles } = useAuth();

// Estado del layout
const sidebarCollapsed = ref(false);
const showUserDropdown = ref(false);
const showEmpresaDropdown = ref(false);
const empresaRef = ref(null);
const isMobile = ref(false);
const notificationsCount = ref(3);
const version = '2.4.1';

// Usuario
const userName = computed(() => user.value?.name || 'Usuario');
const userEmail = computed(() => user.value?.email || 'correo@no-disponible.com');
const userInitial = computed(() => user.value?.name?.charAt(0).toUpperCase() || 'U');

// Empresa
const empresaActual = computed(() => page.props.empresaActual ?? null);
const empresasUsuario = computed(() => page.props.empresasUsuario ?? []);
const enPaginaEmpresas = computed(() => page.url.startsWith('/empresa'));

// Menú
const menuItems = [
  { icon: 'fas fa-home', iconColor: 'text-blue-300', text: 'Dashboard', link: '/dashboard' },
  { icon: 'fas fa-users', iconColor: 'text-blue-300', text: 'Usuarios', link: '/admin/users', roles: ['SUPERADMIN'] },
  {
    icon: 'fas fa-building', iconColor: 'text-blue-300',
    text: 'Contabilidad',
    link: empresaActual.value ? `/empresa/${empresaActual.value.id}/periodos` : '/empresa',
    roles: ['SUPERADMIN','ADMIN','CONTADOR'],
  },
  { icon: 'fas fa-box', iconColor: 'text-blue-300', text: 'Inventario', link: '/inventario', roles: ['SUPERADMIN', 'ADMIN', 'CONTADOR'], requiereEmpresa: true },
  { icon: 'fas fa-chart-bar', iconColor: 'text-blue-300', text: 'Reportes', link: '/reportes', roles: ['SUPERADMIN', 'ADMIN', 'CONTADOR'], requiereEmpresa: true },
  { icon: 'fas fa-cog', iconColor: 'text-blue-300', text: 'Configuración', link: '/empresa', roles: ['SUPERADMIN', 'ADMIN', 'CONTADOR'] },
  { icon: 'fas fa-question-circle', iconColor: 'text-blue-300', text: 'Ayuda', link: '/ayuda', roles: ['SUPERADMIN', 'ADMIN', 'CONTADOR'] },
];

const filteredMenu = computed(() =>
  menuItems.filter((item) =>
    (!item.requiereEmpresa || empresaActual.value) &&
    (!item.roles || item.roles.some((r) => roles.value.includes(r)))
  )
);

const esActivo = (item) => page.url === item.link || page.url.startsWith(item.link + '/') || page.url.startsWith(item.link + '?');

const currentPageTitle = computed(
  () => filteredMenu.value.find(esActivo)?.text ?? 'Panel'
);

// Acciones
const iniciales = (n) =>
  (n || '?').trim().split(/\s+/).slice(0, 2).map((p) => p[0]).join('').toUpperCase();

const cambiarEmpresa = (e) => {
  showEmpresaDropdown.value = false;
  if (e.id === empresaActual.value?.id) return;
  if (!confirm(`¿Cambiar a «${e.nombre}»? Volverás al inicio.`)) return;
  router.post(route('empresas.cambiar'), { empresa_id: e.id });
};

const logout = () => router.post(route('logout'));

const toggleUserDropdown = () => { showUserDropdown.value = !showUserDropdown.value; };
const toggleSidebarCollapse = () => { sidebarCollapsed.value = !sidebarCollapsed.value; };
const toggleSidebar = () => toggleSidebarCollapse();
const cerrarEnMovil = () => { if (isMobile.value) sidebarCollapsed.value = true; };

const checkScreenSize = () => {
  isMobile.value = window.innerWidth < 768;
  if (isMobile.value) sidebarCollapsed.value = true;
};

const clicFuera = (e) => {
  if (empresaRef.value && !empresaRef.value.contains(e.target)) showEmpresaDropdown.value = false;
};

onMounted(() => {
  checkScreenSize();
  window.addEventListener('resize', checkScreenSize);
  document.addEventListener('click', clicFuera);
});
onUnmounted(() => {
  window.removeEventListener('resize', checkScreenSize);
  document.removeEventListener('click', clicFuera);
});
</script>

<style scoped>
.text-gradient {
  background: linear-gradient(90deg, #3b82f6 0%, #60a5fa 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
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
</style>
