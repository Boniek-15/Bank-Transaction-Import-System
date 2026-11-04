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
        const chunksArray = [];
        const chunkSize = 10 * 1024 * 1024;
        const fileSize = file.value.size;
        const chunks = Math.ceil(fileSize / chunkSize);
        for (let i = 0; i < chunks; i++) {
            const start = i * chunkSize;
            const end = Math.min(start + chunkSize, fileSize);

            chunksArray.push(file.value.slice(start, end));

        }
        await uploadFile(chunksArray)
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
    <div class="card bg-white rounded-[20px] py-[15px] px-5 m-[10px] w-2/5 h-fit">
        <h2>Upload transaction file <small>(.csv, .json, .xml)</small></h2>
        <input type="file"
               id="upload"
               hidden
               accept=".csv, .xml, .json"
               @change="selectFile">
        <label for="upload" class="bg-[#06BA63] text-white rounded-[10px] border border-white p-[10px] text-base cursor-pointer mr-[15px] hover:bg-[#059B53]">Choose file</label>
        <span id="mr-[15px]">{{ file ? file.name : 'No file chosen' }}</span>
        <label></label>
        <button :disabled="loading" @click="submit()">Submit</button>
        <h4 v-if="error" class="text-[red]">{{error}}</h4>
    </div>
</template>

<style scoped>

</style>
