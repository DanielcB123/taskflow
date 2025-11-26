<script setup>
import { onMounted, ref, computed } from 'vue';
import axios from 'axios';
import { useTasks } from '@/Composables/useTasks';
import TaskColumn from '@/Components/TaskColumn.vue';
import CreateTaskModal from '@/Components/CreateTaskModal.vue';
import CommandPalette from '@/Components/CommandPalette.vue';
import { toast } from 'vue3-toastify';

const props = defineProps({
  auth: { type: Object, required: false, default: () => ({}) },
  teamMembers: {
    type: Array,
    default: () => [],
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
});

const showCreate = ref(false);
const showCommandPalette = ref(false);
const currentUserId = computed(() => props.auth?.user?.id ?? null);


const {
  tasks,
  todoTasks,
  inProgressTasks,
  doneTasks,
  isLoading,
  isCreating,
  filterAssigned,
  search,
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

/**
 * Command palette handlers
 */
function handleSetSearch(value) {
  search.value = value || '';
  loadTasks();
}

function handleSetFilterAssigned(value) {
  filterAssigned.value = value;
  loadTasks();
}

function handleSwitchColumn(status) {
  const el = document.querySelector(`[data-column="${status}"]`);
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
}

function handleMarkTaskDone(taskId) {
  updateTaskInline(taskId, { status: 'done' });
}
</script>

<template>
  <div class="min-h-screen bg-slate-100 dark:bg-slate-950">
    <div class="max-w-6xl mx-auto px-4 py-6 space-y-4">
      <!-- Header bar -->
      <header class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-2">
          <span class="text-lg font-semibold">TaskFlow</span>
          <span class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-700">
            MediaHaus Squad
          </span>
        </div>

        <div class="flex items-center gap-3">
          <!-- Search input is tied to search ref from useTasks -->
          <div class="flex items-center gap-2">
            <input
              v-model="search"
              type="search"
              placeholder="Search tasks..."
              class="w-48 text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-400"
              @keyup.enter="loadTasks"
            />
            <button
              type="button"
              class="hidden md:inline-flex items-center gap-1 text-[11px] px-2 py-1 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-500 dark:text-slate-300"
              @click="showCommandPalette = true"
            >
              <span class="text-xs text-slate-400">⌘K</span>
              <span>Command palette</span>
            </button>
          </div>

          <button
            type="button"
            class="text-sm px-3 py-1.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 active:scale-95 transition"
            @click="showCreate = true"
            :disabled="isCreating"
          >
            <span v-if="!isCreating">+ New</span>
            <span v-else class="flex items-center gap-1">
              <span class="h-3 w-3 rounded-full border-2 border-t-transparent border-white animate-spin" />
              Creating...
            </span>
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

      <!-- Optional little row to show current filter -->
      <div class="flex items-center justify-between text-xs text-slate-500">
        <div class="flex items-center gap-2">
          <span>Filter:</span>
          <button
            type="button"
            class="px-2 py-0.5 rounded-full border text-[11px]"
            :class="filterAssigned === 'all'
              ? 'bg-blue-600 text-white border-blue-600'
              : 'bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700'"
            @click="handleSetFilterAssigned('all')"
          >
            All
          </button>
          <button
            type="button"
            class="px-2 py-0.5 rounded-full border text-[11px]"
            :class="filterAssigned === 'me'
              ? 'bg-blue-600 text-white border-blue-600'
              : 'bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700'"
            @click="handleSetFilterAssigned('me')"
          >
            My tasks
          </button>
        </div>
        <div class="text-[11px] text-slate-400">
          Press Cmd+K or Ctrl+K for the command palette
        </div>
      </div>

      <!-- Columns -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6 mt-2">
        <TaskColumn
          title="To Do"
          status="todo"
          data-column="todo"
          :tasks="todoTasks"
          :is-loading="isLoading"
          :team-members="props.teamMembers"
          @move-task="handleMove"
          @edit-task="handleEdit"
          @delete-task="handleDelete"
          @assign-task="handleAssign"
          @open-create="showCreate = true"
        />
        <TaskColumn
          title="In Progress"
          status="in_progress"
          data-column="in_progress"
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
          data-column="done"
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

    <CommandPalette
      v-model:show="showCommandPalette"
      :tasks="tasks"
      :team-members="props.teamMembers"
      :current-user-id="currentUserId"
      @create-task="showCreate = true"
      @set-search="handleSetSearch"
      @set-filter-assigned="handleSetFilterAssigned"
      @switch-column="handleSwitchColumn"
      @mark-task-done="handleMarkTaskDone"
    />


  </div>
</template>
