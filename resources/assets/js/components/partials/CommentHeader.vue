<template>
    <div class="bb-comment-header" v-if="data.attrs">

        <div class="bb-comment-header-top d-flex justify-content-between">
            <strong>{{ data.attrs.count_all }} {{ __('Comments') }}</strong>
            <dropdown
                @click="() => !isLogged && openLoginForm()"
                icon="fas fa-comment"
                :selected="{name: isLogged ? userData.name : __('Login')}"
                :options="isLogged && [
                    {name: __('Logout'), onClick: logout}
                ]"
                :no-select-mode="true"
            />
        </div>


        <div class="bb-comment-header-bottom d-flex justify-content-end">
            <dropdown
                :selectedValue="data.sort"
                v-on:updateOption="onChangeSort"
                :options="[
                    {name: __('Newest'), value: 'newest'},
                    {name: __('Best'), value: 'best'},
                    {name: __('Oldest'), value: 'oldest'}
                ]"
            />
        </div>
    </div>
</template>

<script>
import Http from '../../service/http';
import Ls from '../../service/Ls';
import Dropdown from "./Dropdown";

export default {
    name: 'Header',
    components: {
        Dropdown,
    },
    methods: {
        logout() {
            Http.post(this.logoutUrl).then(() => {
                Ls.remove('auth.token');
                this.getUser();
            });
        },
    },
    props: {
        recommend: {
            type: Object,
        }
    },
    computed: {
        isLogged() {
            return this.data.userData;
        },
        userData() {
            return this.data.userData ?? {}
        },
    },
    inject: ['reference', 'data', 'getUser', 'openLoginForm', 'onChangeSort', 'logoutUrl', 'recommendUrl']
}
</script>
