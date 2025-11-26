<script setup>
import { ref, computed } from 'vue';
import TaskCard from '@/Components/TaskCard.vue';

const props = defineProps({
  title: { type: String, required: true },
  status: { type: String, required: true },
  tasks: { type: Array, required: true },
  isLoading: { type: Boolean, default: false },
  teamMembers: { type: Array, default: () => [] },
});

const emits = defineEmits([
  'move-task',
  'edit-task',
  'delete-task',
  'assign-task',
  'open-create',
]);

const isDragOver = ref(false);

const taskCountLabel = computed(() => {
  return props.tasks.length === 1
    ? '1 task'
    : `${props.tasks.length} tasks`;
});

function onDrop(e) {
  e.preventDefault();
  const id = Number(e.dataTransfer.getData('text/plain'));
  isDragOver.value = false;
  if (!id) return;

  const position = props.tasks.length + 1;
  emits('move-task', { id, status: props.status, position });
}

function onDragOver(e) {
  e.preventDefault();
  e.dataTransfer.dropEffect = 'move';
  isDragOver.value = true;
}

function onDragLeave(e) {
  if (e.currentTarget === e.target) {
    isDragOver.value = false;
  }
}

function emptyTitle() {
  if (props.status === 'todo') return 'Ready to tackle something new?';
  if (props.status === 'in_progress') return 'Nothing in motion yet.';
  return 'No wins here yet.';
}

function emptyBody() {
  if (props.status === 'todo') {
    return 'Drop a task here or create a new one to get started.';
  }
  if (props.status === 'in_progress') {
    return 'Move a card here when you are actively working on it.';
  }
  return 'Drag finished work here to celebrate your progress.';
}

function emptyIcon() {
  if (props.status === 'todo') return '✨';
  if (props.status === 'in_progress') return '🚀';
  return '🎯';
}
</script>

<template>
  <div
    class="flex flex-col rounded-2xl bg-slate-50 dark:bg-slate-900/40 border p-3 min-h-[260px]
           transition-colors duration-200"
    :class="[
      'border-slate-200 dark:border-slate-700',
      isDragOver
        ? 'border-blue-500 bg-blue-50/80 dark:bg-slate-900 ring-1 ring-blue-400/60'
        : '',
    ]"
    @drop="onDrop"
    @dragover="onDragOver"
    @dragleave="onDragLeave"
  >
    <div class="flex items-center justify-between mb-2">
      <div class="flex items-center gap-2">
        <h2 class="text-xs font-semibold tracking-wide uppercase text-slate-500 dark:text-slate-300">
          {{ title }}
        </h2>
        <span
          class="text-[11px] px-2 py-0.5 rounded-full bg-slate-200/70 dark:bg-slate-800
                 text-slate-600 dark:text-slate-200"
        >
          {{ taskCountLabel }}
        </span>
      </div>

    </div>

    <!-- Skeleton loading (no spinner) -->
    <div v-if="isLoading" class="space-y-2">
      <div
        v-for="n in 3"
        :key="n"
        class="animate-pulse rounded-xl border border-slate-200/70 dark:border-slate-700/70
               bg-slate-100/80 dark:bg-slate-800/80 p-3 space-y-2"
      >
        <div class="h-3 w-2/3 rounded bg-slate-300 dark:bg-slate-700"></div>
        <div class="h-2 w-full rounded bg-slate-300 dark:bg-slate-700"></div>
        <div class="h-2 w-5/6 rounded bg-slate-300 dark:bg-slate-700"></div>
      </div>
    </div>

    <transition-group
      v-else
      name="slide-fade"
      tag="div"
      class="space-y-2 flex-1"
    >
      <TaskCard
        v-for="task in tasks"
        :key="task.id"
        :task="task"
        :team-members="teamMembers"
        @edit="(fields) => emits('edit-task', { task, fields })"
        @delete="() => emits('delete-task', { task })"
        @assign="(userId) => emits('assign-task', { task, userId })"
      />

      <!-- Empty state -->
      <div
        v-if="!tasks.length"
        key="empty"
        class="flex flex-col items-center justify-center text-center text-xs
               text-slate-400 dark:text-slate-500 border border-dashed
               border-slate-300/60 dark:border-slate-700 rounded-xl py-6 px-3 mt-2
               bg-slate-50/60 dark:bg-slate-900/40"
      >
        <div class="text-xl mb-1">
          {{ emptyIcon() }}
        </div>
        <p class="font-medium mb-1 text-slate-600 dark:text-slate-200">
          {{ emptyTitle() }}
        </p>
        <p class="mb-2">
          {{ emptyBody() }}
        </p>

      </div>
    </transition-group>
  </div>
</template>

<style scoped>
.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: all 0.25s ease;
}
.slide-fade-enter-from,
.slide-fade-leave-to {
  opacity: 0;
  transform: translateY(6px);
}
</style>
