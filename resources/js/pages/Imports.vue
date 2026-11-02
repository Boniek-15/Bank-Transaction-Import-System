<script setup lang="ts">
import { fetchImports } from '@/api';
import {ref, onMounted} from "vue";
import ImportList from "@/components/ImportList.vue";
import UploadForm from "@/components/UploadForm.vue";
import ImportLogs from "../components/ImportLogs.vue";

const imports = ref([]);
const error = ref();
const selectedId = ref<number | null>(null)

async function load() {
    try{
        imports.value = await fetchImports()
    }catch (exception)
    {
        error.value = exception;
    }
}

onMounted(load)


</script>

<template>
    <header>
        <h1>System Importu Transkacji Bankowych - SITB</h1>
    </header>
    <body>
        <div class="container-table-upload">
            <ImportList :imports="imports" @selected="(v) => { selectedId = v }" />
            <UploadForm @uploaded="load" />
        </div>
        <ImportLogs v-if="selectedId != null" :import-id="selectedId" />
    </body>
</template>

<style scoped>

</style>
