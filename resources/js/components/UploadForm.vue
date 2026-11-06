<script setup lang="ts">
import {ref} from "vue";
import { uploadFile } from '@/api';
import ImportController from "@/actions/App/Http/Controllers/ImportController";
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
    const headers = {Accept: 'application/json', 'Content-Type': 'application/json'}
    try {
        if (!file.value) {
            throw new Error('Please select a file');
        }
        const chunksArray = [];
        const chunkSize = 10 * 1024 * 1024;
        const fileSize = file.value.size;
        const chunks = Math.ceil(fileSize / chunkSize);
        const payload = {
            file_name: file.value.name,
            file_size: file.value.size,
            total_chunks: chunks
        };
        const body = JSON.stringify(payload)
        const res = await fetch('/api/imports/init', {method: 'POST', headers, body});
        const data = await res.json();

        if (!res.ok) {
            throw new Error(data.message ?? 'Upload initialization failed');
        }

        const uploadId = data.upload_id;

        if (!uploadId) {
            throw new Error('Upload ID was not returned');
        }
        for (let i = 0; i < chunks; i++) {
            const start = i * chunkSize;
            const end = Math.min(start + chunkSize, fileSize);
            chunksArray.push(file.value.slice(start, end));
        }
        await uploadFile(chunksArray,uploadId)
        const completeBody = JSON.stringify({
            upload_id: uploadId,
            total_chunks: chunks,
            file_name: file.value.name
        });

        const completeResponse = await fetch('/api/imports/complete', {
            method: 'POST',
            headers,
            body: completeBody
        });

        const completeData = await completeResponse.json();

        if (!completeResponse.ok) {
            throw new Error(completeData.message ?? 'Import failed');
        }
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
