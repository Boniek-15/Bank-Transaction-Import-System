<script setup lang="ts">

const props = defineProps<{ imports: any[]}>()

</script>

<template>
    <table>
        <tr>
            <th>File name</th>
            <th>Total records</th>
            <th>Successful records</th>
            <th>Failed records</th>
            <th>Status</th>
        </tr>
        <tr v-for="item in imports" :key="item.id">
            <td>{{ item.file_name }}</td>
            <td>{{ item.total_records }}</td>
            <td>{{ item.successful_records }}</td>
            <td>{{ item.failed_records }}</td>
            <td>{{ item.status }}</td>
        </tr>
    </table>
</template>

<style scoped>

</style>
