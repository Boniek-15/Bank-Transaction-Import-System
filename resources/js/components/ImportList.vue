<script setup lang="ts">

const props = defineProps<{ imports: any[]}>()
const emit = defineEmits<{ selected: [id: number] }>()

const formatter = new Intl.DateTimeFormat('en-EN',{
    dateStyle: "short",
    timeStyle: "short",
});

function formatDate(d)
{
    const temp = new Date(d)
    return formatter.format(temp)
}
</script>

<template>
    <div  class="import-table">
        <h2>Import history</h2>
        <table>
            <tr>
                <th>File name</th>
                <th>Total records</th>
                <th>Successful records</th>
                <th>Failed records</th>
                <th>Status</th>
<!--                <th>Created time</th>-->
            </tr>
            <tr v-for="item in imports" :key="item.id" class="table-rows" @click="emit('selected',item.id)">
                <td>{{ item.file_name }}</td>
                <td>{{ item.total_records }}</td>
                <td>{{ item.successful_records }}</td>
                <td>{{ item.failed_records }}</td>
                <td :class='item.status'>{{ item.status }}</td>
<!--                <td>{{ formatDate(item.created_at) }}</td>-->
            </tr>
        </table>
    </div>
</template>

<style scoped>

</style>
