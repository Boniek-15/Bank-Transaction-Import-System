<script setup lang="ts">

import { useForm,Link } from '@inertiajs/vue3'

const form = useForm({
    username: '',
    password: '',
});

function submit(){
    form.post('/login');
}


</script>

<template>
    <div class="absolute  w-1/3 h-2/5 top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
        <form @submit.prevent="submit" class="card flex flex-col justify-evenly items-center h-5/5">
            <h2>Login</h2>
            <input type="text" name="username" v-model="form.username" placeholder="Username" class="p-[8px] w-4/5"/>
            <p v-if="form.errors.username" class="text-[red]">
                {{ form.errors.username }}
            </p>
            <input type="password" name="password" v-model="form.password" placeholder="Password" class="p-[8px] w-4/5"/>
            <p v-if="form.errors.password" class="text-[red]">
                {{ form.errors.password }}
            </p>
            <button type="submit" :disabled="form.processing" class="w-4/5">Login</button>
            <Link href="/register">Don't have an account? Register</Link>
        </form>
    </div>
</template>

<style scoped>

</style>
