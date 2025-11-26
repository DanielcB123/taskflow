<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
  task: { type: Object, required: true },
  teamMembers: { type: Array, default: () => [] },
});

const emits = defineEmits(['edit', 'delete', 'assign', 'drag-start']);

const isEditingTitle = ref(false);
const isEditingDescription = ref(false);
const localTitle = ref(props.task.title);
const localDescription = ref(props.task.description || '');
const localAssignedId = ref(props.task.assigned_to || '');

watch(
  () => props.task.title,
  (val) => {
    localTitle.value = val;
  }
);

watch(
  () => props.task.description,
  (val) => {
    localDescription.value = val || '';
  }
);

watch(
  () => props.task.assigned_to,
  (val) => {
    localAssignedId.value = val || '';
  }
);

const priorityColor = computed(() => {
  switch (props.task.priority) {
    case 'high':
      return 'bg-red-500/10 text-red-600 border-red-400/50';
    case 'medium':
      return 'bg-amber-500/10 text-amber-600 border-amber-400/50';
    default:
      return 'bg-emerald-500/10 text-emerald-600 border-emerald-400/50';
  }
});

function onTitleBlur() {
  isEditingTitle.value = false;
  if (localTitle.value !== props.task.title) {
    emits('edit', { title: localTitle.value });
}
}

function onDescriptionBlur() {
  isEditingDescription.value = false;
  if (localDescription.value !== (props.task.description || '')) {
    emits('edit', { description: localDescription.value });
  }
}

function onDragStart(e) {
  e.dataTransfer.effectAllowed = 'move';
  e.dataTransfer.setData('text/plain', String(props.task.id));
  emits('drag-start', props.task);
}

function onAssignChange() {
  emits('assign', localAssignedId.value || null);
}
</script>

<template>
  <div
    class="group cursor-grab active:cursor-grabbing hover:-translate-y-1 transition-all duration-200
           rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900
           shadow-sm hover:shadow-lg p-3 space-y-2"
    draggable="true"
    @dragstart="onDragStart"
  >
    <div class="flex items-center justify-between gap-2">
      <div class="flex-1 min-w-0">
        <input
          v-if="isEditingTitle"
          v-model="localTitle"
          type="text"
          class="w-full text-sm font-semibold bg-transparent border-b border-blue-400 outline-none"
          @blur="onTitleBlur"
          @keyup.enter.prevent="onTitleBlur"
        />
        <button
          v-else
          type="button"
          class="text-left text-sm font-semibold text-slate-900 dark:text-slate-50 truncate w-full"
          @click="isEditingTitle = true"
        >
          {{ task.title }}
        </button>
      </div>
      <span
        class="text-[11px] px-2 py-0.5 rounded-full border font-medium"
        :class="priorityColor"
      >
        {{ task.priority.toUpperCase() }}
      </span>
    </div>

    <div class="text-xs text-slate-500 dark:text-slate-300">
      <textarea
        v-if="isEditingDescription"
        v-model="localDescription"
        rows="2"
        class="w-full bg-transparent border border-blue-400 rounded-md p-1 outline-none"
        @blur="onDescriptionBlur"
      ></textarea>

      <button
        v-else
        type="button"
        class="text-left line-clamp-2 w-full"
        @click="isEditingDescription = true"
      >
        {{ task.description || 'Add a description...' }}
      </button>
    </div>

    <div v-if="task.tags && task.tags.length" class="flex flex-wrap gap-1">
      <span
        v-for="tag in task.tags"
        :key="tag.id || tag.name"
        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium"
        :style="{ backgroundColor: tag.color + '20', color: tag.color }"
      >
        {{ tag.name }}
      </span>
    </div>

    <div class="flex items-center justify-between mt-1">
      <div class="flex items-center gap-2">
        <div
          class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-semibold text-white"
          v-if="task.assigned_user"
          :style="{ backgroundColor: task.assigned_user.avatar_color || '#0f766e' }"
        >
          {{ task.assigned_user.name.slice(0, 1).toUpperCase() }}
        </div>

        <span
          v-if="task.assigned_user"
          class="text-[11px] text-slate-500 dark:text-slate-300 truncate max-w-[80px]"
        >
          {{ task.assigned_user.name }}
        </span>

        <select
          v-else
          v-model="localAssignedId"
          class="text-[11px] rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-1 py-0.5 focus:outline-none focus:ring-1 focus:ring-blue-400"
          @change="onAssignChange"
        >
          <option value="">Unassigned</option>
          <option
            v-for="member in teamMembers"
            :key="member.id"
            :value="member.id"
          >
            {{ member.name }}
          </option>
        </select>
      </div>

      <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
        <select
          v-model="localAssignedId"
          class="text-[11px] rounded-md border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 px-1 py-0.5 focus:outline-none focus:ring-1 focus:ring-blue-400"
          @change="onAssignChange"
        >
          <option value="">Unassigned</option>
          <option
            v-for="member in teamMembers"
            :key="member.id"
            :value="member.id"
          >
            {{ member.name }}
          </option>
        </select>

        <button
          type="button"
          class="p-1 rounded-md hover:bg-slate-100 dark:hover:bg-slate-700"
          @click="$emit('edit', { openFull: true })"
          aria-label="Edit task"
        >
          ✏️
        </button>
        <button
          type="button"
          class="p-1 rounded-md hover:bg-red-50 dark:hover:bg-red-900/40"
          @click="$emit('delete')"
          aria-label="Delete task"
        >
          🗑️
        </button>
      </div>
    </div>
  </div>
</template>
