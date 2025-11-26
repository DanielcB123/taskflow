<script setup>
import { ref } from 'vue';
import TaskCard from '@/Components/TaskCard.vue';

const props = defineProps({
  title: { type: String, required: true },
  status: { type: String, required: true },
  tasks: { type: Array, required: true },
  isLoading: { type: Boolean, default: false },
  teamMembers: { type: Array, default: () => [] },
});

const emits = defineEmits(['move-task', 'edit-task', 'delete-task', 'assign-task']);

const isDragOver = ref(false);

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
</script>

<template>
  <div
    class="flex flex-col rounded-2xl bg-slate-50 dark:bg-slate-900/40 border p-3 min-h-[260px] transition-colors"
    :class="[
      'border-slate-200 dark:border-slate-700',
      isDragOver ? 'border-blue-400 bg-blue-50/60 dark:bg-slate-900' : '',
    ]"
    @drop="onDrop"
    @dragover="onDragOver"
    @dragleave="onDragLeave"
  >
    <div class="flex items-center justify-between mb-2">
      <h2 class="text-xs font-semibold tracking-wide uppercase text-slate-500 dark:text-slate-300">
        {{ title }}
      </h2>
      <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-200/70 dark:bg-slate-800">
        {{ tasks.length }}
      </span>
    </div>

    <div v-if="isLoading" class="space-y-2">
      <div v-for="n in 3" :key="n" class="animate-pulse">
        <div class="h-16 rounded-xl bg-slate-200/80 dark:bg-slate-800"></div>
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

      <div
        v-if="!tasks.length"
        key="empty"
        class="flex flex-col items-center justify-center text-center text-xs text-slate-400 dark:text-slate-500 border border-dashed border-slate-300/60 dark:border-slate-700 rounded-xl py-6 px-2 mt-2"
      >
        <p class="font-medium mb-1">
          Ready to tackle something new?
        </p>
        <p class="mb-2">
          Drop a task here or create a new one to get started.
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
