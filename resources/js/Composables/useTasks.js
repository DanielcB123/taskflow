// resources/js/Composables/useTasks.js
import { ref, computed } from 'vue';
import ApiService from '@/Services/ApiService';
import { toast } from 'vue3-toastify';
import confetti from 'canvas-confetti';

export function useTasks() {
  const tasks = ref([]);
  const isLoading = ref(false);
  const filterAssigned = ref('all'); // 'all' | 'me' | userId
  const search = ref('');

  const filteredTasks = computed(() => {
    let list = tasks.value.slice();

    if (filterAssigned.value === 'me') {
      list = list.filter(t => t.assigned_to === window.TaskflowCurrentUserId);
    } else if (filterAssigned.value !== 'all' && filterAssigned.value) {
      list = list.filter(t => t.assigned_to === Number(filterAssigned.value));
    }

    if (search.value.trim().length) {
      const q = search.value.toLowerCase();
      list = list.filter(t => {
        return (
          t.title.toLowerCase().includes(q) ||
          (t.description || '').toLowerCase().includes(q)
        );
      });
    }

    return list;
  });

  const todoTasks = computed(() =>
    filteredTasks.value.filter(t => t.status === 'todo')
  );
  const inProgressTasks = computed(() =>
    filteredTasks.value.filter(t => t.status === 'in_progress')
  );
  const doneTasks = computed(() =>
    filteredTasks.value.filter(t => t.status === 'done')
  );

  async function loadTasks() {
    isLoading.value = true;
    try {
      const data = await ApiService.get('tasks', {
        assigned_to: filterAssigned.value === 'all' ? null : filterAssigned.value,
        search: search.value || null,
      });
      if (data.success) {
        tasks.value = data.tasks;
      }
    } catch (e) {
      // toast handled in ApiService
    } finally {
      setTimeout(() => {
        isLoading.value = false;
      }, 300);
    }
  }

  async function createTask(payload) {
    try {
      const data = await ApiService.post('tasks', payload);
      if (data.success && data.task) {
        tasks.value.push(data.task);
        toast.success('New task created');
      }
      return data.task;
    } catch (e) {
      return null;
    }
  }

  async function updateTaskInline(taskId, fields) {
    const index = tasks.value.findIndex(t => t.id === taskId);
    if (index === -1) return;

    const original = { ...tasks.value[index] };
    tasks.value[index] = { ...tasks.value[index], ...fields };

    try {
      const data = await ApiService.patch(`tasks/${taskId}`, fields);
      if (data.success && data.task) {
        tasks.value[index] = data.task;
        if (data.status_changed_to === 'done') {
          celebrateDone();
        }
      } else {
        tasks.value[index] = original;
      }
    } catch (e) {
      tasks.value[index] = original;
    }
  }

  async function moveTask(taskId, newStatus, newPosition) {
    const index = tasks.value.findIndex(t => t.id === taskId);
    if (index === -1) return;

    const original = tasks.value.map(t => ({ ...t }));

    // Optimistic update
    tasks.value[index].status = newStatus;
    tasks.value[index].position = newPosition;

    try {
      const data = await ApiService.patch(`tasks/${taskId}/move`, {
        status: newStatus,
        position: newPosition,
      });

      if (data.success && data.task) {
        const idx = tasks.value.findIndex(t => t.id === taskId);
        if (idx !== -1) {
          tasks.value[idx] = data.task;
        }
        if (data.status_changed_to === 'done') {
          celebrateDone();
        }
      } else {
        tasks.value = original;
      }
    } catch (e) {
      tasks.value = original;
    }
  }

  async function deleteTask(taskId) {
    const original = tasks.value.slice();
    tasks.value = tasks.value.filter(t => t.id !== taskId);

    try {
      const data = await ApiService.delete(`tasks/${taskId}`);
      if (data.success) {
        toast.success('Task deleted');
      } else {
        tasks.value = original;
      }
    } catch (e) {
      tasks.value = original;
    }
  }

  async function assignTask(taskId, userId) {
    const index = tasks.value.findIndex(t => t.id === taskId);
    if (index === -1) return;
    const original = { ...tasks.value[index] };

    tasks.value[index].assigned_to = userId;

    try {
      const data = await ApiService.post(`tasks/${taskId}/assign`, {
        user_id: userId,
      });
      if (data.success && data.task) {
        tasks.value[index] = data.task;
        toast.success('Task assignment updated');
      } else {
        tasks.value[index] = original;
      }
    } catch (e) {
      tasks.value[index] = original;
    }
  }

  function celebrateDone() {
    confetti({
      particleCount: 80,
      spread: 70,
      origin: { y: 0.6 },
    });
    toast.success('🎉 Task completed - great work!');
  }

  return {
    tasks,
    todoTasks,
    inProgressTasks,
    doneTasks,
    isLoading,
    filterAssigned,
    search,
    loadTasks,
    createTask,
    updateTaskInline,
    moveTask,
    deleteTask,
    assignTask,
  };
}
