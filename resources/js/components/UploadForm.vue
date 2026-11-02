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
    console.log(file.value)
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
    <div class="input-field">
        <h2>Upload transaction file <small>(.csv, .json, .xml)</small></h2>
        <input type="file"
               id="upload"
               hidden
               accept=".csv, .xml, .json"
               @change="selectFile">
        <label for="upload" class="file-btn">Choose file</label>
        <span id="file-name">{{ file ? file.name : 'No file chosen' }}</span>
        <label></label>
        <button :disabled="loading" @click="submit()">Submit</button>
        <h4 v-if="error" class="error-upload">{{error}}</h4>
    </div>
</template>

<style scoped>

</style>
