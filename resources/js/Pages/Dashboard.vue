<script setup>
// Dashboard.vue
import { onMounted, ref, computed } from 'vue';
import axios from 'axios';
import { useTasks } from '@/Composables/useTasks';
import TaskColumn from '@/Components/TaskColumn.vue';
import CreateTaskModal from '@/Components/CreateTaskModal.vue';
import CommandPalette from '@/Components/CommandPalette.vue';
import { toast } from 'vue3-toastify';


const todoColumnRef       = ref(null);
const inProgressColumnRef = ref(null);
const doneColumnRef       = ref(null);
const activeColumn        = ref(null);

const isDark              = ref(false);

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

// local filter for "All" vs "My tasks"
const assignedFilter = ref('all'); // 'all' | 'me'

const currentUserId = computed(() => props.auth?.user?.id ?? null);
const currentUser   = computed(() => props.auth?.user ?? null);

const currentMember = computed(() => {
  if (!currentUser.value) return null;
  return props.teamMembers.find(m => m.id === currentUser.value.id) ?? null;
});

const filteredUserId = computed(() => {
  // "My tasks"
  if (assignedFilter.value === 'me' && currentUserId.value) {
    return Number(currentUserId.value);
  }

  // Specific teammate from command palette (a number)
  if (
    assignedFilter.value !== 'all' &&
    assignedFilter.value !== 'me' &&
    assignedFilter.value != null &&
    assignedFilter.value !== ''
  ) {
    return Number(assignedFilter.value);
  }

  // "All" or nothing selected
  return null;
});




// useTasks
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

/**
 * Filtered columns (client-side "My tasks")
 */
const filteredTodoTasks = computed(() => {
  const uid = filteredUserId.value;
  const q = search.value.trim().toLowerCase();

  return todoTasks.value.filter((task) => {
    const assignedId =
      task.assigned_to != null ? Number(task.assigned_to) : null;
    const relId =
      task.assigned_user?.id != null ? Number(task.assigned_user.id) : null;

    const matchesUser =
      uid == null ? true : assignedId === uid || relId === uid;

    const title = (task.title || '').toLowerCase();
    const desc  = (task.description || '').toLowerCase();
    const matchesSearch = !q ? true : title.includes(q) || desc.includes(q);

    return matchesUser && matchesSearch;
  });
});


const filteredInProgressTasks = computed(() => {
  const uid = filteredUserId.value;
  const q = search.value.trim().toLowerCase();

  return inProgressTasks.value.filter((task) => {
    const assignedId =
      task.assigned_to != null ? Number(task.assigned_to) : null;
    const relId =
      task.assigned_user?.id != null ? Number(task.assigned_user.id) : null;

    const matchesUser =
      uid == null ? true : assignedId === uid || relId === uid;

    const title = (task.title || '').toLowerCase();
    const desc  = (task.description || '').toLowerCase();
    const matchesSearch = !q ? true : title.includes(q) || desc.includes(q);

    return matchesUser && matchesSearch;
  });
});

const filteredDoneTasks = computed(() => {
  const uid = filteredUserId.value;
  const q = search.value.trim().toLowerCase();

  return doneTasks.value.filter((task) => {
    const assignedId =
      task.assigned_to != null ? Number(task.assigned_to) : null;
    const relId =
      task.assigned_user?.id != null ? Number(task.assigned_user.id) : null;

    const matchesUser =
      uid == null ? true : assignedId === uid || relId === uid;

    const title = (task.title || '').toLowerCase();
    const desc  = (task.description || '').toLowerCase();
    const matchesSearch = !q ? true : title.includes(q) || desc.includes(q);

    return matchesUser && matchesSearch;
  });
});



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

  const stored = window.localStorage.getItem('theme');
  const prefersDark =
    window.matchMedia &&
    window.matchMedia('(prefers-color-scheme: dark)').matches;

  const initial =
    stored === 'dark' || stored === 'light'
      ? stored
      : prefersDark
        ? 'dark'
        : 'light';

  console.log('initial theme:', initial);
  applyTheme(initial);
});


function handleSwitchColumn(status) {
  if (status === 'all') {
    activeColumn.value = null;
    return;
  }

  activeColumn.value = status;

  const map = {
    todo: todoColumnRef,
    in_progress: inProgressColumnRef,
    done: doneColumnRef,
  };

  const refObj = map[status];

  if (!refObj || !refObj.value) {
    console.log('No column ref for status:', status, map);
    return;
  }

  const el = refObj.value.$el ?? refObj.value;

  if (el && el.scrollIntoView) {
    el.scrollIntoView({
      behavior: 'smooth',
      block: 'center',
    });
  }
}

function applyTheme(theme) {
  const root = document.documentElement;

  if (theme === 'dark') {
    root.classList.add('dark');
    window.localStorage.setItem('theme', 'dark');
    isDark.value = true;
  } else {
    root.classList.remove('dark');
    window.localStorage.setItem('theme', 'light');
    isDark.value = false;
  }
  console.log('applyTheme ->', theme, 'html classes:', root.className);
}

