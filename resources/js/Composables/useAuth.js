// resources/js/Composables/useAuth.js
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export default function useAuth() {
    const page = usePage(); // 👈 no usar .value aquí

    const user = computed(() => page.props.auth?.user || null);
    const roles = computed(() => user.value?.roles || []);
    const permissions = computed(() => user.value?.permissions || []);

    function hasRole(role) {
        return roles.value.includes(role);
    }

    function can(permission) {
        return permissions.value.includes(permission);
    }

    return {
        user,
        roles,
        permissions,
        hasRole,
        can,
    }
}
