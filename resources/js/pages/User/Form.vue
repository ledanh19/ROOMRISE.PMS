<template>
    <Layout>
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="row mt-1 mb-3">
                <div class="col-md-12">
                    <h5>
                        <Link href="/users" class="text-muted fw-light"
                            >User Management <small> <i class="ti ti-chevron-right me-1" style="margin-top: -6px"></i> </small
                        ></Link>
                        <span v-if="user">Edit User</span>
                        <span v-else>Create New User</span>
                    </h5>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <form @submit.prevent="user ? form.put(`/users/${user.id}`) : form.post('/users')" class="row g-3">
                        <div class="col-12">
                            <h5 class="fw-semibold">User Info</h5>
                            <hr class="mt-0 mb-0" />
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Name<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" :class="{ 'is-invalid': form.errors.name }" v-model="form.name" placeholder="Name" />
                            <div v-if="form.errors.name" class="invalid-feedback">{{ form.errors.name }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Username<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" :class="{ 'is-invalid': form.errors.username }" v-model="form.username" placeholder="Username" />
                            <div v-if="form.errors.username" class="invalid-feedback">{{ form.errors.username }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number<span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" :class="{ 'is-invalid': form.errors.phone }" v-model="form.phone" placeholder="Phone Number" />
                            <div v-if="form.errors.phone" class="invalid-feedback">{{ form.errors.phone }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password<span v-if="!user" class="text-danger">*</span></label>
                            <input type="password" class="form-control" :class="{ 'is-invalid': form.errors.password }" autocomplete="password-new" v-model="form.password" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" />
                            <div v-if="form.errors.password" class="invalid-feedback">{{ form.errors.password }}</div>
                            <span class="form-label text-black-50">Leave empty if unchanged.</span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password Confirmation<span v-if="!user" class="text-danger">*</span></label>
                            <input type="password" class="form-control" :class="{ 'is-invalid': form.errors.password_confirmation }" v-model="form.password_confirmation" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" />
                            <div v-if="form.errors.password_confirmation" class="invalid-feedback">{{ form.errors.password_confirmation }}</div>
                            <span class="form-label text-black-50">Leave empty if unchanged.</span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role<span class="text-danger">*</span></label>
                            <select class="form-select select2" :class="{ 'is-invalid': form.errors.role }" v-model="form.role">
                                <option v-for="role in roles" :key="role.id" :value="role.name">
                                    {{ role.name }}
                                </option>
                            </select>
                            <div v-if="form.errors.role" class="invalid-feedback">{{ form.errors.role }}</div>
                        </div>

                        <div class="col-12 mt-5">
                            <hr />
                        </div>

                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-primary waves-effect waves-light me-3" :disabled="form.processing">
                                <span v-if="user">Update</span>
                                <span v-else>Create</span>
                            </button>
                            <Link href="/users" class="btn btn-outline-secondary waves-effect waves-light">Discard</Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </Layout>
</template>

<script setup>
import Layout from "../../layouts/blank.vue";
import { useForm, Link } from "@inertiajs/vue3";
import { onMounted } from "vue";

const props = defineProps({
    user: Object,
    userRole: String,
    roles: Array,
});

const form = useForm({
    name: null,
    username: null,
    phone: null,
    role: null,
    password: null,
    password_confirmation: null,
});

onMounted(() => {
    if (props.user) {
        form.name = props.user.name;
        form.username = props.user.username;
        form.phone = props.user.phone;
        form.role = props.userRole;
    }
});
</script>
