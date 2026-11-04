<script setup lang="ts">

import {Link, useForm} from '@inertiajs/vue3'

const form = useForm({
    username: '',
    password: '',
    password_confirmation: '',
});

function submit(){
    form.post('/register');
}


</script>

<template>
    <div class="absolute  w-1/3 h-2/3 top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
        <form @submit.prevent="submit" class="card flex flex-col justify-evenly items-center h-5/5">
            <h2>Create Account</h2>
            <h3>Username</h3>
            <input type="text" name="username" v-model="form.username" placeholder="SpoconyMaciuś123" class="p-[8px] w-4/5"/>
            <p v-if="form.errors.username" class="text-[red]">
                {{ form.errors.username }}
            </p>
            <h3>Password</h3>
            <input type="password" name="password" v-model="form.password" placeholder="********" class="p-[8px] w-4/5"/>
            <p v-if="form.errors.password" class="text-[red]">
                {{ form.errors.password }}
            </p>
            <h3>Confirm password</h3>
            <input type="password" name="password_confirmation" v-model="form.password_confirmation" placeholder="********" class="p-[8px] w-4/5"/>
            <p v-if="form.errors.password_confirmation" class="text-[red]">
                {{ form.errors.password_confirmation }}
            </p>
            <button type="submit" :disabled="form.processing" class="w-4/5">Create account</button>
            <Link href="/login">Have an account? Login</Link>
        </form>
    </div>
</template>

<style scoped>

</style>
