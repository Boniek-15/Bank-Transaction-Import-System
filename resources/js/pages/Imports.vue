<script setup lang="ts">
import Heading from "@/components/Heading.vue";
import { fetchImports } from '@/api';
import {ref, onMounted} from "vue";
import ImportList from "@/components/ImportList.vue";
import UploadForm from "@/components/UploadForm.vue";

const imports = ref([]);
const error = ref();

async function load() {
    try{
        imports.value = await fetchImports()
    }catch (exception)
    {
        error.value = exception;
    }
    console.log(imports.value)
}

onMounted(load)


</script>

<template>
    <heading title="Heading import VUE"></heading>
    <header>
        <h1>System Importu Transkacji Bankowych - SITB</h1>
    </header>
    <body>
        <ImportList :imports="imports" />
        <UploadForm @uploaded="load" />
    </body>
</template>

<style scoped>

</style>
