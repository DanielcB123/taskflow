<script setup>
//CommandPalette.vue
import {
  ref,
  computed,
  onMounted,
  onBeforeUnmount,
  watch,
  nextTick,
} from 'vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  tasks: { type: Array, default: () => [] },
  teamMembers: { type: Array, default: () => [] },
  currentUserId: { type: Number, default: null },
});

const emits = defineEmits([
  'update:show',
  'create-task',
  'set-search',
  'set-filter-assigned',
  'switch-column',
  'mark-task-done',
]);

const isOpen = ref(false);
const query = ref('');
const selectedIndex = ref(0);
const inputRef = ref(null);

watch(
  () => props.show,
  (val) => {
    if (val !== isOpen.value) {
      if (val) {
        openPalette();
      } else {
        closePalette();
      }
    }
  }
);

function openPalette() {
  isOpen.value = true;
  emits('update:show', true);
  query.value = '';
  selectedIndex.value = 0;

  nextTick(() => {
    if (inputRef.value) {
      inputRef.value.focus();
    }
  });
}

function closePalette() {
  isOpen.value = false;
  emits('update:show', false);
}

function onGlobalKeydown(e) {
  // Cmd+K or Ctrl+K
  if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
    e.preventDefault();
    if (isOpen.value) {
      closePalette();
    } else {
      openPalette();
    }
  }

  if (e.key === 'Escape' && isOpen.value) {
    e.preventDefault();
    closePalette();
  }
}

onMounted(() => {
  window.addEventListener('keydown', onGlobalKeydown);
});

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onGlobalKeydown);
});

const baseCommands = computed(() => {
  const items = [];

  items.push({
    id: 'cmd-new-task',
    type: 'command',
    label: 'Create new task',
    description: 'Open the new task modal',
    shortcut: 'N',
    command: 'create-task',
  });

  items.push({
    id: 'cmd-search',
    type: 'command',
    label: 'Search tasks',
    description: 'Filter the board by text',
    shortcut: '/',
    command: 'search',
  });

  // Filter by user
  items.push({
    id: 'filter-all',
    type: 'command',
    label: 'Show tasks for all users',
    description: 'Clear assignee filter',
    command: 'set-filter-assigned',
    payload: 'all',
  });

  if (props.currentUserId) {
    items.push({
      id: 'filter-me',
      type: 'command',
      label: 'Show tasks assigned to me',
      description: 'Filter to your tasks',
      command: 'set-filter-assigned',
      payload: 'me',
    });
  }

  props.teamMembers.forEach((member) => {
    items.push({
      id: `filter-user-${member.id}`,
      type: 'command',
      label: `Show tasks for ${member.name}`,
      description: 'Filter by team member',
      command: 'set-filter-assigned',
      payload: member.id,
    });
  });

  // Column commands
  items.push(
    {
      id: 'col-all',
      type: 'command',
      label: 'View all columns',
      description: 'Show all task columns',
      command: 'switch-column',
      payload: 'all',
    },
    {
      id: 'col-todo',
      type: 'command',
      label: 'View To Do column',
      description: 'Focus the To Do column',
      command: 'switch-column',
      payload: 'todo',
    },
    {
      id: 'col-in-progress',
      type: 'command',
      label: 'View In Progress column',
      description: 'Focus the In Progress column',
      command: 'switch-column',
      payload: 'in_progress',
    },
    {
      id: 'col-done',
      type: 'command',
      label: 'View Done column',
      description: 'Focus the Done column',
      command: 'switch-column',
      payload: 'done',
    }
  );

  return items;
});

const taskResults = computed(() => {
  const raw = query.value.trim().toLowerCase();
  const isTaskSearch = raw.startsWith('/');
  const q = (isTaskSearch ? raw.slice(1) : raw).trim(); // strip leading '/'

  if (!q || q.length < 2) return [];

  const matches = props.tasks.filter((t) => {
    const title = (t.title || '').toLowerCase();
    const desc  = (t.description || '').toLowerCase();
    return title.includes(q) || desc.includes(q);
  });

  return matches.slice(0, 6).map((t) => ({
    id: `task-${t.id}`,
    type: 'task',
    label: t.title || '(Untitled task)',
    description: 'Filter the board to this task or mark it as done',
    taskId: t.id,
  }));
});



const filteredCommands = computed(() => {
  const raw = query.value.trim().toLowerCase();
  const isTaskSearch = raw.startsWith('/');
  const q = (isTaskSearch ? raw.slice(1) : raw).trim();

  // If we are doing "/something", hide commands and only show tasks
  if (isTaskSearch) {
    return [];
  }

  if (!q) return baseCommands.value.slice(0, 10);

  return baseCommands.value.filter((cmd) => {
    const label = cmd.label.toLowerCase();
    const desc  = (cmd.description || '').toLowerCase();
    return label.includes(q) || desc.includes(q);
  });
});


const results = computed(() => {
  return [...filteredCommands.value, ...taskResults.value];
});

