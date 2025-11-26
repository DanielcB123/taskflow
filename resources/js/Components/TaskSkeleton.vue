<!-- resources/js/Components/TaskSkeleton.vue -->
<template>
  <div
    class="rounded-xl border border-slate-200 dark:border-slate-700
           bg-slate-100/80 dark:bg-slate-800/70
           p-4 space-y-3 animate-pulse"
  >
    <div class="h-3 w-2/3 rounded bg-slate-300 dark:bg-slate-700"></div>
    <div class="h-2 w-full rounded bg-slate-300 dark:bg-slate-700"></div>
    <div class="h-2 w-5/6 rounded bg-slate-300 dark:bg-slate-700"></div>
    <div class="flex gap-2 pt-1">
      <div class="h-6 w-12 rounded-full bg-slate-300 dark:bg-slate-700"></div>
      <div class="h-6 w-10 rounded-full bg-slate-300 dark:bg-slate-700"></div>
    </div>
  </div>
</template>
