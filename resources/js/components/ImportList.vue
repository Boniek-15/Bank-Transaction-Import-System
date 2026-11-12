<script setup lang="ts">
import DataTable, { Column } from 'datatables.net-vue3';
import DtCore, { Options } from 'datatables.net';
import { router } from '@inertiajs/vue3'
import { ref,onMounted,onUnmounted } from 'vue'

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
const currentPage = ref(1)
const interval = ref();

onMounted(() => {
    const dt = table.value.dt
    dt.on('click', 'tbody tr', (e) => {
        const row = dt.row(e.currentTarget).data()
        if (row) emit('selected', row.id)
    })
    dt.on('page',function() {
        var info = dt.page.info()
        currentPage.value = info.page + 1
    })
    interval.value = setInterval(() => {
        router.reload({
            only: ['imports'],
            onSuccess: (page) => {
                const newData = page.props.imports

                dt.clear()
                dt.rows.add(newData)
                dt.draw(false)
            }
        })
    }, 2000)
})

onUnmounted(() => {
   clearInterval(interval.value);
});

function formatDate(d)
{
    const temp = new Date(d)
    return formatter.format(temp)
}

</script>

<template>
    <div  class="card bg-white rounded-[20px] py-[15px] px-5 m-[10px] w-3/5">
        <h2>Import history</h2>

        <DataTable ref="table" :data="props.imports" :options="options" class="display" />
        <p>Current page: {{currentPage}}</p>
    </div>
</template>
