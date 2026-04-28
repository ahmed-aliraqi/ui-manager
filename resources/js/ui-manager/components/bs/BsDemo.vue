<template>
  <!-- BsWrapper adds .uim-bs scope — Bootstrap styles apply only inside it -->
  <BsWrapper class="p-3">

    <!-- Alerts -->
    <BsAlert variant="info" dismissible>
      Bootstrap 5 components scoped inside <code>.uim-bs</code> — no AdminLTE conflicts.
    </BsAlert>

    <!-- Buttons -->
    <div class="d-flex gap-2 flex-wrap mb-3">
      <BsButton variant="primary">Primary</BsButton>
      <BsButton variant="secondary">Secondary</BsButton>
      <BsButton variant="success">Success</BsButton>
      <BsButton variant="danger">Danger</BsButton>
      <BsButton variant="outline-primary">Outline</BsButton>
      <BsButton variant="primary" size="sm">Small</BsButton>
      <BsButton variant="primary" :loading="true">Loading</BsButton>
    </div>

    <!-- Badges -->
    <div class="d-flex gap-2 mb-3">
      <BsBadge variant="primary">Primary</BsBadge>
      <BsBadge variant="success" pill>Success Pill</BsBadge>
      <BsBadge variant="danger">12</BsBadge>
    </div>

    <!-- Progress -->
    <BsProgress :value="65" variant="primary" striped animated show-label class="mb-3" />

    <!-- Card + Form -->
    <BsCard title="Sample Form" class="mb-3">
      <BsFormGroup label="Name" id="demo-name" required>
        <BsInput id="demo-name" v-model="form.name" placeholder="Enter your name" />
      </BsFormGroup>

      <BsFormGroup label="Role" id="demo-role">
        <BsSelect
          id="demo-role"
          v-model="form.role"
          placeholder="Select a role..."
          :options="[
            { value: 'admin', label: 'Administrator' },
            { value: 'editor', label: 'Editor' },
            { value: 'viewer', label: 'Viewer' },
          ]"
        />
      </BsFormGroup>

      <div class="d-flex gap-2">
        <BsButton variant="primary" @click="showModal = true">Open Modal</BsButton>
        <BsDropdown label="More Actions" variant="outline-secondary">
          <li><a class="dropdown-item" href="#">Edit</a></li>
          <li><a class="dropdown-item" href="#">Duplicate</a></li>
          <li><hr class="dropdown-divider" /></li>
          <li><a class="dropdown-item text-danger" href="#">Delete</a></li>
        </BsDropdown>
      </div>
    </BsCard>

    <!-- Table -->
    <BsCard no-body>
      <BsWrapper class="card-body pb-0">
        <h6 class="card-title">Users</h6>
      </BsWrapper>
      <BsTable :columns="['Name', 'Role', 'Status']" hover striped>
        <tr v-for="row in rows" :key="row.name">
          <td>{{ row.name }}</td>
          <td>{{ row.role }}</td>
          <td><BsBadge :variant="row.active ? 'success' : 'secondary'" pill>{{ row.active ? 'Active' : 'Inactive' }}</BsBadge></td>
        </tr>
      </BsTable>
    </BsCard>

    <!-- Modal (teleported to #ui-manager-app, inside .uim-bs scope) -->
    <BsModal v-model="showModal" title="Confirm Action" size="sm">
      <p>Are you sure you want to proceed?</p>
      <template #footer>
        <BsButton variant="secondary" @click="showModal = false">Cancel</BsButton>
        <BsButton variant="primary" @click="showModal = false">Confirm</BsButton>
      </template>
    </BsModal>

  </BsWrapper>
</template>

<script setup>
import { ref } from 'vue'
import {
  BsWrapper, BsButton, BsAlert, BsBadge, BsCard,
  BsModal, BsProgress, BsFormGroup, BsInput, BsSelect,
  BsTable, BsDropdown,
} from './index.js'

const showModal = ref(false)
const form = ref({ name: '', role: '' })
const rows = [
  { name: 'Ahmed Fathy', role: 'Admin', active: true },
  { name: 'Nada Ibrahim', role: 'Editor', active: true },
  { name: 'Ali Hassan', role: 'Viewer', active: false },
]
</script>
