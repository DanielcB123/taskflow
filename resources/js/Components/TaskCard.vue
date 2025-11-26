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

// local tag state for inline edits
const localTags = ref(props.task.tags ? [...props.task.tags] : []);
const isAddingTag = ref(false);
const newTagName = ref('');
const newTagColor = ref('#0ea5e9');

// keep locals in sync when parent updates
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

watch(
  () => props.task.tags,
  (val) => {
    localTags.value = val ? [...val] : [];
  }
);

const priorities = ['high','medium', 'low'];

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

function onPriorityClick() {
  const current = props.task.priority || 'medium';
  const currentIndex = priorities.indexOf(current);
  const next = priorities[(currentIndex + 1) % priorities.length];

  emits('edit', { priority: next });
}

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

/**
 * Inline tags
 */
function syncAndEmitTags(updated) {
  localTags.value = updated;

  // strip out any extra properties, send only what backend expects
  emits('edit', {
    tags: updated.map((tag) => ({
      id: tag.id ?? undefined,
      name: tag.name,
      color: tag.color,
    })),
  });
}

function handleRemoveTag(tagToRemove) {
  const updated = localTags.value.filter((tag) => {
    if (tag.id && tagToRemove.id) {
      return tag.id !== tagToRemove.id;
    }
    // fall back to name match if no id yet
    return !(tag.name === tagToRemove.name && tag.color === tagToRemove.color);
  });

  syncAndEmitTags(updated);
}

function startAddTag() {
  isAddingTag.value = true;
  newTagName.value = '';
  newTagColor.value = '#0ea5e9';
}

function cancelAddTag() {
  isAddingTag.value = false;
  newTagName.value = '';
}

function submitAddTag() {
  if (!newTagName.value.trim()) {
    return;
  }

  const newTag = {
    name: newTagName.value.trim(),
    color: newTagColor.value || '#0ea5e9',
  };

  const updated = [...localTags.value, newTag];
  syncAndEmitTags(updated);

  newTagName.value = '';
  newTagColor.value = '#0ea5e9';
  isAddingTag.value = false;
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
      <button
        type="button"
        class="text-[11px] px-2 py-0.5 rounded-full border font-medium
              hover:brightness-110 transition-colors"
        :class="priorityColor"
        @click="onPriorityClick"
        title="Click to change priority"
      >
        {{ task.priority.toUpperCase() }}
      </button>

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

    <!-- Tags block with inline add and delete -->
    <div class="space-y-1">
      <div v-if="localTags.length" class="flex flex-wrap gap-1">
        <span
          v-for="tag in localTags"
          :key="tag.id || tag.name"
          class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium"
          :style="{ backgroundColor: (tag.color || '#0ea5e9') + '20', color: tag.color || '#0ea5e9' }"
        >
          {{ tag.name }}
          <button
            type="button"
            class="text-[9px] hover:opacity-70"
            @click="handleRemoveTag(tag)"
          >
            ✕
          </button>
        </span>
      </div>

      <!-- Inline add tag control -->
      <div v-if="isAddingTag" class="flex items-center gap-1 mt-1">
        <input
          v-model="newTagName"
          type="text"
          placeholder="Tag label"
          class="flex-1 text-[11px] rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-2 py-0.5 focus:outline-none focus:ring-1 focus:ring-blue-400"
        />
        <input
          v-model="newTagColor"
          type="color"
          class="w-7 h-7 rounded-md border border-slate-300 dark:border-slate-600"
        />
        <button
          type="button"
          class="text-[11px] px-2 py-0.5 rounded-md bg-blue-600 text-white hover:bg-blue-700"
          @click="submitAddTag"
        >
          Save
        </button>
        <button
          type="button"
          class="text-[11px] px-2 py-0.5 rounded-md border border-slate-300 dark:border-slate-600"
          @click="cancelAddTag"
        >
          Cancel
        </button>
      </div>

      <button
        v-else
        type="button"
        class="text-[11px] text-blue-600 dark:text-emerald-300 hover:underline mt-1"
        @click="startAddTag"
      >
        + Add tag
      </button>
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

        <span
          v-else
          class="text-[11px] text-slate-400"
        >
          Unassigned
        </span>
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
