<script setup>
import { onMounted, ref } from 'vue';
import axios from 'axios';
import { useTasks } from '@/Composables/useTasks';
import TaskColumn from '@/Components/TaskColumn.vue';
import CreateTaskModal from '@/Components/CreateTaskModal.vue';
import { toast } from 'vue3-toastify';

const props = defineProps({
  teamMembers: {
    type: Array,
    default: () => [],
  },
});

const showCreate = ref(false);

const {
  todoTasks,
  inProgressTasks,
  doneTasks,
  isLoading,
  loadTasks,
  createTask,
  updateTaskInline,
  moveTask,
  deleteTask,
  assignTask,
} = useTasks();

async function ensureApiKey() {
  const existing = window.localStorage.getItem('taskflow_api_key');
  if (existing) return existing;

  try {
    const { data } = await axios.get('/api-token');
    if (data.success && data.api_key) {
      window.localStorage.setItem('taskflow_api_key', data.api_key);
      return data.api_key;
    }
  } catch (e) {
    toast.error('Could not get API key. Please log in again.');
  }

  return null;
}

onMounted(async () => {
  const key = await ensureApiKey();
  if (!key) return;
  await loadTasks();
});

function handleMove({ id, status, position }) {
  moveTask(id, status, position);
}

function handleEdit({ task, fields }) {
  updateTaskInline(task.id, fields);
}

function handleDelete({ task }) {
  deleteTask(task.id);
}

function handleAssign({ task, userId }) {
  assignTask(task.id, userId);
}

async function handleCreate(payload) {
  const created = await createTask(payload);
  if (created) {
    showCreate.value = false;
  }
}

async function handleLogout() {
  try {
    await axios.post('/logout');
  } catch (e) {
    toast.error('There was a problem logging you out. Please try again.');
    return;
  }

  window.localStorage.removeItem('taskflow_api_key');
  toast.success('You have been logged out');
  window.location.href = '/login';
}
</script>

<template>
  <div class="min-h-screen bg-slate-100 dark:bg-slate-950">
    <div class="max-w-6xl mx-auto px-4 py-6 space-y-4">
      <!-- Header bar -->
      <header class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="text-lg font-semibold">TaskFlow</span>
          <span class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-700">
            MediaHaus Squad
          </span>
        </div>

        <div class="flex items-center gap-3">
          <input
            type="search"
            placeholder="Search tasks..."
            class="w-48 text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-400"
          />

          <button
            type="button"
            class="text-sm px-3 py-1.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 active:scale-95 transition"
            @click="showCreate = true"
          >
            + New
          </button>

          <div class="flex items-center gap-2">
            <div
              class="w-8 h-8 rounded-full bg-gradient-to-tr from-emerald-500 to-blue-500 flex items-center justify-center text-xs font-semibold text-white"
            >
              U
            </div>

            <button
              type="button"
              class="text-xs px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 active:scale-95 transition"
              @click="handleLogout"
            >
              Logout
            </button>
          </div>
        </div>
      </header>

      <!-- Columns -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6 mt-4">
        <TaskColumn
          title="To Do"
          status="todo"
          :tasks="todoTasks"
          :is-loading="isLoading"
          :team-members="props.teamMembers"
          @move-task="handleMove"
          @edit-task="handleEdit"
          @delete-task="handleDelete"
          @assign-task="handleAssign"
        />
        <TaskColumn
          title="In Progress"
          status="in_progress"
          :tasks="inProgressTasks"
          :is-loading="isLoading"
          :team-members="props.teamMembers"
          @move-task="handleMove"
          @edit-task="handleEdit"
          @delete-task="handleDelete"
          @assign-task="handleAssign"
        />
        <TaskColumn
          title="Done"
          status="done"
          :tasks="doneTasks"
          :is-loading="isLoading"
          :team-members="props.teamMembers"
          @move-task="handleMove"
          @edit-task="handleEdit"
          @delete-task="handleDelete"
          @assign-task="handleAssign"
        />
      </div>
    </div>

    <CreateTaskModal
      :show="showCreate"
      :team-members="props.teamMembers"
      @close="showCreate = false"
      @submit="handleCreate"
    />
  </div>
</template>
