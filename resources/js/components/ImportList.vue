<script setup lang="ts">
import DataTable, { Column } from 'datatables.net-vue3';
import DtCore, { Options } from 'datatables.net';
import { ref,onMounted } from 'vue'

DataTable.use(DtCore);

const formatter = new Intl.DateTimeFormat('en-EN',{
    dateStyle: "short",
    timeStyle: "short",
});

const options = {
    pageLength: 10,
    columns: [
        { data: 'file_name', title: 'File name' },
        { data: 'total_records', title: 'Total records' },
        { data: 'successful_records', title: 'Successful records' },
        { data: 'failed_records', title: 'Failed records' },
        { data: 'status', title: 'Status' },
    ]
};

const props = defineProps({ imports: Array })
const emit = defineEmits(['selected'])

const table = ref()

onMounted(() => {
    const dt = table.value.dt   // property in v4; try table.value.dt() if undefined
    dt.on('click', 'tbody tr', (e) => {
        const row = dt.row(e.currentTarget).data()
        if (row) emit('selected', row.id)
    })
})

function formatDate(d)
{
    const temp = new Date(d)
    return formatter.format(temp)
}

</script>

<template>
    <div  class="card bg-white rounded-[20px] py-[15px] px-5 m-[10px] w-3/5">
        <h2>Import history</h2>

        <DataTable ref="table" :data="imports" :options="options" class="display" />

<!--        <table>-->
<!--            <tr>-->
<!--                <th>File name</th>-->
<!--                <th>Total records</th>-->
<!--                <th>Successful records</th>-->
<!--                <th>Failed records</th>-->
<!--                <th>Status</th>-->
<!--&lt;!&ndash;                <th>Created time</th>&ndash;&gt;-->
<!--            </tr>-->
<!--            <tr v-for="item in imports" :key="item.id" class="table-rows" @click="emit('selected',item.id)">-->
<!--                <td>{{ item.file_name }}</td>-->
<!--                <td>{{ item.total_records }}</td>-->
<!--                <td>{{ item.successful_records }}</td>-->
<!--                <td>{{ item.failed_records }}</td>-->
<!--                <td :class='item.status'>{{ item.status }}</td>-->
<!--&lt;!&ndash;                <td>{{ formatDate(item.created_at) }}</td>&ndash;&gt;-->
<!--            </tr>-->
<!--        </table>-->
    </div>
</template>
