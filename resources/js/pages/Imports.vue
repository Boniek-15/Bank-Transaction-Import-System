<script setup lang="ts">
import { fetchImports } from '@/api';
import {ref, onMounted} from "vue";
import ImportList from "@/components/ImportList.vue";
import UploadForm from "@/components/UploadForm.vue";
import ImportLogs from "../components/ImportLogs.vue";
import {router, usePage} from '@inertiajs/vue3';

const imports = ref([]);
const error = ref();
const selectedId = ref(null)
const page = usePage()

async function load() {
    try{
        imports.value = await fetchImports()
    }catch (exception)
    {
        error.value = exception;
    }
}

function handleSelected(id) {
    selectedId.value = id
}

onMounted(load)

function logout() {
    router.post('/logout')
}

</script>

<template>
    <header class="card flex justify-between items-center">
        <h1>Bank Transaction Import System - BTIS  Najszybsze czasy: 2m 42s/3m 42s</h1>
        <div class="flex items-center">
            <h3 class="text-[#06BA63] text-xl font-bold mr-[10px]">{{page.props.auth.user.username}}</h3>
            <button @click="logout">Logout</button>
        </div>
    </header>
    <body>
        <div class="flex w-5/5">
            <ImportList :imports="imports" @selected="handleSelected" />
            <UploadForm @uploaded="load" />
        </div>
        <ImportLogs v-if="selectedId != null" :import-id="selectedId" />
    </body>
</template>

<style scoped>

</style>
