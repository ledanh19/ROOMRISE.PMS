<script setup>
import { router } from "@inertiajs/vue3";
const props = defineProps({
  userData: {
    type: Object,
    required: true,
  },
})


const isUserInfoEditDialogVisible = ref(false)
const isUpgradePlanDialogVisible = ref(false)

const editlUser = async () => {
  router.visit(route('users.edit', props.userData.id));
};
</script>

<template>
  <VRow>
    <!-- SECTION User Details -->
    <VCol cols="12">
      <VCard v-if="props.userData">
        <VCardText class="d-flex justify-space-between align-center flex-wrap gap-4">
          <h5 class="text-h5">
            User Information
          </h5>
          <VBtn @click="editlUser">
            Edit
            <VIcon end icon="tabler-edit" />
          </VBtn>
        </VCardText>
        <VDivider class="mb-5" />
        <VCardText class="pt-5">
          <!-- 👉 Avatar -->
          <VAvatar rounded :size="100" :color="!props.userData.profile_photo_url ? 'primary' : undefined"
            :variant="!props.userData.profile_photo_url ? 'tonal' : undefined">
            <VImg v-if="props.userData.profile_photo_url" :src="props.userData.profile_photo_url" />
            <span v-else class="text-5xl font-weight-medium">
              {{ avatarText(props.userData.name) }}
            </span>
          </VAvatar>

          <!-- 👉 User fullName -->
          <h5 class="text-h5 mt-4">
            {{ props.userData.name }}
          </h5>
        </VCardText>

        <VCardText>
          <!-- 👉 Details -->
          <p class="text-sm text-disabled">
            ABOUT ME
          </p>

          <!-- 👉 User Details list -->
          <VList class="card-list mt-2">
            <VListItem>
              <div class="text-h6">
                First Name:
                <div class="d-inline-block text-body-1">
                  {{ props.userData.first_name }}
                </div>
              </div>
            </VListItem>
            <VListItem>
              <div class="text-h6">
                Last Name:
                <div class="d-inline-block text-body-1">
                  {{ props.userData.last_name }}
                </div>
              </div>
            </VListItem>
            <VListItem>
              <div class="text-h6">
                Role:
                <div class="d-inline-block text-body-1">
                  {{ props.userData.role }}
                </div>
              </div>
            </VListItem>
          </VList>
        </VCardText>
        <VCardText>
          <!-- 👉 Details -->
          <p class="text-sm text-disabled">
            CONTACTS
          </p>

          <!-- 👉 User Details list -->
          <VList class="card-list mt-2">

            <VListItem>
              <VListItemTitle>
                <span class="text-h6">
                  Email:
                </span>
                <span class="text-body-1">
                  {{ props.userData.email }}
                </span>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  Phone:
                  <div class="d-inline-block text-body-1 text-capitalize">
                    {{ props.userData.phone }}
                  </div>
                </h6>
              </VListItemTitle>
            </VListItem>
          </VList>
        </VCardText>
        <VCardText v-if="props.userData.usermeta.length > 0">
          <!-- 👉 Details -->
          <p class="text-sm text-disabled">
            OTHER INFORMATION
          </p>

          <!-- 👉 User Details list -->
          <VList class="card-list mt-2">
            <VListItem v-for="item in props.userData.usermeta">
              <VListItemTitle>
                <span class="text-h6 text-capitalize">
                  {{ item.meta_key }}:
                </span>
                <span class="text-body-1">
                  {{ item.meta_value }}
                </span>
              </VListItemTitle>
            </VListItem>
          </VList>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>

  <!-- 👉 Edit user info dialog -->
  <UserInfoEditDialog v-model:is-dialog-visible="isUserInfoEditDialogVisible" :user-data="props.userData" />

  <!-- 👉 Upgrade plan dialog -->
  <UserUpgradePlanDialog v-model:is-dialog-visible="isUpgradePlanDialogVisible" />
</template>

<style lang="scss" scoped>
.card-list {
  --v-card-list-gap: 0.5rem;
}

.text-capitalize {
  text-transform: capitalize !important;
}
</style>
