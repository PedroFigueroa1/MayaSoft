<template>
  <Layout>
    <div class="px-6 py-4 bg-white rounded-lg shadow">
      <h1 class="text-2xl font-bold text-gray-800">Estados Financieros</h1>

      <!-- Formulario para generar nuevos reportes -->
      <div class="mt-6 bg-gray-50 p-4 rounded-lg">
        <h2 class="text-lg font-semibold mb-4">Generar Nuevo Reporte</h2>
        <form @submit.prevent="generarReporte" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Período Contable</label>
              <select v-model="form.periodo_id" required class="w-full rounded-md border-gray-300 shadow-sm">
                <option value="">Seleccione un período</option>
                <option v-for="periodo in periodos" :key="periodo.id" :value="periodo.id">
                  {{ periodo.nombre }} - {{ periodo.empresa.nombre }} ({{ formatDate(periodo.fecha_inicio) }} al {{ formatDate(periodo.fecha_cierre) }})
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Reporte</label>
              <select v-model="form.tipo" required class="w-full rounded-md border-gray-300 shadow-sm">
                <option value="">Seleccione un tipo</option>
                <option v-for="tipo in tiposReporte" :key="tipo.value" :value="tipo.value">
                  {{ tipo.label }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
              <input v-model="form.observaciones" type="text" class="w-full rounded-md border-gray-300 shadow-sm">
            </div>
          </div>

          <div class="flex justify-end space-x-3">
            <button type="button" @click="resetForm" class="btn-secondary">
              Cancelar
            </button>
            <button type="submit" class="btn-primary" :disabled="form.processing">
              <span v-if="form.processing">Generando...</span>
              <span v-else>Generar Reporte</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Listado de reportes generados -->
      <div class="mt-8">
        <h2 class="text-lg font-semibold mb-4">Reportes Generados</h2>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Período</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Empresa</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Generado por</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="reporte in reportes.data" :key="reporte.id">
                <td class="px-6 py-4 whitespace-nowrap">{{ reporte.periodo.nombre }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="badgeClass(reporte.tipo)">
                    {{ tipoReporte(reporte.tipo) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">{{ reporte.periodo.empresa.nombre }}</td>
                <td class="px-6 py-4 whitespace-nowrap">{{ reporte.generado_por.name }}</td>
                <td class="px-6 py-4 whitespace-nowrap">{{ formatDateTime(reporte.created_at) }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <a :href="route('estados-financieros.download', reporte.id)"
                     class="text-blue-600 hover:text-blue-900 mr-3">
                    Descargar
                  </a>
                  <button @click="confirmDelete(reporte)" class="text-red-600 hover:text-red-900">
                    Eliminar
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div class="mt-4 flex justify-between items-center">
          <div class="text-sm text-gray-700">
            Mostrando {{ reportes.from }} a {{ reportes.to }} de {{ reportes.total }} resultados
          </div>
          <Pagination :links="reportes.links" />
        </div>
      </div>
    </div>

    <!-- Modal de confirmación para sobrescribir -->
    <ConfirmationModal :show="showOverwriteModal" @close="showOverwriteModal = false">
      <template #title>
        Reporte existente
      </template>
      <template #content>
        Ya existe un reporte de este tipo para el período seleccionado. ¿Desea sobrescribirlo?
      </template>
      <template #footer>
        <button @click="showOverwriteModal = false" class="btn-secondary">
          Cancelar
        </button>
        <button @click="submitWithOverwrite" class="btn-primary ml-2">
          Sobrescribir
        </button>
      </template>
    </ConfirmationModal>
  </Layout>
</template>

<script>
import Layout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import { useForm } from '@inertiajs/vue3';

export default {
  components: { Layout, Pagination, ConfirmationModal },

  props: {
    periodos: Array,
    reportes: Object,
    tiposReporte: Array
  },

  data() {
    return {
      showOverwriteModal: false,
      form: useForm({
        periodo_id: '',
        tipo: '',
        observaciones: '',
        sobreescribir: false
      })
    }
  },

  methods: {
    generarReporte() {
      this.form.sobreescribir = false;
      this.form.post(route('estados-financieros.generar'), {
        onSuccess: () => this.resetForm(),
        onError: (errors) => {
          if (errors.overwrite) {
            this.showOverwriteModal = true;
          }
        }
      });
    },

    submitWithOverwrite() {
      this.form.sobreescribir = true;
      this.form.post(route('estados-financieros.generar'), {
        onSuccess: () => {
          this.showOverwriteModal = false;
          this.resetForm();
        }
      });
    },

    resetForm() {
      this.form.reset();
      this.form.clearErrors();
    },

    confirmDelete(reporte) {
      if (confirm('¿Está seguro de eliminar este reporte?')) {
        Inertia.delete(route('estados-financieros.destroy', reporte.id));
      }
    },

    formatDate(date) {
      return new Date(date).toLocaleDateString();
    },

    formatDateTime(datetime) {
      return new Date(datetime).toLocaleString();
    },

    tipoReporte(tipo) {
      const tipoObj = this.tiposReporte.find(t => t.value === tipo);
      return tipoObj ? tipoObj.label : tipo;
    },

    badgeClass(tipo) {
      const classes = {
        BG: 'bg-blue-100 text-blue-800',
        ER: 'bg-green-100 text-green-800',
        FL: 'bg-purple-100 text-purple-800',
        CP: 'bg-yellow-100 text-yellow-800'
      };
      return `px-2 py-1 rounded-full text-xs font-medium ${classes[tipo] || 'bg-gray-100 text-gray-800'}`;
    }
  }
}
</script>