watch(
  () => results.value.length,
  (len) => {
    if (selectedIndex.value > len - 1) {
      selectedIndex.value = len === 0 ? 0 : len - 1;
    }
  }
);

function moveSelection(delta) {
  const len = results.value.length;
  if (!len) return;

  let next = selectedIndex.value + delta;
  if (next < 0) next = len - 1;
  if (next >= len) next = 0;
  selectedIndex.value = next;
}

function selectCurrent() {
  const item = results.value[selectedIndex.value];
  if (!item) return;

  if (item.type === 'command') {
    runCommand(item);
  } else if (item.type === 'task') {
    // Filter board to this task
    const label = (item.label || '').trim();
    emits('set-search', label);
  }

  closePalette();
}


function runCommand(item) {
  switch (item.command) {
    case 'create-task':
      emits('create-task');
      break;
    case 'search': {
      // Use current query as board search, strip leading '/'
      const raw = query.value || '';
      const cleaned = raw.startsWith('/')
        ? raw.slice(1).trim()
        : raw.trim();

      emits('set-search', cleaned);
      break;
    }
    case 'set-filter-assigned':
      emits('set-filter-assigned', item.payload);
      break;
    case 'switch-column':
      emits('switch-column', item.payload);
      break;
  }
}

</script>

<template>
  <Teleport to="body">
    <transition name="fade">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-40 flex items-start justify-center pt-24
               bg-black/40 backdrop-blur-sm"
        @click.self="closePalette"
      >
        <transition name="scale-fade">
          <div
            class="w-full max-w-md rounded-2xl bg-white dark:bg-slate-900
                   border border-slate-200 dark:border-slate-700
                   shadow-xl overflow-hidden"
          >
            <!-- Input -->
            <div class="border-b border-slate-200 dark:border-slate-700 px-3 py-2.5">
              <div class="flex items-center gap-2">
                <span class="text-slate-400 text-sm">⌘K</span>
                <input
                  ref="inputRef"
                  v-model="query"
                  type="text"
                  placeholder="Search commands or tasks..."
                  class="flex-1 bg-transparent outline-none text-sm
                         text-slate-900 dark:text-slate-50"
                  @keydown.down.prevent="moveSelection(1)"
                  @keydown.up.prevent="moveSelection(-1)"
                  @keydown.enter.prevent="selectCurrent"
                  @keydown.esc.prevent="closePalette"
                />
              </div>
            </div>

            <!-- Results -->
            <div class="max-h-80 overflow-y-auto">
              <div v-if="!results.length" class="px-3 py-4 text-xs text-slate-400">
                No matching commands or tasks.
              </div>

              <ul v-else class="py-1 text-sm">
                <li
                v-for="(item, idx) in results"
                :key="item.id"
                class="px-3 py-2 flex items-center justify-between gap-2 cursor-pointer
                        transition-colors"
                :class="[
                    idx === selectedIndex
                    ? 'bg-blue-50 dark:bg-slate-800 text-slate-900 dark:text-slate-50'
                    : 'text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/80',
                ]"
                @mouseenter="selectedIndex = idx"
                @click="() => { selectedIndex = idx; selectCurrent(); }"
                >
                <div class="flex flex-col">
                    <span class="text-xs font-medium">
                    <span v-if="item.type === 'task'">✅ </span>{{ item.label }}
                    </span>
                    <span
                    v-if="item.description"
                    class="text-[11px] text-slate-400 dark:text-slate-400"
                    >
                    {{ item.description }}
                    </span>
                </div>

                <div class="flex items-center gap-2 text-[10px] text-slate-400">
                    <!-- command shortcuts -->
                    <span
                    v-if="item.type === 'command' && item.shortcut"
                    class="px-1.5 py-0.5 rounded-md border border-slate-300 dark:border-slate-600"
                    >
                    {{ item.shortcut }}
                    </span>

                    <!-- task "Done" button -->
                    <button
                    v-if="item.type === 'task'"
                    type="button"
                    class="px-1.5 py-0.5 rounded-md border border-slate-300 dark:border-slate-600
                            hover:bg-emerald-50 dark:hover:bg-emerald-900/30"
                    @click.stop="
                        () => {
                        emits('mark-task-done', item.taskId);
                        closePalette();
                        }
                    "
                    >
                    Done
                    </button>
                </div>
                </li>

              </ul>
            </div>

            <!-- Footer hint -->
            <div class="border-t border-slate-200 dark:border-slate-700 px-3 py-2">
              <div class="flex items-center justify-between text-[11px] text-slate-400">
                <span>Use ↑ ↓ to navigate, Enter to run, Esc to close.</span>
                <span>Cmd+K or Ctrl+K to open</span>
              </div>
            </div>
          </div>
        </transition>
      </div>
    </transition>
  </Teleport>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.scale-fade-enter-active,
.scale-fade-leave-active {
  transition: all 0.15s ease;
}
.scale-fade-enter-from,
.scale-fade-leave-to {
  opacity: 0;
  transform: translateY(4px) scale(0.98);
}
</style>
