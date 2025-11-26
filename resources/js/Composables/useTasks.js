// resources/js/Composables/useTasks.js
import { ref, computed } from 'vue';
import ApiService from '@/Services/ApiService';
import { toast } from 'vue3-toastify';
import confetti from 'canvas-confetti';

export function useTasks() {
  const tasks = ref([]);
  const isLoading = ref(false);
  const isCreating = ref(false); // for create button loading state
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
    const startedAt = performance.now();

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
      const elapsed = performance.now() - startedAt;
      const remaining = 300 - elapsed; // min 300ms for smooth skeleton

      if (remaining > 0) {
        setTimeout(() => {
          isLoading.value = false;
        }, remaining);
      } else {
        isLoading.value = false;
      }
    }
  }

  async function createTask(payload) {
    isCreating.value = true;

    try {
      const data = await ApiService.post('tasks', payload);
      if (data.success && data.task) {
        // Put new task at top so it can slide in nicely in a TransitionGroup
        tasks.value = [data.task, ...tasks.value];
        toast.success('New task created');
      }
      return data.task || null;
    } catch (e) {
      return null;
    } finally {
      isCreating.value = false;
    }
  }

  async function updateTaskInline(taskId, fields) {
    const index = tasks.value.findIndex(t => t.id === taskId);
    if (index === -1) return;
  
    const original = { ...tasks.value[index] };
  
    // optimistic update, including tags
    tasks.value[index] = { ...tasks.value[index], ...fields };
  
    try {
      const data = await ApiService.patch(`tasks/${taskId}`, fields);
      if (data.success && data.task) {
        // merge, do not blow away any local fields accidentally
        tasks.value[index] = {
          ...tasks.value[index],
          ...data.task,
        };
  
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

    // Optional per-card loading flag for micro animation
    const movingId = taskId;
    markTaskMoving(movingId, true);

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
          tasks.value[idx] = {
            ...data.task,
            _isMoving: false,
          };
        }
        if (data.status_changed_to === 'done') {
          celebrateDone();
        }
      } else {
        tasks.value = original;
      }
    } catch (e) {
      tasks.value = original;
    } finally {
      markTaskMoving(movingId, false);
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

  function markTaskMoving(taskId, isMoving) {
    const index = tasks.value.findIndex(t => t.id === taskId);
    if (index === -1) return;
    tasks.value[index] = {
      ...tasks.value[index],
      _isMoving: isMoving,
    };
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
    isCreating,
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
