<script setup lang="ts">
import {ref, watch} from "vue";
import { fetchImport } from "@/api";

const props = defineProps<{ importId: number}>()
const logs = ref([]);
const loading = ref(false)
const error = ref<string | null>(null)

watch(
    () => props.importId,
    async (id) => {
        loading.value = true
        error.value = null
        try {
            const data = await fetchImport(id)
            logs.value = data.logs
        } catch (e) {
            error.value = 'Could not load logs'
            logs.value = []
        } finally {
            loading.value = false
        }
    },
    { immediate: true }
)
</script>

<template>
    <div class="error-logs">
        <h2>Error logs</h2>
        <p v-if="loading">Loading…</p>
        <p v-else-if="error">{{ error }}</p>
        <p v-else-if="logs.length === 0">No error logs for this import</p>
        <ul v-else>
            <li v-for="log in logs" :key="log.id">
                <span>{{ log.error_message }}</span> <small> Transaction: {{ log.transaction_id ?? '—' }}</small>
            </li>
        </ul>
    </div>
</template>

<style scoped>

</style>
