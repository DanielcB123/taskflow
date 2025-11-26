<script setup>
import { ref, watch, computed } from 'vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  teamMembers: { type: Array, default: () => [] },
});

const emits = defineEmits(['close', 'submit']);

const title = ref('');
const description = ref('');
const priority = ref('medium');
const assignedTo = ref('');
const tags = ref([]);

const newTagName = ref('');
const newTagColor = ref('#0ea5e9');

watch(
  () => props.show,
  (val) => {
    if (val) {
      resetForm();
    }
  }
);

const hasContent = computed(() =>
  title.value.trim().length > 0 || description.value.trim().length > 0
);

function resetForm() {
  title.value = '';
  description.value = '';
  priority.value = 'medium';
  assignedTo.value = '';
  tags.value = [];
  newTagName.value = '';
  newTagColor.value = '#0ea5e9';
}

function addTag() {
  if (!newTagName.value.trim()) return;
  tags.value.push({
    name: newTagName.value.trim(),
    color: newTagColor.value || '#0ea5e9',
  });
  newTagName.value = '';
}

function removeTag(index) {
  tags.value.splice(index, 1);
}

function handleSubmit() {
  if (!title.value.trim()) return;

  emits('submit', {
    title: title.value.trim(),
    description: description.value.trim() || null,
    priority: priority.value,
    assigned_to: assignedTo.value ? Number(assignedTo.value) : null,
    tags: tags.value,
    status: 'todo',
  });
}


function handleOverlayClick(e) {
  if (e.target === e.currentTarget) {
    emits('close');
  }
}
</script>

<template>
  <transition name="fade">
    <div
      v-if="show"
      class="fixed inset-0 z-40 flex items-center justify-center bg-black/30"
      @click="handleOverlayClick"
    >
      <transition name="scale">
        <div
          class="w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 shadow-xl p-5 space-y-4"
        >
          <header class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">
              Create task
            </h2>
            <button
              type="button"
              class="text-xs px-2 py-1 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800"
              @click="$emit('close')"
            >
              ✕
            </button>
          </header>

          <div class="space-y-3">
            <div>
              <label class="block text-xs font-medium text-slate-500 mb-1">
                Title
              </label>
              <input
                v-model="title"
                type="text"
                class="w-full text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-400"
                placeholder="Write a clear task title..."
              />
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-500 mb-1">
                Description
              </label>
              <textarea
                v-model="description"
                rows="3"
                class="w-full text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400"
                placeholder="Add helpful context..."
              ></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">
                  Priority
                </label>
                <select
                  v-model="priority"
                  class="w-full text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-400"
                >
                  <option value="low">Low</option>
                  <option value="medium">Medium</option>
                  <option value="high">High</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">
                  Assignee
                </label>
                <select
                  v-model="assignedTo"
                  class="w-full text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-400"
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
            </div>

            <div class="space-y-2">
              <label class="block text-xs font-medium text-slate-500">
                Tags
              </label>

              <div class="flex items-center gap-2">
                <input
                  v-model="newTagName"
                  type="text"
                  placeholder="Tag name"
                  class="flex-1 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-400"
                />
                <input
                  v-model="newTagColor"
                  type="color"
                  class="w-10 h-8 rounded-md border border-slate-300 dark:border-slate-700"
                />
                <button
                  type="button"
                  class="text-xs px-2 py-1 rounded-lg bg-slate-900 text-white hover:bg-slate-800 active:scale-95 transition"
                  @click="addTag"
                >
                  Add
                </button>
              </div>

              <div v-if="tags.length" class="flex flex-wrap gap-1 mt-1">
                <span
                  v-for="(tag, index) in tags"
                  :key="index"
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium"
                  :style="{
                    backgroundColor: tag.color + '20',
                    color: tag.color,
                  }"
                >
                  {{ tag.name }}
                  <button
                    type="button"
                    class="text-[9px]"
                    @click="removeTag(index)"
                  >
                    ✕
                  </button>
                </span>
              </div>
            </div>
          </div>

          <footer class="flex items-center justify-between pt-2">
            <button
              type="button"
              class="text-xs text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
              @click="$emit('close')"
            >
              Cancel
            </button>
            <button
              type="button"
              class="text-xs px-3 py-1.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 active:scale-95 transition disabled:opacity-50"
              :disabled="!title.trim().length"
              @click="handleSubmit"
            >
              Create task
            </button>
          </footer>
        </div>
      </transition>
    </div>
  </transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
.scale-enter-active,
.scale-leave-active {
  transition: all 0.2s ease;
}
.scale-enter-from,
.scale-leave-to {
  opacity: 0;
  transform: scale(0.95);
}
</style>