function toggleTheme() {
  applyTheme(isDark.value ? 'light' : 'dark');
}


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
 * Command palette / filters
 */
function handleSetSearch(value) {
  search.value = value || '';
}

function handleSetFilterAssigned(value) {
  assignedFilter.value = value;
}


function handleMarkTaskDone(taskId) {
  updateTaskInline(taskId, { status: 'done' });
}
</script>


<template>
  <div class="min-h-screen bg-slate-100 dark:bg-slate-950">
    <div class="max-w-6xl mx-auto px-4 py-6 space-y-4">
      <!-- Header bar -->
      <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
      >
        <!-- Left: title / badge -->
        <div class="flex items-center gap-2">
          <span class="text-lg font-semibold">TaskFlow</span>
          <span
            class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-700 whitespace-nowrap"
          >
            MediaHaus Squad
          </span>
        </div>

        <!-- Right: search + controls -->
        <div
          class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3 w-full sm:w-auto"
        >
          <!-- Search and command palette -->
          <div class="flex items-center gap-2 w-full sm:w-auto">
            <input
              v-model="search"
              type="search"
              placeholder="Search tasks..."
              class="w-full sm:w-48 text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-400"
              @keyup.enter="loadTasks"
            />
            <button
              type="button"
              class="inline-flex items-center gap-1 text-[11px] px-2 py-1 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-500 dark:text-slate-300"
              @click="showCommandPalette = true"
            >
              <span class="text-xs text-slate-400">⌘K</span>
              <span class="hidden xs:inline">Command palette</span>
            </button>
          </div>

          <!-- New button + user info -->
          <div class="flex items-center justify-between sm:justify-end gap-2">
            <button
              type="button"
              class="text-sm px-3 py-1.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 active:scale-95 transition"
              @click="showCreate = true"
              :disabled="isCreating"
            >
              <span v-if="!isCreating">+ New</span>
              <span v-else class="flex items-center gap-1">
                <span
                  class="h-3 w-3 rounded-full border-2 border-t-transparent border-white animate-spin"
                />
                Creating...
              </span>
            </button>

            <div class="flex items-center gap-2">
              <div
                v-if="currentMember"
                class="w-7 h-7 rounded-full flex items-center justify-center text-[11px] font-semibold text-white"
                :style="{ backgroundColor: currentMember.avatar_color || '#0f766e' }"
              >
                {{ currentMember.name.slice(0, 1).toUpperCase() }}
              </div>

              <span
                v-if="currentMember"
                class="hidden sm:inline text-[11px] text-slate-500 dark:text-slate-300 truncate max-w-[120px]"
              >
                {{ currentMember.name }}
              </span>

              <!-- THEME TOGGLE BUTTON -->
              <button
                type="button"
                class="text-xs dark:text-white px-2 py-1 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 active:scale-95 transition flex items-center gap-1"
                @click="toggleTheme"
                :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
              >
                <span v-if="isDark">🌞</span>
                <span v-else>🌙</span>
                <span class="hidden sm:inline">
                  {{ isDark ? 'Light' : 'Dark' }}
                </span>
              </button>

              <button
                type="button"
                class="text-xs px-3 py-1.5 dark:text-white rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 active:scale-95 transition"
                @click="handleLogout"
              >
                Logout
              </button>
            </div>
          </div>

        </div>
      </header>

      <!-- Optional little row to show current filter -->
      <div
        class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between text-xs text-slate-500 mt-2"
      >
        <div class="flex items-center gap-2">
          <span>Filter:</span>

          <button
            type="button"
            class="px-2 py-0.5 rounded-full border text-[11px]"
            :class="assignedFilter === 'all'
              ? 'bg-blue-600 text-white border-blue-600'
              : 'bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700'"
            @click="handleSetFilterAssigned('all')"
          >
            All
          </button>
          <button
            type="button"
            class="px-2 py-0.5 rounded-full border text-[11px]"
            :class="assignedFilter === 'me'
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
        <!-- To Do -->
        <TaskColumn
          v-if="!activeColumn || activeColumn === 'todo'"
          ref="todoColumnRef"
          title="To Do"
          status="todo"
          :tasks="filteredTodoTasks"
          :is-loading="isLoading"
          :team-members="props.teamMembers"
          @move-task="handleMove"
          @edit-task="handleEdit"
          @delete-task="handleDelete"
          @assign-task="handleAssign"
          @open-create="showCreate = true"
        />

        <!-- In Progress -->
        <TaskColumn
          v-if="!activeColumn || activeColumn === 'in_progress'"
          ref="inProgressColumnRef"
          title="In Progress"
          status="in_progress"
          :tasks="filteredInProgressTasks"
          :is-loading="isLoading"
          :team-members="props.teamMembers"
          @move-task="handleMove"
          @edit-task="handleEdit"
          @delete-task="handleDelete"
          @assign-task="handleAssign"
        />

        <!-- Done -->
        <TaskColumn
          v-if="!activeColumn || activeColumn === 'done'"
          ref="doneColumnRef"
          title="Done"
          status="done"
          :tasks="filteredDoneTasks"
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
