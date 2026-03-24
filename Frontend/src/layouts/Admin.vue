<script setup>
import Sidebar from "@/components/admin/Sidebar.vue";
</script>

<template>
  <div class="flex h-screen">
    <!-- Sidebar -->
    <Sidebar />

    <!-- Main Content -->
    <main class="flex-1 overflow-x-hidden overflow-y-auto">
      <!-- Topbar -->
      <div class="bg-white shadow-sm sticky top-0 z-10">
        <div class="flex items-center justify-between px-6 py-4">
          <h2 class="text-xl font-semibold text-gray-800">
            {{ $route.meta.title }}
          </h2>
          <div class="flex items-center space-x-4">
            <div class="flex items-center bg-gray-50 px-4 py-2 rounded-full">
              <img
                src="https://ui-avatars.com/api/?name=Admin&background=0D8ABC&color=fff"
                alt="Profile"
                class="w-8 h-8 rounded-full"
              />
              <span class="ml-2 text-sm font-medium text-gray-700">Admin</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Content -->
      <div class="p-6 space-y-6">
        <router-view></router-view>
      </div>
    </main>
  </div>
</template>
