<script setup lang="ts">
import {ref} from "vue";
import { uploadFile } from '@/api';
const file = ref(null);
const loading = ref(false);
const error = ref('');
const emit = defineEmits(['uploaded'])
function selectFile(e)
{
    file.value = e.target.files[0];
}

async function submit()
{
    loading.value = true;
    error.value = '';

    try {
        await uploadFile(file.value)
        emit('uploaded')
    }
    catch (exception) {
        error.value = exception instanceof Error ? exception.message : 'Upload failed';
    }
    finally {
        loading.value = false
    }
}
</script>

<template>
    <input type="file"
           accept=".csv, .xml, .json"
           @change="selectFile">
    <button :disabled="loading" @click="submit()">Submit</button>
    <p v-if="error" style="color: red">{{error}}</p>
</template>

<style scoped>

</style>
